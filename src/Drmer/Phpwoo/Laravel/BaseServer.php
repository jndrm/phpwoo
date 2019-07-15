<?php

namespace Drmer\Phpwoo\Laravel;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Drmer\Phpwoo\Laravel\ServerCallbackInterface;

abstract class BaseServer
{
	protected $enableCache = true;

    protected $host = '0.0.0.0';

    protected $port = '8264';

    protected $cache = null;

    protected $callback = null;

    public function __construct(ServerCallbackInterface $callback=null)
    {
    	$this->cache = new RequestCache();

        $this->enableCache = config('phpwoo.cache');

        $this->host = config('phpwoo.host');

        $this->port = config('phpwoo.port');

        $this->callback = $callback;
    }

    public function onRequest(SwooleRequest $req, SwooleResponse $resp)
    {
    	$cache = $this->cache;
    	$res = $this->enableCache ? $cache->get($req) : null;
        if (!$res) {
            $res = $this->httpCall($req);
            if ($this->enableCache && $cache->should($req, $res)) {
                $cache->put($req, $res);
            }
        }

        with(new Response($req, $resp))->send($res);
    }

    public abstract function httpCall($req);
}
