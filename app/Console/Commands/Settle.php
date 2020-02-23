<?php

namespace App\Console\Commands;

use App\Logic\UserLogic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Settle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebate:settle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用户待结算收入结算';

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
        Log::info('待结算收入自动结算任务开始');
        //待结算收入的钱过了结算期后，自动流入余额
        $userLogic = new UserLogic();
        $userLogic->autoSettle();

    }
}
