<?php

namespace App\Logic;

use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\TeamFollow;
use App\Models\TeamFound;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamOrderLogic extends Model
{
    protected $team;// 拼团模型
    protected $order;//订单模型
    protected $goods;//商品模型
    protected $orderGoods;//订单商品模型.
    protected $store;//商家模型
    protected $goodsBuyNum;//购买的商品数量
    protected $user_id = 0;//user_id
    protected $user; //用户模型
    protected $teamFound;//开团模型
    protected $error;
    protected $specKey;
    protected $address;

    /**
     * 设置收货地址
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * 设置用户ID
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
        $this->user = User::find($user_id);
    }

    /**
     * 设置商品规格
     */
    public function setSpecKey($specKey)
    {
        $this->specKey = GoodsSpec::getSpecKey($specKey);
    }

    /**
     * 设置拼团模型
     * @param $team
     */
    public function setTeam($team)
    {
        $this->team = $team;
    }

    /**
     * 设置商品模型
     * @param $goods
     */
    public function setGoods($goods)
    {
        $this->goods = $goods;
    }


    /**
     * 设置订单模型
     * @param $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * 设置订单商品模型
     * @param $orderGoods
     */
    public function setOrderGoods($orderGoods)
    {
        $this->orderGoods = $orderGoods;
    }

    /**
     * 设置店铺模型
     * @param $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * 设置购买的商品数量
     * @param $goodsBuyNum
     */
    public function setGoodsBuyNum($goodsBuyNum)
    {
        $this->goodsBuyNum = $goodsBuyNum;
    }

    /**
     * 设置开团模型
     * @param $teamFound
     */
    public function setTeamFound($teamFound)
    {
        $this->teamFound = $teamFound;
    }

    /**
     * 返回订单模型
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * 返回订单商品模型
     * @return mixed
     */
    public function getOrderGoods()
    {
        return $this->orderGoods;
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 拼团下单
     */
    public function add()
    {
        if (empty($this->team) || $this->team['status'] != 1) {
            $this->setError('该商品拼团活动不存在或者已下架');
            return false;
        }

        if (empty($this->goods) || $this->goods['is_on_sale'] != 1) {
            $this->setError('该商品拼团活动不存在或者已下架');
            return false;
        }

        if ($this->goodsBuyNum <= 0) {
            $this->setError('至少购买一份');
            return false;
        }
        if ($this->team['buy_limit'] != 0 && $this->goodsBuyNum > $this->team['buy_limit']) {

            $this->setError('购买数已超过该活动单次购买限制数(' . $this->team['buy_limit'] . ')');
            return false;
        }

        //拼团价
        $teamPrice = $this->getTeamPrice();

        $order_sn = build_order_sn();
        $order_amount = round($teamPrice * $this->goodsBuyNum + $this->team->goods['shipper_fee'], 2);
        $orderData = [
            'consignee' => $this->address ? $this->address['name'] : '',
            'mobile' => $this->address ? $this->address['mobile'] : '',
            'province_id' => $this->address ? $this->address['province_id'] : 0,
            'city_id' => $this->address ? $this->address['city_id'] : 0,
            'district_id' => $this->address ? $this->address['district_id'] : 0,
            'address' => $this->address ? $this->address['address'] : '',
            'user_id' => $this->user_id,
            'order_sn' => $order_sn,
            'goods_price' => $teamPrice * $this->goodsBuyNum,
            'order_prom_id' => $this->team['id'],
            'order_prom_type' => 2,
            'created_at' => date('Y-m-d H:i:s'),
            'store_id' => $this->team['store_id'],
            'order_amount' => $order_amount,
            'total_amount' => round($teamPrice * $this->goodsBuyNum + $this->team->goods['shipper_fee'], 2),
            'shipping_code' => 'ems',

        ];

        $order_id = DB::table('order')->insertGetId($orderData);
        if ($order_id) {
            //插入order_goods
            $order = Order::find($order_id);

//            Log::info('spec_key:' . $this->specKey);
            $goodsSpec = GoodsSpec::getGoodsSpecsBySpecKeys($this->specKey);

//            Log::info('拼团下单参数: ' , ['spec_key' => $this->specKey, 'goods_spec' => $goodsSpec]);
            $orderGoodsParam = [
                'order_id' => $order_id,
                'goods_id' => $this->goods['id'],
                'goods_name' => $this->goods['name'],
                'goods_num' => $this->goodsBuyNum,
                'goods_price' => $order['goods_price'],
                'spec_key' => $this->specKey,
                'spec_key_name' => $goodsSpec ? $goodsSpec : '',
                'store_id' => $order['store_id'],
                'give_integral' => $this->goods->give_integral, //赠送店铺积分,
                'give_account' => $this->goods->give_account, //赠送麦穗
                'self_rebate' => $this->goods->self_rebate,
                'share_rebate' => $this->goods->share_rebate,
                'commission' => DB::table('category')->where('id', $this->goods->cat2)->value('commission'),
                'prom_type' => 2,
                'prom_id' => $this->team['id'],
            ];
            $orderGoodsId = DB::table('order_goods')->insertGetId($orderGoodsParam);

            $orderGoods = OrderGoods::find($orderGoodsId);
            if ($this->teamFound) {
                /**团员拼团s**/
                $team_follow_data = [
                    'follow_user_id' => $this->user['id'],
                    'follow_user_nickname' => $this->user['name'],
                    'follow_user_head_pic' => $this->user['avatar'],
                    'follow_time' => date('Y-m-d H:i:s'),
                    'order_id' => $order['id'],
                    'found_id' => $this->teamFound['id'],
                    'found_user_id' => $this->teamFound['user_id'],
                    'team_id' => $this->team['id'],
                ];
                DB::table('team_follow')->insert($team_follow_data);
                /***团员拼团e***/
            } else {
                /***团长开团s***/
                $team_found_data = [
                    'found_time' => date('Y-m-d H:i:s'),
                    'found_end_time' => Carbon::now()->addSeconds(intval($this->team['time_limit'])),
                    'user_id' => $this->user_id,
                    'team_id' => $this->team['id'],
                    'nickname' => $this->user['name'],
                    'head_pic' => $this->user['avatar'],
                    'order_id' => $order['id'],
                    'need' => $this->team['needer'],
                    'price' => $teamPrice,
                    'goods_price' => $this->goods['shop_price'],
                    'store_id' => $this->team['store_id']
                ];
                DB::table('team_found')->insert($team_found_data);
                /***团长开团e***/
            }

            if ($orderGoodsId) {
                return ['order_id' => $order->id, 'order_sn' => $order_sn, 'order_amount' => $order_amount];
            } else {
                $this->setError('拼团商品下单失败');
                return false;
            }
        } else {
            $this->setError('拼团商品下单失败');
            return false;
        }

    }

    private function getTeamPrice(){
        //拼团价
        if($this->specKey){
            $teamPrice = DB::table('goods_spec')->where(function($query){

//                $key = strKey2json($this->specKey);
//                dd($key);
                $query->where('goods_id', $this->goods['id']);
                $query->where('spec_keys', $this->specKey);
            })->value('team_price');
        }else{
            $teamPrice = $this->team['price'];
        }
        return $teamPrice;
    }

    /**
     * 计算订单价
     * @return mixed
     */
    private function getOrderAmount()
    {
        $order_amount = price_format($this->order->goods_price - $this->order->user_account_money - $this->order->coupon_price + $this->order->shipping_price);
        $order_amount = $order_amount > 0 ? $order_amount : 0;
    }

    private function getTotalAmount()
    {
        $total_amount = round(($this->order->goods_price + $this->order->shipping_price), 2);
        return $total_amount;
    }

    /**
     * 拼团支付后操作
     * @param $order
     * @throws \think\Exception
     */
    public function doOrderPayAfter($order)
    {
        $teamFound = TeamFound::where(['order_id' => $order['id']])->first();
        //团长的单
        if ($teamFound) {
            $teamFound->found_time = Carbon::now();
            $teamFound->found_end_time = Carbon::now()->addSeconds(intval($this->team['time_limit']));
            $teamFound->status = 1;
            $teamFound->save();
        } else {
            //团员的单
            $teamFollow = TeamFollow::where(['order_id' => $order['id']])->first();
            if ($teamFollow) {
                $teamFollow->status = 1;
                $teamFollow->save();
                //更新团长的单
                $teamFollow->team_found->join = $teamFollow['team_found']['join'] + 1;//参团人数+1
                //如果参团人数满足成团条件
                if ($teamFollow->team_found->join >= $teamFollow->team_found->need) {
                    $teamFollow->team_found->status = 2;//团长成团成功
                    //更新团员成团成功
                    DB::table('team_follow')->where(['found_id' => $teamFollow->team_found->id, 'status' => 1])->update(['status' => 2]);
                    //更新拼团活动
                    DB::table('team_activity')->where('id', $order->order_prom_id)->increment('sales_sum');
                }
                $teamFollow->team_found->save();
            }
        }


    }


    /**
     * 获取确认订单信息
     */
    public function getConfirmOrder()
    {
        $goods = $this->goods;
        $store = $this->store;
        $teamPrice = $this->getTeamPrice();
        Log::info('teamPrice:'. $teamPrice);
        $goods_num = $this->goodsBuyNum;
        $totalPrice = $teamPrice * $this->goodsBuyNum;
        $shipperPrice = $goods->shipper_fee;

        $total_amount = price_format($totalPrice + $shipperPrice);
        $totalAmount = $total_amount > 0 ? $total_amount : 0;
        $goodsSpec = GoodsSpec::getGoodsSpecsBySpecKeys($this->specKey);
        $cartdata = [
            0 => [
                'storeinfo' => [
                    'id' => $store->id,
                    'shop_name' => $store->shop_name,
                    'send_type' => $store->send_type,
                    'customer_service' => $store->customer_service,
                    'goods_price' => $totalPrice,
                    'shipper_price' => price_format($shipperPrice),
                    'exchange_integral_price' => 0,
                    'goods_num' => $goods_num,
                    'total_amount' => $totalAmount
                ],

                'cartlist' => [[
                    "id" => 0,
                    "user_id" => 0,
                    "store_id" => $store->id,
                    "goods_id" => $goods->id,
                    "goods_name" => $goods->name,
                    "goods_price" => $teamPrice,
                    "goods_num" => $goods_num,
                    "spec_key" => $this->specKey,
                    "spec_key_name" => $goodsSpec ? $goodsSpec : '',
                    "selected" => 1,
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                    "goods_cover" => $goods->cover,
                    "price" => $totalPrice,
                ]]
            ]
        ];
        return ['total_amount' => $totalAmount, 'cartdata' => $cartdata];
    }

    /**
     * 拼团有效期结束，未拼成的单退款
     */
    public function autoCheck()
    {
        $list = TeamFound::where(function ($query) {
            $query->where('status', 1);//团长已支付
            $query->where('found_end_time', '<', date('Y-m-d H:i:s'));
        })->chunk(20, function ($teamFounds) {
            foreach ($teamFounds as $teamFound) {
                $this->refund($teamFound);
            }
        });
    }

    /**
     * 拼团退款
     * @param TeamFound $teamFound
     */
    protected function refund(TeamFound $teamFound)
    {
        //退款给团长
        $order = Order::find($teamFound->order_id);
        $order->pay_status = 3; //已退款
        $order->save();
        User::moneyLog($order->user_id,$order->order_amount,$order->order_sn,'拼团失败退回',0,2);

        //修改拼团状态
        $teamFound->status = 3;
        $teamFound->save();

        //退款给团员
        foreach ($teamFound->team_follow as $teamFollow) {
            if ($teamFollow->status == 1) {
                $followOrder = Order::find($teamFollow->order_id);
                $followOrder->pay_status = 3; //已退款
                $followOrder->save();
                User::moneyLog($followOrder->user_id, $followOrder->order_amount, $followOrder->order_sn, '拼团失败退回', 0, 2);
                //修改参团状态
                $teamFollow->status = 3;
                $teamFollow->save();
            }
        }
    }


}