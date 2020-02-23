<?php

namespace App\Console\Commands;

use App\Logic\TeamOrderLogic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseTeamFound extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teamFound:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '拼团有效期结束，未拼成的单退款';


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
        //
        //Log::info('拼团有效期结束，未拼成的单退款任务开始');
        $teamOrderLogic = new TeamOrderLogic();
        $teamOrderLogic->autoCheck();
    }
}
