<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardScoreLog extends Model
{
    //会员卡积分记录
    protected  $table = 'card_score_log';

    protected $appends = [
        'type_text',
        'source_text'
    ];

    //类型:0=收入,1=支出
    public static function getTypeArr()
    {
        return [0 => '收入', 1 => '支出'];
    }

    public function getTypeTextAttribute()
    {
        return self::getTypeArr()[$this->type];
    }

    //来源:0=签到赠送，1=充值，2=订单抵扣，3=积分兑换
    public static function getSourceArr()
    {
        return [0 => '签到赠送', 1 => '充值', 2 => '订单抵扣',3 => '积分兑换'];
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
