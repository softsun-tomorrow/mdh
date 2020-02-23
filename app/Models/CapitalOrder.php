<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CapitalOrder extends Model
{
    //
    protected $table = 'capital_order';
    public $timestamps = false;

    public static function doAfterPay($order_sn){
        $order = DB::table('capital_order')->where('order_sn',$order_sn)->first();
        $user = User::find($order->user_id);
        //给用户增加资金
        User::capitalLog($user->id,$order->order_amount,$order->order_sn,'用户充值资金',0,0);
    }
}
