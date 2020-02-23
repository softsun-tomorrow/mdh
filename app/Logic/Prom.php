<?php

namespace App\Logic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 活动抽象类
 * Class Prom
 * @package App\Logic
 */
abstract class Prom extends Model
{
    abstract protected function getPromModel(); //获取活动模型
    abstract protected function IsAble(); //活动是否失效
    abstract protected function getGoodsInfo(); //获取商品详细


}