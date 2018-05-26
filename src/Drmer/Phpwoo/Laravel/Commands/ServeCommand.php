<?php

namespace Drmer\Phpwoo\Laravel\Commands;

use Log;
use Drmer\Phpwoo\Laravel\HttpServer;
use Illuminate\Console\Command;

class ServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phpwoo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Phpwoo Server';

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
        Log::getMonolog()->popHandler();
        Log::useDailyFiles(storage_path('logs/log-phpwoo.log'));
        with(new HttpServer)->start();
    }
}