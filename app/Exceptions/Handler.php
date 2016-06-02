<?php namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler {

	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		'Symfony\Component\HttpKernel\Exception\HttpException'
	];

	/**
	 * Report or log an exception.
	 *
	 * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function report(Exception $e)
	{
		return parent::report($e);
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Exception $e)
	{
		if($e instanceof MethodNotAllowedHttpException){
			return response()->json(['code'=>404, 'type'=>'not supported', 'message'=>sprintf('Method `%s` Is Not Supported', $request->getMethod())], 404);
		}

		if($e instanceof NotFoundHttpException){
			return response()->json(['code'=>404, 'type'=>'not found', 'message'=>$e->getMessage() ? $e->getMessage() : sprintf('The requested url `%s` is not found in this server', $request->fullUrl())], 404);
		}

		if ($e instanceof \Bican\Roles\Exceptions\RoleDeniedException) {
			\Auth::logout();
			return redirect('/');
		}

		return response()->json(['code'=>404, 'type'=>'unknown', 'message'=>$e->getMessage() ? $e->getMessage() : 'Unknown error'], 404);
//		return parent::render($request, $e);
	}

}
