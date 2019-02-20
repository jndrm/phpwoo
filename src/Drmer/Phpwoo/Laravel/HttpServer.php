<?php

namespace Drmer\Phpwoo\Laravel;

use Swoole\Http\Server;

class HttpServer extends BaseServer
{
    protected $http = null;

    public function start()
    {
        $this->create();

        $this->setup();

        $this->registerEvents();

        $this->http->start();
    }

    public function create()
    {
        $this->http = new Server($this->host, $this->port);
    }

    public function setup()
    {
        $this->http->set(config('phpwoo.swoole'));
    }

    public function registerEvents()
    {
        $this->http->on("start", function ($server) {
            echo "Phpwoo http server is started at http://{$this->host}:{$this->port}\n";
        });

        $this->http->on('request', array($this, 'onRequest'));
    }

    public function httpCall($req)
    {
    	return HttpClient::getInstance()->handle($req);
    }
}
