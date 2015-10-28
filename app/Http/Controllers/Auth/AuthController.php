<?php namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Bican\Roles\Models\Role;
use Hash;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades;
use Mail;
use Validator;

class AuthController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Registration & Login Controller
	|--------------------------------------------------------------------------
	|
	| This controller handles the registration of new users, as well as the
	| authentication of existing users. By default, this controller uses
	| a simple trait to add these behaviors. Why don't you explore it?
	|
	*/

	use AuthenticatesAndRegistersUsers;

	/**
	 * Create a new authentication controller instance.
	 *
	 * @param  \Illuminate\Contracts\Auth\Guard  $auth
	 * @param  \Illuminate\Contracts\Auth\Registrar  $registrar
	 * @return void
	 */
	public function __construct()
	{

		$this->middleware('guest',
			['except' =>
				['getLogout', 'resendEmail', 'activateAccount']]);
	}

	/**
	 * Get a validator for an incoming registration request.
	 *
	 * @param  array  $data
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	public function validator(array $data)
	{
		return Validator::make($data, [
				'name' => 'required|max:255',
				'email' => 'required|email|max:255|unique:accounts',
				'password' => 'required|confirmed|min:6',
				]);
	}

	/**
	 * Handle a registration request for the application.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function postRegister(Request $request)
	{

		$validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $activation_code       = str_random(60) . $request->input('email');
        $user                  = new User;
        $user->name            = $request->input('name');
        $user->email           = $request->input('email');
        $user->password        = $request->input('password');
        $user->activation_code = $activation_code;
        $user->resent          = 0;

		if ($user->save()) {
            $role = Role::whereSlug('developer')->first();
            $user->attachRole($role);
			$this->sendEmail($user, 'authorization');

			return view('auth.activateAccount')
				->with('email', $request->input('email'));

		} else {

			\Session::flash('message', \Lang::get('notCreated') );
			return redirect()->back()->withInput();

		}

	}
	//*/

	public function sendEmail(User $user, $type)
	{

		$email = Email::whereType($type)->first();
        $email->replaceMarkers($user);
        $data = [
            'content' => $email->content
        ];

		Mail::queue('emails.base', $data, function($message) use ($user, $email) {
            $address = env('MAIL_FROM', 'admin@adm.dev');
            $name    = env('MAIL_FROM_NAME', 'AdminUI');
            $message->from($address, $name);
			$message->subject($email->subject);
			$message->to($user->email);
		});
	}

	public function resendEmail()
	{
		$user = \Auth::user();
		if( $user->resent >= 3 )
		{
			return view('auth.tooManyEmails')
				->with('email', $user->email);
		} else {
			$user->resent = $user->resent + 1;
			$user->save();
			$this->sendEmail($user, 'authorization');
			return view('auth.activateAccount')
				->with('email', $user->email);
		}
	}

	public function activateAccount($code, User $user)
	{

		if($user->accountIsActive($code)) {
            $this->sendEmail($user, 'confirmation');
			\Session::flash('message', \Lang::get('auth.successActivated') );
			return redirect('app/list');
		}

		\Session::flash('message', \Lang::get('auth.unsuccessful') );
		return redirect('app/list');

	}

}
