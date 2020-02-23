<?php

namespace App\Logic;

use App\Models\Goods;
use App\Models\FlashSale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FlashSaleLogic extends Prom{
    protected $flashSale; //限时抢购模型
    protected $goods; //商品模型

    public function __construct($goods)
    {
        parent::__construct();

        $this->goods = $goods;
        $this->flashSale = FlashSale::find($this->goods['prom_id']);

        if($this->flashSale){
            //每次初始化都检测活动是否失效，如果失效就恢复商品成普通商品
            if(!$this->isAble()){
                //失效
                Db::table('goods')->where("id", $this->flashSale['goods_id'])->update(['prom_type' => 0, 'prom_id' => 0]);
                unset($this->goods);
                $this->goods = Goods::get($goods['id']);
            }
        }
    }

    //获取活动模型
    public function getPromModel(){
        return $this->flashSale;
    }

    //获取商品详细
    public function getGoodsInfo(){
        return $this->goods;
    }

    /**
     * 活动是否失效
     * @return bool
     */
    public function IsAble(){
        if(empty($this->flashSale)){
            return false;
        }
        if($this->flashSale['status'] != 1){
            return false;
        }

        return true;
    }


}