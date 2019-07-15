<?php

namespace Drmer\Phpwoo\Laravel;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class Response
{
    private $req;
    private $resp;

    protected $name = null;

    public function __construct($req, $resp)
    {
        $this->req = $req;
        $this->resp = $resp;

        $this->name = config('phpwoo.name');
    }

    public function send($res)
    {
        $resp = $this->resp;

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
        if (strtolower($this->req->server['request_method']) == 'head') {
            $resp->end();
            return;
        }
        $resp->end($res['body']);
    }

    public function getName()
    {
        return $this->name;
    }
}
