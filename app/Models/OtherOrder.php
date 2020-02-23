<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OtherOrder extends Model
{
    //
    protected $table = 'other_order';
    public $timestamps = false;


    public static function doAfterPay($order_sn)
    {

        $order = DB::table('other_order')->where('order_sn',$order_sn)->first();

        //修改入驻是否缴费
        DB::table('store')->where('id',$order->store_id)->update([
            'pay_status' => 1
        ]);

        //给小区长返利
        if($order->extra){
            User::moneyLog($order->extra,$order->order_amount,$order->order_sn,'邀请商家入驻佣金',0,6);

            //增加邀请记录
            DB::table('invite')->insert([
                'user_id' => $order->extra,
                'store_id' => $order->store_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

    }
}
