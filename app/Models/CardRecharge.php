<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CardRecharge extends Model
{
    //会员卡充值订单

    protected $table = 'card_recharge';
    public  $timestamps = false;

    public static function doAfterPay($order_sn){
        $order = self::where('order_sn',$order_sn)->first();
        $order->pay_status = 1;
        $order->pay_time = date('Y-m-d H:i:s');
        $order->save();

        Card::cardAccountLog($order->card_id,$order->account,0, 1,$order_sn,'充值会员卡余额');
        Store::storeAccountLog(0,5,$order->store_id,$order->account,'会员卡充值',$order->order_sn); //给店铺增加余额
        \App\Models\ExpenseLog::add(0,2,$order->account,0,'','充值会员卡余额');

    }
}
