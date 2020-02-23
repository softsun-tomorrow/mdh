<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nav extends Model
{
    //导航
    protected $table = 'nav';

    public $timestamps = false;

    public static function getTypeArr(){
        return [0 => '商品分类', 1 => '邀请返钱', 2 => '签到领钱', 3 => '抵扣专区', 4 => '活动专区'];
    }
}
