<?php

namespace Drmer\Phpwoo\Laravel\Commands;

use Swoole\Process;
use Swoole\Server;
use Drmer\Phpwoo\Laravel\HttpServer;
use Illuminate\Console\Command;
use Drmer\Phpwoo\Laravel\ServerCallbackInterface;

class ServeCommand extends Command implements ServerCallbackInterface
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phpwoo {action=start : start|stop}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start or stop Phpwoo Server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->getAction();

        $method = "call" . ucfirst($action);

        if (!method_exists($this, $method)) {
            $this->error("Method not found");
            return;
        }
        $this->$method();
    }

    private function getAction()
    {
        $action = $this->argument('action') ?: 'start';
        if (!in_array($action, ['start', 'stop'])) {
            $this->error("Invalid argument");
            exit(1);
        }
        return $action;
    }

    public function onStart(Server $server)
    {
        @file_put_contents($this->pidPath(), $server->master_pid);
    }

    private function callStart()
    {
        with(new HttpServer($this))->start();
    }

    private function callStop()
    {
        $pid = $this->getPid();
        if (!$this->isRunning($pid)) {
            $this->error("Failed! There is no server process running.");
            exit(1);
        }
        $this->info('Stopping server...');

        if ($this->killProcess($pid, SIGTERM, 15)) {
            $this->error('Unable to stop the server process.');
            exit(1);
        }
        $this->info("\tsuccess");
    }

    private function pidPath()
    {
        return storage_path('phpwoo.pid');
    }

    private function isRunning($pid)
    {
        if (!$pid) {
            return false;
        }
        Process::kill($pid, 0);
        return !swoole_errno();
    }

    /**
     * Kill process.
     *
     * @param int $pid
     * @param int $sig
     * @param int $wait
     */
    protected function killProcess($pid, $sig, $wait = 0)
    {
        Process::kill($pid, $sig);

        $startTime = time();
        do {
            if (!$this->isRunning($pid)) {
                return false;
            }
            usleep(100000);
        } while (time() < $startTime + $wait);
        return $this->isRunning($pid);
    }

    private function getPid()
    {
        return intval(@file_get_contents($this->pidPath()));
    }
}
