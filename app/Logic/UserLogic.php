<?php

namespace App\Logic;

use App\Events\AfterUpgrade;
use App\Models\Level;
use App\Models\Like;
use App\Models\Order;
use App\Models\Tyfon;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserLogic extends Model
{
    //用户逻辑
    protected $user;
    protected $user_id;
    protected $parent; //上级
    protected $order; //代理版块订单

    public function test()
    {

        throw new \Exception('ceshi');
        return 22;

    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }


    /**
     * 关注量
     * @return mixed
     */
    public function getFollowCount()
    {
        $count = DB::table('follow')->where(function ($query) {
            $query->where('commentable_type', 'user');
            $query->where('user_id', $this->user->id);
        })->count();

        return $count;
    }

    /**
     * 粉丝量
     */
    public function getFansCount()
    {
        $count = DB::table('follow')->where(function ($query) {
            $query->where('commentable_type', 'user');
            $query->where('commentable_id', $this->user->id);
        })->count();
        return $count;
    }

    /**
     * 获赞量(心情的获赞量)
     */
//    public function getTyfonLikeCount()
//    {
//        $count = DB::table('like')->where(function ($query) {
//            $query->where('commentable_type', 'tyfon');
//            $query->where('commentable_id', $this->user->id);
//        })->count();
//        return $count;
//    }

    /**
     * 我的心情数量
     */
    public function getTyfonCount()
    {
        return DB::table('tyfon')->where(function ($query) {
            $query->where('commentable_type', 'user');
            $query->where('commentable_id', $this->user->id);
        })->count();
    }

    /**
     * 我的收藏数量
     */
    public function getCollectCount()
    {
        return DB::table('collect')->where(function ($query) {
            $query->where('commentable_type', 'tyfon');
            $query->where('user_id', $this->user->id);
        })->count();
    }

    /**
     * 我的点赞数量
     */
    public function getLikeCount($commentable_type = 'tyfon',$commentable_id = 0)
    {
        return DB::table('like')->where(function ($query) use ($commentable_id, $commentable_type) {
            $query->where('commentable_type', $commentable_type);
            if($commentable_id) $query->where('commentable_id', $commentable_id);
            $query->where('user_id', $this->user_id);
        })->count();
    }

    //我的心情获赞数量
    public function getTyfonLikeCount(){
        $tyfonIds = Tyfon::where('commentable_type', 'user')->where('commentable_id', $this->user_id)->pluck('id');
        $count = Like::where(function($query) use ($tyfonIds){
            $query->where('commentable_type', 'tyfon');
            $query->whereIn('commentable_id', $tyfonIds);
        })->count();
        return $count;
    }

    /**
     * 是否关注
     */
    public function isFollow($commentable_id, $commentable_type)
    {
        $isCollect = DB::table('follow')->where(function ($query) use ($commentable_id, $commentable_type) {
            $query->where('user_id', $this->user_id);
            $query->where('commentable_id', $commentable_id);
            $query->where('commentable_type', $commentable_type);
        })->count();

        return $isCollect ? 1 : 0;
    }

    /**
     * 是否点赞
     */
    public function isLike($commentable_id, $commentable_type)
    {
        $isLike = DB::table('like')->where(function ($query) use ($commentable_id, $commentable_type) {
            $query->where('user_id', $this->user_id);
            $query->where('commentable_id', $commentable_id);
            $query->where('commentable_type', $commentable_type);
        })->count();
        return $isLike ? 1 : 0;
    }

    /**
     * 是否收藏
     */
    public function isCollect($commentable_id, $commentable_type)
    {
        $isLike = DB::table('collect')->where(function ($query) use ($commentable_id, $commentable_type) {
            $query->where('user_id', $this->user_id);
            $query->where('commentable_id', $commentable_id);
            $query->where('commentable_type', $commentable_type);
        })->count();
        return $isLike ? 1 : 0;
    }

    public function upgrade()
    {
        //升级为一级代理
        DB::table('users')->where('id', $this->user_id)->update([
            'level' => 1
        ]);
        Log::info('购买代理升级商品，升级为一级代理');

        //推送消息
        event(new AfterUpgrade($this->user));

        $upgradeConfig = get_upgrade_config();
        User::accountLog($this->user_id, $upgradeConfig['first_getcoin'],$this->order->order_sn, '升级一级代理获得麦穗',  0, 8);

        //是否有上级
        if ($this->user['first_leader']) {
//            $this->upgradeParent($this->user['first_leader'], $this->order);
            $parent = User::find($this->user['first_leader']);
            $this->setParent($parent);

            if ($parent->level > 0 && $parent->level < 4) {
                //可以升级，给上级升级
                $this->upgradeParent();
            }

            //上级返利
            if ($parent->level > 0) {
                $level = Level::find($parent->level); //上级级别信息
                $this->inviteRebateMoney($level); //上级返利金额
                $this->inviteRebateCoin($level); //上级返利麦穗
            }

            //上上级返利
            $grand = User::find($this->user['second_leader']);
            if ($grand && $grand->level > 1) {
                $this->secondLeaderRebate(); //上上级返利金额
                $this->secondLeaderTeamTotalAmount(); //上上级团队营业额分红
            }

        }
    }

    /**
     * 给上级升级
     */
    public function upgradeParent()
    {
        $sonCount = $this->upgradeSonCount(); //直属下级代理
        $trainCount = $this->upgradeTrainCount(); //培养的直属下级代理
        $teamCount = $this->upgradeTamCount(); //团队总代理数
        //模拟升级
//        $sonCount = 10;
//        $trainCount = 2;
//        $teamCount = 50;
        //


        $toLevel = $this->parent['level']+1;
        $level = Level::find($toLevel);

        if($level['child_count']){
            $condition['childCount'] =  (int)$sonCount >= $level['child_count'] ? 1 : 0;
        }
        if($level['train_count']) {
            $condition['train_count'] = (int)$trainCount >= $level['train_count']? 1 : 0;
        }
        if($level['team_count']) {
            $condition['team_count'] = (int)$teamCount >= $level['team_count']? 1 : 0;
        }

        foreach ($condition as $k => $v){ //是否满足升级条件
            if(!$v){
                return;
            }
        }

        //升级
        DB::table('users')->where('id',$this->parent->id)->update([
            'level' => $toLevel
        ]);

        //推送消息
        event(new AfterUpgrade(User::find($this->parent->id)));
//        dd($sonCount,$trainCount,$teamCount,$condition);
    }

    /**
     * 升级需要直接邀请的代理人数(升级条件)
     * @param User $user 上级
     * @param int $level 升级到的级别
     * @return int
     */
    protected function upgradeSonCount()
    {
        $sonCount = DB::table('users')->where(function ($query) {
            $query->where('first_leader', $this->parent->id);
            $query->where('level', '>', 0);
        })->count();
        return $sonCount;
    }


    /**
     * 升级需要培养的直属代理人数(升级条件)
     * @param User $this ->parent 上级
     * @param int $level 升级到的级别
     * @return int
     */
    protected function upgradeTrainCount()
    {
        $count = DB::table('users')->where(function ($query) {
            $query->where('first_leader', $this->parent->id);
            $query->where('level', $this->parent->level);
        })->count();
        return $count;
    }

    /**
     * 升级需要团队总代理达到数量(升级条件)
     * @param User $this ->parent 上级
     * @param int $level 升级到的级别
     * @return int
     */
    protected function upgradeTamCount()
    {
        $count = DB::table('users')->where(function ($query) {
            $query->where('level', '>', 0);
            $query->where(function ($query) {
                $query->orWhere('first_leader', $this->parent->id);
                $query->orWhere('second_leader', $this->parent->id);
                $query->orWhere('third_leader', $this->parent->id);
            });

        })->count();
        return $count;
    }

    /**
     * 每直接邀请一个一级代理获得金额（上级返利金额）
     */
    protected function inviteRebateMoney($level)
    {
        if ($level['rebate_money'] * 100) {
            User::rebateLog($this->parent->id, $level['rebate_money'], $this->order['order_sn'], '销售佣金', 0, 0);
        }
    }

    /**
     * 每直接邀请一个一级代理获得麦穗（上级返利麦穗）
     *
     */
    protected function inviteRebateCoin($level)
    {
        if ($level['rebate_coin'] * 100) {
            User::accountLog($this->parent->id, $level['rebate_coin'], $this->order['order_sn'], '邀请代理返利', 0, 9);
        }
    }

    /**
     * 所直推的代理每直推一个一级代理获得金额（上上级返利金额）
     *
     */
    protected function secondLeaderRebate()
    {
        if ($this->user['second_leader']) {
            $grand = User::find($this->user['second_leader']);
            if ($grand->level && $grand->level > 1) {
                $level = Level::find($grand->level);
                if ($level['rebate_money'] * 100) {
                    User::rebateLog($this->user->second_leader, $level['rebate_money'], $this->order['order_sn'], '管理津贴', 0, 1);
                }
            }
        }
    }

    /**
     * 所直推的代理每直推一个一级代理获得团队营业额比例 (上上级团队营业额分红)
     */
    protected function secondLeaderTeamTotalAmount()
    {
        if ($this->user['second_leader']) {
            //销售额 直推代理的订单额
            $childIds = User::where(function($query){
                $query->where('first_leader',$this->user['second_leader']);
            })->pluck('id')->toArray();

            $amount = Order::where(function($query) use ($childIds){
                $query->whereIn('user_id',$childIds);
                $query->where('pay_status',1);
                $query->whereHas('order_goods',function($query){
                    $query->whereNotIn('goods_type',[1,2]);
                    $query->where('prom_type','!=',3);
                });
            })->sum('order_amount');

            $grand = User::find($this->user['second_leader']);
            if ($grand->level && $grand->level > 1) {
                $level = Level::find($grand->level);
                if ($level['team_rate'] * 100) {
                    $money = round($amount * $level['team_rate']/100,2);
                    User::rebateLog($this->user->second_leader, $money, $this->order['order_sn'], '团队营业额分红', 0, 5);
                }
            }

        }

    }

    //待结算收入的钱过了结算期后，自动流入余额(定时任务)
    public function autoSettle()
    {
        DB::table('user_rebate_log')->chunk(100, function ($logs) {
            foreach ($logs as $k => $log) {
                if(Carbon::parse($log->change_time)->addDays(7) <= Carbon::now()){

                    DB::table('user_rebate_log')->where('id',$log->id)->update(['is_settle' => 1]);
                    //减少收益
                    User::rebateLog($log->user_id,'-'.$log->change_money,$log->order_sn,'收益结算',1,4);
                    //流入到余额
                    User::moneyLog($log->user_id,$log->change_money,$log->order_sn,'收益结算',0,5);
                }
            }
        });
    }


    /**
     * 代理业绩
     */
    public function getAgentArchive() : array
    {
        //销售额 直推代理的订单额
        $childIds = User::where(function($query){
            $query->where('first_leader',$this->user_id);
        })->pluck('id')->toArray();

        $totalAmount = Order::where(function($query) use ($childIds){
            $query->whereIn('user_id',$childIds);
            $query->where('pay_status',1);
            $query->whereHas('order_goods',function($query){
                $query->whereNotIn('goods_type',[1,2]);
                $query->where('prom_type','!=',3);
            });
        })->sum('order_amount');


        $lastMouthAmount = Order::where(function($query) use ($childIds){
            $query->whereIn('user_id',$childIds);
            $query->where('pay_status',1);
            $query->where('created_at','>',Carbon::parse('-1 months')->toDateTimeString());
            $query->whereHas('order_goods',function($query){
                $query->whereNotIn('goods_type',[1,2]);
                $query->where('prom_type','!=',3);
            });
        })->sum('order_amount');

        return [
            'totalAmount' => $totalAmount,
            'lastMouthAmount' => $lastMouthAmount
        ];
    }


}