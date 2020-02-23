<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    //轮播图
    protected $table = 'banner';
    public $timestamps = false;

    public static function getHrefTypeArr(){
        //跳转类型：0=文章, 1=商品，2=活动版块，3=店铺
        return [0 => '文章', 1 => '商品', 2 => '活动版块',3 => '店铺'];
    }

    public static function getPageTypeArr(){
        //所属页面：0=首页，1=抵扣专区，2=活动版块，3=抽奖商品列表，4=升级赚钱专区，5=逛街，6=签到页面，7 = '首页中部图片'
        return [0 => '首页顶部', 1 => '抵扣专区', 2 => '活动版块', 3 => '抽奖商品列表', 4 => '升级赚钱专区', 5 => '逛街', 6 => '签到页面',7 => '首页中部图片'];
    }


}
