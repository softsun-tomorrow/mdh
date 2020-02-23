<?php

namespace App\Logic;

use App\Models\FlashSale;
use App\Models\GoodsSpec;

use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlashSaleOrderLogic extends Model
{
    //秒杀订单逻辑
    protected $flash_sale;// 秒杀模型
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
     * 设置秒杀模型
     * @param $flash_sale
     */
    public function setFlashSale($flash_sale)
    {
        $this->flash_sale = $flash_sale;
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

    public function check()
    {
        if (empty($this->flash_sale) || $this->flash_sale['status'] != 1) {
            $this->setError('该商品秒杀活动不存在或者已下架');
            return false;
        }

        if (empty($this->goods) || $this->goods['is_on_sale'] != 1) {
            $this->setError('该商品秒杀活动不存在或者已下架');
            return false;
        }



        if($this->flash_sale['order_num'] > $this->flash_sale['goods_num']){
            $this->setError('活动商品已抢完');
            return false;
        }

        $now = Carbon::now();
        $start = intval($this->flash_sale['scene']);
        $end = intval($this->flash_sale['scene']) == 22 ? 0 : intval($this->flash_sale['scene'])+2;

        if($now->hour < $start || $now->hour > $end){
            $this->setError('不在有效抢购时间段内, 场次为'. $this->flash_sale['scene'] .'点场');
            return false;
        }

        return true;
    }

    /**
     * 秒杀下单
     */
    public function add(){
        if (empty($this->flash_sale) || $this->flash_sale['status'] != 1) {
            $this->setError('该商品秒杀活动不存在或者已下架');
            return false;
        }

        if (empty($this->goods) || $this->goods['is_on_sale'] != 1) {
            $this->setError('该商品秒杀活动不存在或者已下架');
            return false;
        }

        if($this->flash_sale['buy_limit'] < $this->goodsBuyNum) {
            $this->setError('购买数量超出每人限购量' . $this->flash_sale['buy_limit'] .'个');
            return false;
        }

        if($this->flash_sale['order_num'] > $this->flash_sale['goods_num']){
            $this->setError('活动商品已抢完');
            return false;
        }

        $now = Carbon::now();
        $start = intval($this->flash_sale['scene']);
        $end = intval($this->flash_sale['scene']) == 22 ? 0 : intval($this->flash_sale['scene'])+2;

        if($now->hour < $start || $now->hour > $end){
            $this->setError('不在有效抢购时间段内, 场次为'. $this->flash_sale['scene'] .'点场');
            return false;
        }

        $order_sn = build_order_sn();
        $order_amount = round($this->flash_sale['price'] * $this->goodsBuyNum + $this->flash_sale->goods['shipper_fee'],2);
        $orderData = [
            'consignee' => $this->address ? $this->address['name'] : '',
            'mobile' => $this->address ? $this->address['mobile'] : '',
            'province_id' => $this->address ? $this->address['province_id'] : 0,
            'city_id' => $this->address ? $this->address['city_id'] : 0,
            'district_id' => $this->address ? $this->address['district_id'] : 0,
            'address' => $this->address ? $this->address['address'] : '',
            'user_id' => $this->user_id,
            'order_sn' => $order_sn,
            'goods_price' => $this->flash_sale['price'] * $this->goodsBuyNum,
            'order_prom_id' => $this->flash_sale['id'],
            'order_prom_type' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'store_id' => $this->flash_sale['store_id'],
            'order_amount' => $order_amount,
            'total_amount' => round($this->flash_sale['price'] * $this->goodsBuyNum + $this->flash_sale->goods['shipper_fee'],2),
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
                'prom_type' => 1,
                'prom_id' => $this->flash_sale['id'],
            ];
            $orderGoodsId = DB::table('order_goods')->insertGetId($orderGoodsParam);
            $orderGoods = OrderGoods::find($orderGoodsId);

            if($orderGoodsId){
                return ['order_id' => $order->id, 'order_sn' => $order_sn, 'order_amount' => $order_amount];
            }else{
                $this->setError('秒杀商品下单失败');
                return false;
            }
        }else{
            $this->setError('秒杀商品下单失败');
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
     * 秒杀支付后操作
     * @param $order
     * @throws \think\Exception
     */
    public function doOrderPayAfter(Order $order){
        $orderGoods = OrderGoods::where('order_id',$order->id)->first();
        $flashSale = FlashSale::find($order->order_prom_id);
        $flashSale->buy_num += 1;
        $flashSale->order_num += $orderGoods->goods_num;
        if($flashSale['order_num'] == $flashSale['goods_num']) $flashSale->status = 4;
        $flashSale->save();
    }

    /**
     * 获取确认订单信息
     */
    public function getConfirmOrder()
    {
        $goods = $this->goods;
        $store = $this->store;

        $goods_num = $this->goodsBuyNum;
        $totalPrice = $this->flash_sale['price'] * $this->goodsBuyNum;
        $shipperPrice = $goods->shipper_fee;

        $total_amount = price_format($totalPrice + $shipperPrice);
        $totalAmount = $total_amount > 0 ? $total_amount : 0;
        $goodsSpec = GoodsSpec::getGoodsSpecsBySpecKeys($this->specKey);

        $cartdata = [
             0 =>   [
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
                        "goods_price" => $this->flash_sale['price'],
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
