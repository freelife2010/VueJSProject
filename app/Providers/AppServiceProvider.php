<?php namespace App\Providers;

use App\Helpers\SidebarHelper;
use App\Models\App;
use Illuminate\Support\ServiceProvider;

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
            $model    = (isset($viewData['app'])
                            and $viewData['app'] instanceof App) ? $viewData['app'] : null;
            $helper   = new SidebarHelper($model);
            $view->with(compact('helper'));
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
	}

}
