<?php

namespace App\Jobs;

use App\Logic\OrderLogic;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * // 代表这个类需要被放到队列中执行，而不是触发时立即执行
 * Class CloseOrder
 * @package App\Jobs
 */
class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, $delay)
    {
        //
        $this->order = $order;
        // 设置延迟的时间，delay() 方法的参数代表多少秒之后执行
        $this->delay($delay);
    }

    /**
     * 定义这个任务类具体的执行逻辑
     * 当队列处理器从队列中取出任务时，会调用 handle() 方法
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 判断对应的订单是否已经被支付
        // 如果已经支付则不需要关闭订单，直接退出
        if ($this->order['pay_status'] == 1) {
            return;
        }

        // 通过事务执行 sql
        DB::transaction(function() {
            $orderLogic = new OrderLogic();
            $orderLogic->setOrder($this->order);
            $orderLogic->setUserId($this->order['user_id']);
            $orderLogic->cancelOrder();
        });

    }
}
