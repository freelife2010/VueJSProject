@extends('app')
@section('wrapper_content')
	<div class="block-center mt-xl wd-xl">
		<div class="panel panel-dark panel-flat">
			<div class="panel-heading text-center">
				<a href="/">
					<img src="/img/logo.png" alt="Image" class="block-center img-rounded">
				</a>
			</div>
			<div class="panel-body">
				<p>An email was sent to {{ $email }} on {{ $date }}.</p>

				<p>{{ Lang::get('auth.clickInEmail') }}</p>

				<p><a href='/resendEmail'>{{ Lang::get('auth.clickHereResend') }}</a></p>
				<div class="text-center">
					<a class="btn btn-default" href="{{url('auth/logout')}}">
						Logout
					</a>
				</div>
			</div>
		</div>
	</div>
@endsection

