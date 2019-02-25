<?php

namespace Drmer\Phpwoo\Laravel;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Client;
use Illuminate\Support\Facades\Facade;
use App\Http\Kernel as HttpKernel;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use File;

class HttpClient
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Additional server variables for the request.
     *
     * @var array
     */
    protected $serverVariables = [];

    private static $instance;

    private function __construct()
    {
        $this->app = $this->createApplication();
    }

    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($req)
    {
        $params = $req->post ?: [];

        $files = $this->transformFiles($req->files);

        $cookies = $req->cookie ?: [];

        $server = $this->transformHeadersToServerVars(array_merge($req->header, [
            'PATH_INFO' => array_get($req->server, 'path_info'),
        ]));
        $server['X_REQUEST_ID'] = Str::uuid()->toString();

        $requestUri = $req->server['request_uri'];
        if (isset($req->server['query_string']) && $req->server['query_string']) {
            $requestUri .= "?" . $req->server['query_string'];
        }

        $resp = $this->call(
            strtolower($req->server['request_method']),
            $requestUri, $params, $cookies, $files,
            $server, $req->rawContent()
        );

        return [
            'status' => $resp->getStatusCode(),
            'headers' => $resp->headers,
            'body' => $resp->getContent(),
        ];
    }

    protected function transformFiles($reqFiles)
    {
        $files = [];
        foreach ((array)$reqFiles as $key => $file) {
            $files[$key] = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
        }
        return $files;
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     * @see https://github.com/laravel/framework/blob/5.6/src/Illuminate/Foundation/Testing/Concerns/MakesHttpRequests.php
     *
     * @param  array  $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        return collect($headers)->mapWithKeys(function ($value, $name) {
            $name = strtr(strtoupper($name), '-', '_');

            return [$this->formatServerHeaderKey($name) => $value];
        })->all();
    }

    /**
     * Format the header name for the server array.
     * @see https://github.com/laravel/framework/blob/5.6/src/Illuminate/Foundation/Testing/Concerns/MakesHttpRequests.php
     *
     * @param  string  $name
     * @return string
     */
    protected function formatServerHeaderKey($name)
    {
        if (! Str::startsWith($name, 'HTTP_') && $name != 'CONTENT_TYPE' && $name != 'REMOTE_ADDR') {
            return 'HTTP_'.$name;
        }

        return $name;
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string  $content
     * @return \Illuminate\Foundation\Response
     */
    protected function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make(HttpKernel::class);

        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest($uri), $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );

        $response = $kernel->handle(
            $request = Request::createFromBase($symfonyRequest)
        );

        $kernel->terminate($request, $response);

        $this->flushSession();

        $this->reset();

        return $response;
    }

    /**
     * Turn the given URI into a fully qualified URL.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }

        if (!Str::startsWith($uri, 'http')) {
            $uri = env('APP_URL', 'http://localhost').'/'.$uri;
        }

        return trim($uri, '/');
    }

    protected function createApplication()
    {
        return require base_path('/bootstrap/app.php');
    }

    protected function flushSession()
    {
        if ($this->app->resolved('session')) {
            foreach ($this->app['session']->getDrivers() as $driver) {
                $driver->flush();
                $driver->migrate();
            }
        }
    }

    protected function reset()
    {
        $resets = $this->app['config']->get('phpwoo.resets', []);
        foreach ($resets as $abstract) {
            if (is_subclass_of($abstract, ServiceProvider::class)) {
                $this->registerServiceProvider($abstract);
            } elseif ($this->app->has($abstract)) {
                $this->rebindAbstract($abstract);

                Facade::clearResolvedInstance($abstract);
            }
        }
    }

    /**
     * Rebind abstract.
     *
     * @param string $abstract
     * @return void
     */
    protected function rebindAbstract($abstract)
    {
        $abstract = $this->app->getAlias($abstract);
        $binding = array_get($this->app->getBindings(), $abstract);

        unset($this->app[$abstract]);

        if ($binding) {
            $this->app->bind($abstract, $binding['concrete'], $binding['shared']);
        }
    }
}
