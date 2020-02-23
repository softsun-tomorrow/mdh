<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreAccountLog extends Model
{
    //
    protected $table = 'store_account_log';

    public static function getTypeArr(){
        //类型:0=收入，1=支出
        return [0 => '收入',1=>'支出'];
    }

    public static function getSourceArr(){
        //来源：0=订单结算,1=发布大喇叭,2=提现,3=扫码支付收款，4=会员卡开卡，5=会员卡充值
        return [0 => '订单结算', 1 => '发布大喇叭',2 => '提现', 3 => '扫码支付收款',4 => '会员卡开卡', 5 => '会员卡充值'];
    }

    public function store(){
        return $this->belongsTo('App\Models\Store');
    }



}
