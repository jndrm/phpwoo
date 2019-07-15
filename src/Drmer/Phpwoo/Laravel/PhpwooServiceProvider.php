<?php

namespace Drmer\Phpwoo\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

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

        Request::macro('xId', function() {
            return $this->server('X_REQUEST_ID');
        });
    }

    protected function configPath()
    {
        return __DIR__ . '/config/phpwoo.php';
    }
}
