<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardAccountLog extends Model
{
    //
    protected $table = 'card_account_log';

    protected $appends = [
        'type_text',
        'source_text'
    ];

    //类型:0=收入,2=支出
    public static function getTypeArr()
    {
        return [0 => '收入', 1 => '支出'];
    }

    public function getTypeTextAttribute()
    {
        return self::getTypeArr()[$this->type];
    }

    //来源:0=签到赠送，1=充值，2=订单抵扣，3=取消订单退回,4=扫码支付
    public static function getSourceArr()
    {
        return [0 => '签到赠送', 1 => '充值', 2 => '订单抵扣', 3 => '取消订单退回', 4 => '扫码支付'];
    }

    public function getSourceTextAttribute()
    {
        return self::getSourceArr()[$this->source];

    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function card(){
        return $this->belongsTo('App\Models\Card');
    }
}
