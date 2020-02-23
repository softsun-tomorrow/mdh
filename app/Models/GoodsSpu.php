<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsSpu extends Model
{
    //商品属性表
    protected $table = 'goods_spu';

    public $timestamps = false;

    protected $fillable = ['spu_name','spu_value'];

    public function goods(){
        return $this->belongsTo('App\Models\Goods');
    }

}
