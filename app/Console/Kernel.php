<?php

namespace App\Console;

use App\Console\Commands\Settle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        Settle::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('rebate:settle')->daily(); //用户待结算收入结算
        $schedule->command('teamFound:close')->everyMinute(); //拼团有效期结束，未拼成的单退款
        $schedule->command('store:transfer')->dailyAt('01:00'); //商家订单结算
        $schedule->command('order:confirm')->dailyAt('02:00'); //订单发货后14天确认收货
        $schedule->command('order:cancel')->dailyAt('03:00'); //48小时未付款取消订单
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
