<?php

namespace Drmer\Phpwoo\Laravel;

use Cache;

class RequestCache
{
    public function get($req)
    {
        $server = $req->server;
        $method = strtolower($server['request_method']);
        if (!in_array($method, ['get', 'head'])) {
            return false;
        }
        $cacheKey = md5($server['request_uri']);
        return Cache::get($cacheKey);
    }

    public function should($req, $res)
    {
        $server = $req->server;
        $method = strtolower($server['request_method']);
        if (!in_array($method, ['get', 'head'])) {
            return false;
        }
        if ($res['status'] != 200) {
            return false;
        }
        if ($res['headers']->getCacheControlDirective('private')) {
            return false;
        }
        return true;
    }

    public function put($req, $res)
    {
        $maxAge = $res['headers']->getCacheControlDirective('max-age');
        if ($maxAge <= 0) {
            return;
        }
        $res['headers']->remove('set-cookie');

        $key = md5($req->server['request_uri']);
        $expiresAt = now()->addSeconds($maxAge);
        Cache::put($key, $res, $expiresAt);
    }
}
