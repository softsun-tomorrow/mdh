<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamActivity extends Model
{
    //拼团
    protected $table = 'team_activity';
    public $timestamps = false;
    protected $appends = [
        'status_text',
        'original_price',
        'goods_cover'
    ];

    public static function getStatusArr(){
        //0待审核1正常2拒绝3关闭
        return [0 => '待审核',1 => '正常', 2 => '拒绝', 3 => '关闭',4 => '商品售罄'];
    }

    public function goods(){
        return $this->belongsTo('App\Models\Goods');
    }

    public function getStatusTextAttribute(){
        if(isset($this->status)) return self::getStatusArr()[$this->status];

    }

    public function getOriginalPriceAttribute()
    {
        $goods = Goods::find($this->goods_id);
        return $goods ? $goods->shop_price : 0;

    }

    public function getGoodsCoverAttribute()
    {
        $goods = Goods::find($this->goods_id);
        return $goods ? $goods->cover : '';
    }

    public static function getIsRecArr()
    {
        return [0 => '否', 1 => '是'];
    }



}
