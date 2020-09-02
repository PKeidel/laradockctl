<?php

namespace PKeidel\Laradockctl\Providers;

use Illuminate\Support\ServiceProvider;
use PKeidel\Laradockctl\Commands\LaradockConfigureCommand;
use PKeidel\Laradockctl\Commands\LaradockInstallCommand;
use PKeidel\Laradockctl\Commands\LaradockSetEnvCommand;
use PKeidel\Laradockctl\Commands\LaradockStopCommand;
use PKeidel\Laradockctl\Commands\LaradockUpCommand;
use PKeidel\Laradockctl\Commands\LaradockUpdateCommand;
use PKeidel\Laradockctl\Commands\LaradockExecCommand;

class LaradockctlServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {

//        $this->publishes([
//            __DIR__.'/path/to/config/laradockctl.php' => config_path('laradockctl.php'),
//        ]);
//        $value = config('laradockctl.option');

//        $this->loadRoutesFrom(__DIR__.'/../routes.php');

//        $this->loadTranslationsFrom(__DIR__.'/../translations', 'laradockctl');

//        $this->loadViewsFrom(__DIR__.'/../views', 'laradockctl');

        if ($this->app->runningInConsole()) {
            $this->commands([
                LaradockInstallCommand::class,
                LaradockUpdateCommand::class,
                LaradockSetEnvCommand::class,
                LaradockConfigureCommand::class,
                LaradockUpCommand::class,
                LaradockStopCommand::class,
                LaradockExecCommand::class,
            ]);
        }

//        $this->publishes([
//            __DIR__.'/path/to/assets' => public_path('vendor/courier'),
//        ], 'public');
//        // php artisan vendor:publish --tag=public --force
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
    }
}
