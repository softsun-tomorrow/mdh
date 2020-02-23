<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanOrder extends Model
{
    //扫码付款订单
    //支付方式:0=会员卡,1=支付宝,2=微信
    protected $table = 'scan_order';

    public $timestamps = false;

    /**
     * 支付后调用
     * @param string $order_sn
     */
    public static function doAfterPay($order_sn){
        $order = self::where('order_sn',$order_sn)->first();
        $order->pay_status = 1;
        $order->pay_time = date('Y-m-d H:i:s');
        $order->save();

        if($order->pay_type == 0){
            //会员卡
            //减会员卡余额
            $card = Card::getUserOnlineCard($order->user_id,$order->store_id);
            Card::cardAccountLog($card->id,'-'.$order->account,1,4,$order->order_sn,'扫码支付');
            //增加店铺余额
            Store::storeAccountLog(0,3,$order->store_id,$order->account,'扫码支付',$order->order_sn);

        }else{
            //增加店铺余额
            Store::storeAccountLog(0,3,$order->store_id,$order->account,'扫码支付',$order->order_sn);

        }
    }
}
