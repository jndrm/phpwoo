<?php

namespace Drmer\Phpwoo\Laravel;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Client;
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

    public function __construct()
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
        $params = [];
        if ($req->post) {
            $params = array_merge($params, $req->post);
        }
        $content = $req->rawContent();
        $files = [];
        if (is_array($req->files)) {
            foreach ($req->files as $key => $file) {
                $files[$key] = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
            }
        }
        $cookies = [];
        $server = [
            'PATH_INFO' => array_get($req->server, 'path_info'),
        ];
        foreach ($req->header as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');
            $_name = $this->formatServerHeaderKey($name);
            $server[$_name] = $value;
        }
        $requestUri = $req->server['request_uri'];
        if (isset($req->server['query_string']) && $req->server['query_string']) {
            $requestUri .= "?" . $req->server['query_string'];
        }
        $resp = $this->call(strtolower($req->server['request_method']), $requestUri, $params, $cookies, $files, $server, $content);
        return [
            'status' => $resp->getStatusCode(),
            'headers' => $resp->headers->all(),
            'body' => $resp->getContent(),
        ];
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
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
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

        if (! Str::startsWith($uri, 'http')) {
            $uri = env('APP_URL', 'http://localhost').'/'.$uri;
        }

        return trim($uri, '/');
    }

    public function createApplication()
    {
        return require base_path('/bootstrap/app.php');
    }
}