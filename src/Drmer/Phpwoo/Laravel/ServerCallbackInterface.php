<?php

namespace Drmer\Phpwoo\Laravel;

use Swoole\Server;

interface ServerCallbackInterface {
    public function onStart(Server $server);
}
