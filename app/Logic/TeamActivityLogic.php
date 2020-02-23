<?php

namespace App\Logic;

use App\Models\Goods;
use App\Models\TeamActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TeamActivityLogic extends Prom{
    protected $team; //拼团模型
    protected $goods; //商品模型

    public function __construct($goods)
    {
        parent::__construct();

        $this->goods = $goods;
        $this->team = TeamActivity::find($this->goods['prom_id']);

        if($this->team){
            //每次初始化都检测活动是否失效，如果失效就恢复商品成普通商品
            if(!$this->isAble()){
                //失效
                DB::name('goods')->where("id", $this->team['goods_id'])->update(['prom_type' => 0, 'prom_id' => 0]);
                unset($this->goods);
                $this->goods = Goods::find($goods['id']);
            }
        }
    }

    //获取活动模型
    public function getPromModel(){
        return $this->team;
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
        if(empty($this->team)){
            return false;
        }
        if($this->team['status'] != 1){
            return false;
        }
        return true;
    }

    /**
     * 单独购买
     */
    public function buyNow($buyGoods){
        $buyGoods['prom_type'] = 0;
        $buyGoods['prom_id'] = 0;
        
    }




}