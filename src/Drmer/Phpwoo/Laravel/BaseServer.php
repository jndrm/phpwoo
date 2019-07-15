<?php

namespace Drmer\Phpwoo\Laravel;

abstract class BaseServer
{
	protected $enableCache = true;

    protected $host = '0.0.0.0';

    protected $port = '8264';

    protected $cache = null;

    protected $name = null;

    public function __construct()
    {
    	$this->cache = new RequestCache();

        $this->enableCache = config('phpwoo.cache');

        $this->host = config('phpwoo.host');

        $this->port = config('phpwoo.port');

        $this->name = config('phpwoo.name');
    }

	public function sendResp($resp, $res)
    {
        $resp->status($res['status']);
        foreach ($res['headers']->all() as $key => $values) {
            if (in_array(strtolower($key), ['server', 'x-powered-by'])) {
                continue;
            }
            if (strtolower($key) == 'set-cookie') {
                continue;
            }
            if (count($values) == 1) {
                $resp->header($key, $values[0]);
            } else {
                $resp->header($key, implode(";", $values));
            }
        }
        foreach ($res['headers']->getCookies() as $cookie) {
            $resp->cookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }
        if ($name = $this->getName()) {
            $resp->header('server', $name);
        }
        $resp->end($res['body']);
    }

    public function onRequest($req, $resp)
    {
    	$cache = $this->cache;
    	$res = $this->enableCache ? $cache->get($req) : null;
        if (!$res) {
            $res = $this->httpCall($req);
            if ($this->enableCache && $cache->should($req, $res)) {
                $cache->put($req, $res);
            }
        }
        $this->sendResp($resp, $res);
    }

    public function getName()
    {
        return $this->name;
    }

    public abstract function httpCall($req);
}
