<?php

namespace Drmer\Phpwoo\Laravel;

use Illuminate\Support\ServiceProvider;

class PhpwooServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->configPath() => config_path('phpwoo.php')
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Commands\ServeCommand::class,
        ]);

        $this->mergeConfigFrom($this->configPath(), 'phpwoo');
    }

    protected function configPath()
    {
        return __DIR__ . '/config/phpwoo.php';
    }
}
