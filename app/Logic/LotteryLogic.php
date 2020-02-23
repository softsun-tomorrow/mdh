<?php

namespace App\Logic;

use App\Models\Goods;
use App\Models\Lottery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LotteryLogic extends Prom{
    protected $lottery; //抽奖模型
    protected $goods; //商品模型



    public function __construct($goods)
    {
        parent::__construct();

        $this->goods = $goods;
        $this->lottery = Lottery::find($this->goods['prom_id']);

        if($this->lottery){
            //每次初始化都检测活动是否失效，如果失效就恢复商品成普通商品
            if(!$this->isAble()){
                //失效
                Db::table('goods')->where("id", $this->lottery['goods_id'])->update(['prom_type' => 0, 'prom_id' => 0]);
                unset($this->goods);
                $this->goods = Goods::get($goods['id']);
            }
        }
    }



    //获取活动模型
    public function getPromModel(){
        return $this->lottery;
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
        if(empty($this->lottery)){
            return false;
        }
        if($this->lottery['status'] != 1){
            return false;
        }

        return true;
    }

    public function setUserId($user_id)
    {
        return $this->user_id = $user_id;
    }
    



}