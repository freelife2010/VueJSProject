<?php namespace App\Providers;

use App\Helpers\SidebarHelper;
use App\Models\App;
use Illuminate\Support\ServiceProvider;
use Queue;

class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        //Adds sidebar helper to every view of this layout
        app('view')->composer('layouts.default', function ($view) {
            $viewData = $view->getData();
            $model    = (isset($viewData['APP'])
                            and $viewData['APP'] instanceof App) ? $viewData['APP'] : null;
            $helper   = new SidebarHelper($model);
            $view->with(compact('helper'));
        });

        Queue::failing(function ($connection, $job, $data) {
            $job->delete();
        });
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application. As you can see, we are registering our
	 * "Registrar" implementation here. You can add your own bindings too!
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(
			'Illuminate\Contracts\Auth\Registrar',
			'App\Services\Registrar'
		);

		$this->app->register('Darkaonline\L5Swagger\L5SwaggerServiceProvider');
	}

}
