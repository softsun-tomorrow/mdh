<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Leesign extends Model
{
    //签到
    //类型: 0=平台签到, 1=店铺会员卡签到，2=店铺积分签到
    protected $table = 'leesign';
    public $timestamps = false;

    /**
     * 签到（非连续）
     * @param int $type 类型: 0=平台签到, 1=店铺会员卡签到，2=店铺积分签到
     */
    public static function firstSign($user_id, $type = 0, $store_id = 0)
    {
        $config = Config::getConfigValue();
        //get_config_by_name('sign_coin');
        DB::table('leesign')->insert([
            'user_id' => $user_id,
            'sign_reward' => self::getUserSign($user_id),
            'max_sign' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'type' => $type
        ]);

        //签到奖励
        self::reward($user_id, $type, $store_id, 1);
    }

    /**
     * 连续签到
     * @param int $type 类型: 0=平台签到, 1=店铺会员卡签到，2=店铺积分签到
     */
    public static function continueSign($user_id, $type, $store_id = 0)
    {
        $config = Config::getConfigValue();
        $last = DB::table('leesign')->where('user_id', $user_id)->where('type', $type)->orderBy('id', 'desc')->first();

        if ($last->max_sign == 7) {
            self::firstSign($user_id);
        } else {
            $sign_extra_reward = $config['base.sign_extra_reward'] * $last->max_sign;
            DB::table('leesign')->insert([
                'user_id' => $user_id,
                'sign_reward' => self::getUserSign($user_id),
                'sign_extra_reward' => $sign_extra_reward,
                'max_sign' => $last->max_sign + 1,
                'created_at' => date('Y-m-d H:i:s'),
                'type' => $type
            ]);
            //签到奖励
            self::reward($user_id, $type, $store_id, 0);
        }

    }

    /**
     * 签到奖励
     */
    public static function reward($user_id, $type, $store_id, $is_first = 1)
    {
        if ($type == 1) {
            //店铺会员卡签到
            $cardId = Card::getCardId($user_id, $store_id);
            $money = self::getSignRewardMoney($user_id, $type, $store_id, $is_first);
            Card::cardAccountLog($cardId, $money, 0, 0, '', '用户会员卡签到');
        } elseif ($type == 2) {
            //店铺积分签到
            $cardId = Card::getCardId($user_id, $store_id);

            $money = self::getSignRewardMoney($user_id, $type, $store_id, $is_first);
            Card::cardScoreLog($cardId, $money, 0, 0, '', '用户积分签到');
        } else {
            //平台签到

            $money = self::getSignRewardMoney($user_id, $type, $store_id, $is_first);
            User::accountLog($user_id, $money, '', '用户麦穗签到', 0, 0);
        }
    }

    /**
     * 获取签到奖励金额
     */
    public static function getSignRewardMoney($user_id, $type, $store_id, $is_first = 0)
    {
        $config = Config::getConfigValue();
        $store = Store::find($store_id);
        $last = DB::table('leesign')->where('user_id', $user_id)->where('type', $type)->orderBy('id', 'desc')->first();

        if ($type == 1) {
            //店铺会员卡签到
            if ($is_first) {
                $money = $store['sign_card_reward'];
            } else {
                $sign_extra_reward = $store['sign_card_extra_reward'] * $last->max_sign;
                $money = ($store['sign_card_reward'] * 100 + $sign_extra_reward * 100) / 100;
            }
        } elseif ($type == 2) {
            //店铺积分签到
            if ($is_first) {
                $money = $store['sign_score_reward'];
            } else {
                $sign_extra_reward = $store['sign_score_extra_reward'] * $last->max_sign;
                $money = ($store['sign_score_reward'] * 100 + $sign_extra_reward * 100) / 100;
            }
        } else {
            //平台签到
            if ($is_first) {
                $money = self::getUserSign($user_id);
            } else {
                $last = DB::table('leesign')->where('user_id', $user_id)->orderBy('id', 'desc')->first();
                $sign_extra_reward = $config['base.sign_extra_reward'] * $last->max_sign;
                $money = (self::getUserSign($user_id) * 100 + $sign_extra_reward * 100) / 100;
            }
        }

        return $money;
    }

    /**
     * 显示7天签到奖励金额
     * @param $type
     * @param $store_id
     */
    public static function getRewardMoney($type, $store_id,$user_id)
    {
        $config = Config::getConfigValue();
        $store = Store::find($store_id);
        if ($type == 1) {
            //店铺会员卡签到
            $baseMoney = $store['sign_card_reward'];
            $extraMoney = $store['sign_card_extra_reward'];
        } elseif ($type == 2) {
            //店铺积分签到
            $baseMoney = $store['sign_score_reward'];
            $extraMoney = $store['sign_score_extra_reward'];

        } else {
            //平台签到
            $baseMoney = self::getUserSign($user_id);
            $extraMoney = $config['base.sign_extra_reward'];

        }
        return [
            'baseMoney' => $baseMoney,
            'extraMoney' => $extraMoney
        ];
    }

    private static function getUserSign($user_id){
        $config = Config::getConfigValue();
        $user = User::find($user_id);
        if($user->level){
            return $config['base.vip_user_sign'];
        }else{
            return $config['base.normal_user_sign'];
        }
    }

    /**
     * 获取从今天开始7天的日期数据
     * @param int $isContinue 是否连续签到
     */
    public static function getWeek($user_id, $type, $isContinue = 0, $store_id = 0)
    {
        $yd = Carbon::yesterday()->toDateString();
        if ($isContinue) {
            //连续签到
            $yestodayDate = DB::table('leesign')
                ->where('user_id', $user_id)
                ->whereDate('created_at', $yd)
                ->where('type', $type)
                ->first();

            $yestodayMaxSign = $yestodayDate? $yestodayDate->max_sign : 0;
            $start = Carbon::parse('-' . $yestodayMaxSign . ' days')->timestamp;
//            echo date('Y-m-d',$start);exit;
            $week = getSevenDays($start);
        } else {
            $today = Carbon::today()->timestamp;
            $week = getSevenDays($today);
        }

        $data = [];
        $rewardMoney = self::getRewardMoney($type, $store_id,$user_id);
        foreach ($week as $k => $v) {
            $count = DB::table('leesign')->where('user_id', $user_id)
                ->where('type', $type)
                ->whereDate('created_at', $v)
                ->count();
            $dt = Carbon::parse($v);

            $data[$k]['date'] = $dt->month . '.' . $dt->day;
            $data[$k]['is_sign'] = $count;
            $data[$k]['money'] = ($rewardMoney['baseMoney'] * 100 + $rewardMoney['extraMoney'] * $k * 100) / 100;
        }

        return $data;
    }
}
