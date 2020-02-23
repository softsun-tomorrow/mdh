<?php

namespace App\Logic;

use App\Models\GoodsSpec;
use App\Models\LotteryFollow;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LotteryOrderLogic extends Model
{
    //抽奖订单逻辑
    protected $lottery;// 抽奖模型
    protected $order;//订单模型
    protected $goods;//商品模型
    protected $orderGoods;//订单商品模型.
    protected $store;//商家模型
    protected $user_id = 0;//user_id
    protected $user; //用户模型
    protected $error;
    protected $specKey;
    protected $address;
    protected $goodsBuyNum = 1;

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
    public function setSpecKey($specKey){
        $this->specKey = GoodsSpec::getSpecKey($specKey);
    }

    /**
     * 设置抽奖模型
     * @param $lottery
     */
    public function setLottery($lottery)
    {
        $this->lottery = $lottery;
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

    protected function setError($error){
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 抽奖下单
     */
    public function add(){
        if (empty($this->lottery) || $this->lottery['status'] != 1) {
            $this->setError('该商品抽奖活动不存在或者已下架');
            return false;
        }

        if (empty($this->goods) || $this->goods['is_on_sale'] != 1) {
            $this->setError('该商品抽奖活动不存在或者已下架');
            return false;
        }

        $lotteryChance = DB::table('lottery_chance')->where([
            'user_id' => $this->user_id,
            'lottery_id' => $this->lottery->id
        ])->first();
        if($lotteryChance && $lotteryChance->use_num >=  $lotteryChance->lottery_num){
            $this->setError('抽奖次数已用完，请先分享增加抽奖机会');
            return false;
        }

        $order_sn = build_order_sn();
        $order_amount = round($this->lottery['price'] * $this->goodsBuyNum + $this->lottery->goods['shipper_fee'],2);
        $orderData = [
            'consignee' => $this->address ? $this->address['name'] : '',
            'mobile' => $this->address ? $this->address['mobile'] : '',
            'province_id' => $this->address ? $this->address['province_id'] : 0,
            'city_id' => $this->address ? $this->address['city_id'] : 0,
            'district_id' => $this->address ? $this->address['district_id'] : 0,
            'address' => $this->address ? $this->address['address'] : '',
            'user_id' => $this->user_id,
            'order_sn' => $order_sn,
            'goods_price' => $this->lottery['price'] * $this->goodsBuyNum,
            'order_prom_id' => $this->lottery['id'],
            'order_prom_type' => 3,
            'created_at' => date('Y-m-d H:i:s'),
            'store_id' => $this->lottery['store_id'],
            'order_amount' => $order_amount,
            'total_amount' => round($this->lottery['price'] * $this->goodsBuyNum + $this->lottery->goods['shipper_fee'],2),
            'shipping_code' => 'ems',

        ];

        $order_id = DB::table('order')->insertGetId($orderData);
        if($order_id){
            //插入order_goods
            $order = Order::find($order_id);
            $goodsSpec = GoodsSpec::getGoodsSpecsBySpecKeys($this->specKey);

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
                'commission' => DB::table('category')->where('id',$this->goods->cat2)->value('commission'),
                'prom_type' => 3,
                'prom_id' => $this->lottery['id'],
                'goods_type' => 4, //抽奖活动

            ];
            $orderGoodsId = DB::table('order_goods')->insertGetId($orderGoodsParam);
            $orderGoods = OrderGoods::find($orderGoodsId);

            //写入抽奖记录
            DB::table('lottery_follow')->insert([
                'user_id' => $this->user_id,
                'lottery_id' => $this->lottery->id,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 0,
                'order_id' => $order->id,
                'sku' => $goodsSpec,
                'spec_key' => $this->specKey,
                'goods_id' => $this->goods['id'],
                'address_id' => $this->address['id'],
            ]);

            //记录参与抽奖次数
            if(!$lotteryChance){
                DB::table('lottery_chance')->insert([
                    'lottery_id' => $this->lottery->id,
                    'user_id' => $this->user_id,
                    'lottery_num' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }


            if($orderGoodsId){
                return ['order_id' => $order->id, 'order_sn' => $order_sn, 'order_amount' => $order_amount];
            }else{
                $this->setError('抽奖商品下单失败');
                return false;
            }
        }else{
            $this->setError('抽奖商品下单失败');
            return false;
        }
    }

    /**
     * 计算订单价
     * @return mixed
     */
    private function getOrderAmount(){
        $order_amount = price_format($this->order->goods_price - $this->order->user_account_money - $this->order->coupon_price + $this->order->shipping_price);
        $order_amount = $order_amount > 0 ? $order_amount : 0;
    }

    private function getTotalAmount(){
        $total_amount = round(($this->order->goods_price + $this->order->shipping_price),2);
        return $total_amount;
    }

    /**
     * 抽奖支付后操作
     * @param $order
     * @throws \think\Exception
     */
    public function doOrderPayAfter(Order $order){
        $lotteryFollow = LotteryFollow::where('order_id',$order->id)->first();
        $lotteryFollow->status = 1;
        $lotteryFollow->order_id = $order->id;
        //更新抽奖记录
        $lotteryFollow->save();
        //更新抽奖次数
        DB::table('lottery_chance')->where([
            'user_id' => $order->user_id,
            'lottery_id' => $lotteryFollow->lottery->id
        ])->increment('use_num');
        //更新抽奖活动
        $lotteryFollow->lottery->join_num = $lotteryFollow['lottery']['join_num']+1;
        //如果参与抽奖人数满足开奖条件
        if($lotteryFollow['lottery']['join_num'] >= $lotteryFollow['lottery']['needer']){
            $lotteryFollow->lottery->status = 4; //已开奖
            //开始抽奖
            $this->startLottery();
        }
        $lotteryFollow->lottery->save();

    }

    /**
     * 抽奖
     */
    public function startLottery()
    {
        $win = LotteryFollow::where([
            'lottery_id' => $this->lottery->id,
            'status' => 1
        ])->inRandomOrder()->first();

        //中奖
        $win->status = 2;
        $win->lottery_time = date('Y-m-d H:i:s');
        $win->save();

        //修改其他参与者记录
        DB::table('lottery_follow')->where([
            'lottery_id' => $this->lottery->id,
            'status' => 1
        ])->where(function($query) use ($win){
            $query->where('lottery_id',$this->lottery->id);
            $query->where('status',1);
            $query->where('id','!=',$win->id);
        })->update(['status' => 3]);
    }

    /**
     * 获取确认订单信息
     */
    public function getConfirmOrder()
    {
        $goods = $this->goods;
        $store = $this->store;

        $goods_num = $this->goodsBuyNum;
        $totalPrice = $this->lottery['price'] * $this->goodsBuyNum;
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
                    "goods_price" => $this->lottery['price'],
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

}
