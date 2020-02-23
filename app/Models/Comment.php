<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    //订单评论

    protected $table = 'comment';
    public $timestamps = false;

    public function order_goods(){
        return $this->belongsTo('App\Models\OrderGoods');
    }

    public function goods(){
        return $this->belongsTo('App\Models\Goods');
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function getImagesAttribute($images){
        return $images ? explode(',',$images) : [];
    }

    public static function getIsShowArr(){
        return [0 => '否', 1 => '是'];
    }


}
