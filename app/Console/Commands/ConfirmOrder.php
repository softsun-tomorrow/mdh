<?php

namespace App\Console\Commands;

use App\Logic\OrderLogic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConfirmOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:confirm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单发货后14天确认收货';

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
        Log::info('订单发货后14天确认收货');
        $orderLogic = new OrderLogic();
        $orderLogic->autoConfirmOrder();
    }
}
