<?php

namespace App\Console\Commands;

use App\Logic\StoreLogic;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StoreTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '商家订单结算';

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
        Log::info('商家订单结算任务开始');

        $storeLogic = new StoreLogic();
        Store::where('status',1)->chunk(100,function($stores) use ($storeLogic){
            foreach ($stores as $store){
                $storeLogic->setStoreId($store->id);
                $storeLogic->autoTransfer();
            }
        });
    }
}
