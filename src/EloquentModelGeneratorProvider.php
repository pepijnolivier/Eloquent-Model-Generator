<?php namespace User11001\EloquentModelGenerator;

use Illuminate\Support\ServiceProvider;

class EloquentModelGeneratorProvider extends ServiceProvider {
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {

        $this->app->singleton('command.models.generate', function ($app) {
            return $app['User11001\EloquentModelGenerator\Console\GenerateModelsCommand'];
        });

        $this->commands('command.models.generate');
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return array('generatemodels', 'command.models.generate');
    }
}
