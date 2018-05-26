<?php

namespace Drmer\Phpwoo\Laravel;

use Swoole\Http\Server;

class HttpServer extends BaseServer
{
    public function start()
    {
        $http = new Server($this->host, $this->port);

        $http->set(config('phpwoo.swoole'));

        $http->on("start", function ($server) {
            echo "Phpwoo http server is started at http://{$this->host}:{$this->port}\n";
        });

        $http->on('request', array($this, 'onRequest'));

        $http->start();
    }

    public function httpCall($req)
    {
    	return HttpClient::getInstance()->handle($req);
    }
}