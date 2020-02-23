<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeOrder extends Model
{
    //兑换订单
    //状态: 0=未发货 ，1=已发货
    protected $table = 'exchange_order';

    public $timestamps = false;

    public static function getStatusArr(){
        return [0 => '未发货', 1 => '已发货'];
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function exchange(){
        return $this->belongsTo('App\Models\Exchange');
    }
}
