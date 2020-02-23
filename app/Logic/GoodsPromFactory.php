<?php

namespace App\Logic;
use App\Models\Lottery;
use Illuminate\Database\Eloquent\Model;

class GoodsPromFactory
{
    /**
     * @param $goods|商品实例
     *
     * @return FlashSaleLogic|TeamActivityLogic|LotteryLogic
     */
    public function makeModule($goods)
    {
        //活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
        switch ($goods['prom_type']) {
            case 1:
                return new FlashSaleLogic($goods);
            case 2:
                return new TeamActivityLogic($goods);
            case 3:
                return new LotteryLogic($goods);

        }
    }

    /**
     * 检测是否符合商品活动工厂类的使用
     * @param $promType |活动类型
     * @return bool
     */
    public function checkPromType($promType)
    {
        if (in_array($promType, array_values([1, 2, 3]))) {
            return true;
        } else {
            return false;
        }
    }

}
