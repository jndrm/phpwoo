<?php

namespace Drmer\Phpwoo\Laravel;

use Cache;

class RequestCache
{
	public function get($req)
    {
        $server = $req->server;
        if (strtolower($server['request_method']) != 'get') {
            return null;
        }
        $cacheKey = md5($server['request_uri']);
        $content = Cache::get($cacheKey);
        if ($content) {
            return unserialize($content);
        }
        return null;
    }

    public function should($req, $res)
    {
        $server = $req->server;
        if (strtolower($server['request_method']) != 'get') {
            return false;
        }
        if ($res['status'] != 200) {
            return false;
        }
        return true;
    }

    public function put($req, $res)
    {
        $key = md5($req->server['request_uri']);
        echo "caching $key\n";
        Cache::put($key, serialize($res), 10);
    }
}