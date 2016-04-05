<?php namespace App\Http\Controllers;

use App\Helpers\Misc;
use Auth;
use Response;
use Storage;

class HomeController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Home Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders your application's "dashboard" for users that
	| are authenticated. Of course, you are free to change or remove the
	| controller as you wish. It is just here to get your app started!
	|
	*/
	/**
	 * HomeController constructor.
	 */
	public function __construct()
	{
		$this->middleware('auth');
	}


	/**
	 * Show the application dashboard to the user.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
		$user = Auth::user();
		return $user->isAdmin() ? redirect('users') : redirect('app/list');
	}

	public function getVoiceMail($filename)
	{
		if (Storage::disk('voicemail')->has($filename))
			return Response::download(storage_path('app/voice')."/$filename", $filename);
		else return redirect('/');

	}

}
