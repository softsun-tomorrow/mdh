<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoods extends Model
{
    /**
     * is_send : 是否发货：0未发货，1已发货，2已换货，3已退货
     *
     */
    protected $table = 'order_goods';

    public $timestamps = false;

    protected $appends = ['goods_cover'];

    //type 商品类型：0=普通商品, 1=升级赚钱, 2=麦穗抵扣, 3=活动专区
    const GOODS_TYPE = [0 => '普通商品', 1 => '升级赚钱', 2 => '麦穗抵扣', 3 => '活动专区'];

    public function getGoodsCoverAttribute(){
        if($goods = Goods::find($this->goods_id)) return $goods->cover;
    }

    public function goods(){
        return $this->belongsTo('App\Models\Goods');
    }

    public function getGoodsTypeTextAttribute()
    {
        return isset($this->goods_type) ? self::GOODS_TYPE[$this->goods_type] : '';
    }

    public function return_goods()
    {
        return $this->hasOne(ReturnGoods::class);
    }


}
