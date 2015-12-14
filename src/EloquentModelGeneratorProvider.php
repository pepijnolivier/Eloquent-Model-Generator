<?php

namespace User11001\EloquentModelGenerator;

use Illuminate\Support\ServiceProvider;

class EloquentModelGeneratorProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('command.models.generate', function ($app) {
            return $app['User11001\EloquentModelGenerator\Console\GenerateModelsCommand'];
        });

        $this->commands('command.models.generate');
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $viewPath = __DIR__ . '/Console/templates/';
        $this->loadViewsFrom($viewPath, 'views');

        $this->publishes([
           $viewPath => base_path('resources/eloquent-model-generator-templates'),
        ]);

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('generatemodels', 'command.models.generate');
    }
}
