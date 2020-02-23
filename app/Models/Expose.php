<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expose extends Model
{
    //举报
    /**
     * 状态: 0=未处理, 1=已处理
     * 处理结果:1=无效举报,2=恶意举报,3=有效举报
     */
    protected $table = 'expose';

    public static function getStatusArr(){
        //状态: 0=未处理, 1=已处理
        return [0 => '未处理', 1=> '已处理'];
    }

    public static function getHandleTypeArr(){
        //处理结果:1=无效举报,2=恶意举报,3=有效举报
        return [1 => '无效举报', 2 => '恶意举报', 3 => '有效举报'];
    }

    public function getImgsAttribute($value){
        return explode(',',$value);
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function store(){
        return $this->belongsTo('App\Models\Store');
    }


}
