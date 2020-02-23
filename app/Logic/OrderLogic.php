<?php

namespace App\Logic;

use App\Models\Address;
use App\Models\Card;
use App\Models\Cart;
use App\Models\Config;
use App\Models\Coupon;
use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\OrderGoods;
use App\Models\ShippingType;
use App\Models\Store;
use App\Models\Order;
use App\Models\Rebatelog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderLogic extends Model
{

    protected $user_id;
    protected $store_id;
    protected $pay_type;
    protected $error;
    protected $cartList;
    protected $action;
    protected $order;
    protected $order_id;

    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    public function getOrderId()
    {
        return $this->order_id;

    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function setCartList($cartList)
    {
        $this->cartList = $cartList;
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setPayType($pay_type)
    {
        $this->pay_type = $pay_type;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setStoreId($store_id)
    {
        $this->store_id = $store_id;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * 获取订单 order_sn
     * @return string
     */
    public function get_order_sn()
    {
        $order_sn = null;
        // 保证不会有重复订单号存在
        while (true) {
            $order_sn = build_order_sn(); // 订单编号
            $order_sn_count = DB::table('order')->where('order_sn', $order_sn)->orWhere('master_order_sn', $order_sn)->count();
            if ($order_sn_count == 0)
                break;
        }
        return $order_sn;
    }

    /**
     * 插入订单
     * @param int $user_id 下单用户id
     * @param int $address_id 收货地址id
     * @param array $coupon_id 优惠券id
     * @param array $car_prices 各种价格
     * {
     * "shipping_price": "10.00",
     * "coupon_price": "120.00",
     * "pay_points": "87.5",
     * "goods_price": 550,
     * "order_amount": "332.50",
     * "total_amount": "420"
     * }
     * @param string $user_note 用户备注
     * @param int $pay_type 支付方式: 0=会员卡,1=支付宝,2=微信
     * @return array
     */
    public function insertOrder($address_id, $pay_type, $coupon_id, $car_prices, $user_note = [])
    {
        $address = Address::find($address_id);
        $coin2rmb = get_config_by_name('coin2rmb');//麦穗兑换数量

        //每个商家生成一个订单
        foreach ($car_prices as $k => $car_price) {
            //生成订单
            $order_sn = $this->get_order_sn(); // 获取生成订单号
            !isset($master_order_sn) && ($master_order_sn = $this->get_order_sn()); // 主订单号

            $param = [
                'order_sn' => $order_sn,
                'master_order_sn' => $master_order_sn,
                'user_id' => $this->user_id,
                'consignee' => $address['name'],
                'mobile' => $address['mobile'],
                'province_id' => $address['province_id'],
                'city_id' => $address['city_id'],
                'district_id' => $address['district_id'],
                'address' => $address['detail'],
                'goods_price' => $car_price['goods_price'],//商品总价
                'order_amount' => $car_price['order_amount'],//应付金额
                'total_amount' => $car_price['total_amount'], //订单总金额
                'shipping_price' => $car_price['shipping_price'], //配送费用
                'coupon_price' => $car_price['coupon_price'], //优惠券金额
                'user_account_money' => $car_price['pay_points'], //麦穗抵扣金额
                'user_account' => $car_price['pay_points'] * $coin2rmb,
                'created_at' => date('Y-m-d H:i:s'),
                'store_id' => $k,
                'pay_type' => $pay_type,
                'user_note' => isset($user_note[$k]) ? $user_note[$k] : '',
                'coupon_ids' => json_encode($coupon_id),
                'area' => $address['area'],
                'shipping_code' => 'ems'
            ];

            //插入订单表
            $order_id = DB::table('order')->insertGetId($param);
            // 1插入order_goods 表
            if ($this->action == 'buy_now') {
                $cartList = $this->cartList;
            } else {
                $cartList = Cart::where(function ($query) use ($k) {
                    $query->where('store_id', $k);
                    $query->where('user_id', $this->user_id);
                    $query->where('selected', 1);
                })->get();
            }


            foreach ($cartList as $ko => $vo) {
                $goods = Goods::find($vo['goods_id']);
                $orderGoodsParam = [
                    'order_id' => $order_id,
                    'goods_id' => $vo['goods_id'],
                    'goods_name' => $vo['goods_name'],
                    'goods_num' => $vo['goods_num'],
                    'goods_price' => $vo['goods_price'],
                    'spec_key' => $vo['spec_key'],
                    'spec_key_name' => $vo['spec_key_name'],
                    'store_id' => $vo['store_id'],
                    'give_integral' => $goods->give_integral, //赠送积分,
                    'give_account' => $goods->give_account, //赠送麦穗
                    'self_rebate' => $goods->self_rebate,
                    'share_rebate' => $goods->share_rebate,
                    'commission' => DB::table('category')->where('id', $goods->cat2)->value('commission'),
                    'goods_type' => $goods->type,
                ];
                DB::table('order_goods')->insert($orderGoodsParam);

            }//插入order_goods 结束
        }


        // 如果应付金额为0  可能是余额支付  + 优惠券 这里订单支付状态直接变成已支付
        if ($car_price['order_amount'] == 0) {
            update_pay_status($order_sn, 1); // 这里刚刚下的订单必须从主库里面去查
        }



        // 3扣除麦穗
        if ($car_price['pay_points'] * 100 > 0) {
            //验证用户麦穗
            $user = User::find($this->user_id);
            if ($car_price['pay_points'] * 100 > $user->account) {
                //麦穗不足
                $this->setError('麦穗不足');
                return false;
            }

            User::accountLog($this->user_id, '-' . $car_price['pay_points'] * 100, $order_sn, '购物抵扣', 1, 2);
        }

        // 4 清空购物车
//        Cart::where(function($query){
//            $query->where('user_id',$this->user_id);
//            $query->where('selected',1);
//        })->delete(); //方便测试，暂时屏蔽
        //插入order 结束

        return [
            'order_sn' => $master_order_sn,
            'pay_type' => $pay_type,
            'order_amount' => $car_price['order_amount']
        ];
    }


    /**
     * 立即购买 --> 确认订单页面
     */
    public function buyNow($goods_id, $spec_key, $goods_num)
    {
        $goods = Goods::find($goods_id);
        $store = Store::find($goods['store_id']);
        $spec_key_name = '';

        if ($spec_key) {
            $price = Goods::getSpecPrice($goods_id, $spec_key);
            $goodsSpec = GoodsSpec::getSpecValueByKey($goods_id,  GoodsSpec::getSpecKey($spec_key));

            $spec_key_name = $goodsSpec->goods_specs;
            if($goods_num - $goodsSpec->goods_stock > 0 ){
                $this->setError('此规格库存不足！');
                return false;
            }
        } else {
            if($goods_num - $goods->store_count > 0 ){
                $this->setError('商品库存不足！');
                return false;
            }
            $price = $goods->shop_price;
        }

        $totalPrice = price_format($price * $goods_num);
        $shipperPrice = $goods->shipper_fee;
        $exchangeIntegralPrice = $goods->exchange_integral_price * $goods_num;
        $total_amount = price_format($totalPrice + $shipperPrice - $exchangeIntegralPrice);
        $totalAmount = $total_amount > 0 ? $total_amount : 0;

        $cartdata = [

            0 => [
                'storeinfo' => [
                    'id' => $store->id,
                    'shop_name' => $store->shop_name,
                    'send_type' => $store->send_type,
                    'customer_service' => $store->customer_service,
                    'goods_price' => $totalPrice,
                    'shipper_price' => price_format($shipperPrice),
                    'exchange_integral_price' => price_format($exchangeIntegralPrice),
                    'goods_num' => $goods_num,
                    'total_amount' => $totalAmount
                ],

                'cartlist' => [[
                    "id" => 0,
                    "user_id" => 0,
                    "store_id" => $store->id,
                    "goods_id" => $goods_id,
                    "goods_name" => $goods->name,
                    "goods_price" => $price,
                    "goods_num" => $goods_num,
                    "spec_key" => $spec_key,
                    "spec_key_name" => $spec_key_name,
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
     * 计算订单价格
     * @param array $order_goods_list 用户选中的购物车
     * @param float $pay_points 麦穗抵扣金额
     * @param array $coupon_id 优惠券id数组
     * @param int $pay_type 支付方式: 0=余额,1=支付宝,2=微信
     * @return array [
     *      shipping_price 配送费用
     * coupon_price 优惠券金额
     * pay_points 麦穗抵扣金额
     * order_amount 订单总额 减去 积分 减去余额 减去优惠券 优惠活动
     * goods_price 总商品价格
     * ]
     *
     */
    public function calculatePrice(array $order_goods_list, $pay_points = 0, $coupon_id = [], $pay_type)
    {
        $grouped = collect($order_goods_list)->groupBy('store_id');
        $order_goods_list = $grouped->toArray();
//        dd($order_goods_list);
        $data = [];
        foreach ($order_goods_list as $store_id => $order_goods) {
            $goods_price = array_sum(array_map(function ($val) {
                return $val['price'];
            }, $order_goods));
            $coinPrice = 0;
            $shipping_price = 0;
            $coin2rmb = get_config_by_name('coin2rmb');
            foreach ($order_goods as $k => $v) {
                $goods = Goods::find($v['goods_id']);
                $coinPrice += price_format($v['price'] * $goods->exchange_integral / $coin2rmb);
                $shipping_price += $goods->shipper_fee;
            }

            //优惠券优惠金额
            $coupon_price = Coupon::whereIn('id', $coupon_id)->sum('money');
            $order_amount = price_format($goods_price - $pay_points - $coupon_price + $shipping_price);
            $order_amount = $order_amount > 0 ? $order_amount : 0;
            $data[$store_id]['shipping_price'] = price_format($shipping_price);
            $data[$store_id]['coupon_price'] = price_format($coupon_price);
            $data[$store_id]['pay_points'] = price_format($pay_points);
            $data[$store_id]['goods_price'] = price_format($goods_price);
            $data[$store_id]['order_amount'] = price_format($order_amount);
            $data[$store_id]['total_amount'] = price_format($order_amount + $pay_points);

        }

        if ($pay_type == 0) {
            //余额支付时，验证会员余额
            $user = User::find($this->user_id);
            if ($user->money * 100 - $order_amount * 100 < 0) {
                $this->setError('余额不足！请使用其他付款方式');
                return false;
            }
        }

        return $data;
    }

    /**
     * 支付成功减库存
     * @param $order
     */
    public function minus_stock($order)
    {
        $orderGoods = OrderGoods::where('order_id', $order->id)->get();
        foreach ($orderGoods as $k => $v) {
            if (!empty($v->spec_key)) {
                //有规格
                $goodsSpec = GoodsSpec::where(['goods_id' => $v->goods_id, 'spec_keys' => $v->spec_key])->first();
                $goodsSpec->decrement('goods_stock', $v->goods_num);
                $this->refresh_stock($v->goods_id);
            } else {
                $goods = Goods::find($v->goods_id);
                $goods->decrement('store_count', $v->goods_num);
            }
        }
    }

    /**
     * 刷新总库存
     */
    public function refresh_stock($goods_id)
    {
        $count = GoodsSpec::where('goods_id', $goods_id)->count();
        if (!$count) return false;

        $totalCount = GoodsSpec::where('goods_id', $goods_id)->sum('goods_stock');
        Goods::where('id', $goods_id)->update(['store_count' => $totalCount]);
    }


    /**
     * 赠送店铺积分
     */
    public function give_integral($order)
    {
        $orderGoods = OrderGoods::where('order_id', $order->id)->get();
        foreach ($orderGoods as $k => $v) {
            $goods = Goods::find($v->goods_id);

            if ($card = Card::where(['user_id' => $order->user_id, 'store_id' => $goods->store_id])->first()) {
                //有会员卡
                if ($goods->give_integral) {
                    Card::cardScoreLog($card->id, $goods->give_integral, 0, 1, $order->order_sn, '订单赠送店铺积分');
                }
            }
        }
    }

    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        DB::table('order')->where(['user_id' => $this->user_id, 'id' => $this->order->id])->update([
            'order_status' => 3,
            'user_note' => '用户取消订单'
        ]);


        //使用平台麦穗情况下
        if ($this->order->user_account) {
            User::accountLog($this->user_id, $this->order->user_account, $this->order->order_sn, '用户取消订单退回', 0, 4);
        }

        //使用优惠券
//        if ($this->order->coupon_ids) {
//            DB::table('user_coupon')->where(['user_id' => $this->user_id, 'order_id' => $this->order->id])->update([
//                'order_id' => 0,
//                'status' => 0,
//                'use_time' => ''
//            ]);
//        }
        return true;
    }

    /**
     * 订单确认收货
     */
    public function order_confirm()
    {
        $order = Order::find($this->order_id);
        if ($order['order_status'] != 1 || empty($order['pay_time']) || $order['pay_status'] != 1) {
            $this->setError('该订单不能确认收货');
            return false;
        }
        $order->order_status = 2;//已收货
        $order->confirm_time = date('Y-m-d H:i:s');
        $res = $order->save();
        if ($res !== false) {
            //下单赠送
            $this->order_give($order);

            return true;
        } else {
            $this->setError('确认收货失败');
            return false;
        }
    }

    /**
     * 下单赠送 (确认收货后)
     */
    protected function order_give($order)
    {
        //自购赚，分享赚返利
        $orderGoods = OrderGoods::where('order_id', $order->id)->get();
        foreach ($orderGoods as $k => $v) {
            $goods = Goods::find($v->goods_id);
            //只有代理才能获得分享赚，自购赚, 且不能是麦穗抵扣，抽奖商品
            if ($order->user['level'] > 0 && $goods->type != 2 && $goods->prom_type != 3) {
                if ($goods->self_rebate * 100) {
                    //自购赚
                    User::accountLog($order->user_id, $goods->self_rebate * 100, $order->order_sn, '订单赠送麦穗', 0, 1);
                }

                if ($goods->share_rebate * 100) {
                    //需要分享过后才能获得
                    User::rebateLog($order->user_id, $goods->share_rebate, $order->order_sn, '分享收益', 0, 3);
                }
            }
        }
    }


    //订单商品总数
    public function getTotalGoodsNum()
    {
        $count = DB::table('order_goods')->where('order_id', $this->order_id)->sum('goods_num');
        return $count;
    }

    /**
     * 订单发货后14天确认收货(定时任务)
     */
    public function autoConfirmOrder()
    {
        //shipping_status=1 shipping_time
        Order::chunk(100, function($orders){
            foreach ($orders as $order){
                if ($order['order_status'] != 1 || empty($order['pay_time']) || $order['pay_status'] != 1 || $order['shipping_status'] != 1) {
                    continue;
                }
                if(Carbon::parse($order->shipping_time)->addDays(14) <= Carbon::now()){
                    DB::table('order')->where('id',$order->id)->update([
                        'order_status' => 2,
                        'confirm_time' => date('Y-m-d H:i:s'),
                        'admin_note' => '订单发货超过14天，系统确认收货'
                    ]);
                    $this->order_give($order);
                }
            }
        });
    }

    public function autoCancelOrder()
    {
        Order::chunk(100, function($orders){
            foreach ($orders as $order){
                if ($order['pay_status'] > 0) {
                    continue;
                }
                if(Carbon::parse($order->created_at)->addDays(2) <= Carbon::now()){
                    DB::table('order')->where([ 'id' => $order->id])->update([
                        'order_status' => 3,
                        'admin_note' => '超过48小时未付款，系统取消订单'
                    ]);

                    //使用平台麦穗情况下
                    if ($order->user_account) {
                        User::accountLog($order->user_id, $order->user_account, $order->order_sn, '用户取消订单退回', 0, 4);
                    }
                }
            }
        });
    }


}
