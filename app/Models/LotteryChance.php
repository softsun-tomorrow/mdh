<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryChance extends Model
{
    //抽奖次数
    protected $table = 'lottery_chance';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
