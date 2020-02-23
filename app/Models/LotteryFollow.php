<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryFollow extends Model
{
    //抽奖记录
    //状态：0=待支付, 1=待开奖, 2=中奖，3=未中奖
    protected $table = 'lottery_follow';
    public $timestamps = false;
    const STATUS = [0 => '待支付', 1 => '待开奖', 2 => '中奖', 3 => '未中奖' ];

    public function lottery()
    {
        return $this->belongsTo(Lottery::class,'lottery_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusTextAttribute()
    {
        return isset($this->status) ? self::STATUS[$this->status] : '';
    }


}
