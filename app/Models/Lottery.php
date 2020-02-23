<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lottery extends Model
{
    //抽奖
    protected $table = 'lottery';
    public $timestamps = false;

    protected $appends = [
        'status_text',
        'goods_cover',
        'original_price',
    ];

    //0待审核1正常2拒绝3关闭
    public static function getStatusArr(){
        return [0 => '待审核', 1 => '正常', 2 => '拒绝', 3 => '关闭',4 => '已开奖'];
    }

    public function goods(){
        return $this->belongsTo('App\Models\Goods');
    }

    public function getStatusTextAttribute()
    {
        return $this->status ? self::getStatusArr()[$this->status] : '';
    }

    public function getGoodsCoverAttribute()
    {
        $goods = Goods::find($this->goods_id);
        return $goods ? $goods->cover : '';
    }

    public function getOriginalPriceAttribute()
    {
        $goods = Goods::find($this->goods_id);
        return $goods ? $goods->shop_price : 0;

    }

    public static function getIsRecArr()
    {
        return [0 => '否', 1 => '是'];
    }


}
