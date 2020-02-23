<?php

namespace App\Logic;

use App\Models\Cart;
use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CartLogic extends Model
{
    //

    protected $user_id;
    protected $store_id;
    protected $error;
    protected $goods;//商品模型
    protected $goodsSpec;//商品规格模型
    protected $goodsBuyNum;//购买的商品数量

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setStoreId($store_id)
    {
        $this->store_id = $store_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setGoodsModel($goods_id)
    {
        $this->goods = Goods::find($goods_id);
    }

    public function setGoodsSpecModel($goods_id, $spec_key)
    {
        $spec_key = str2json($spec_key);
        $this->goodsSpec = GoodsSpec::where(function ($query) use ($goods_id, $spec_key) {
            $query->where('goods_id', $goods_id);
            $query->where('spec_keys', $spec_key);
        })->first();
    }

    public function setGoodsBuyNum($goodsBuyNum)
    {
        $this->goodsBuyNum = $goodsBuyNum;
    }


    public function getCartByCartIds($cart_ids)
    {
        $cartIdArr = explode(',', $cart_ids);

        $list = Cart::whereIn('id', $cartIdArr)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('store_id');

        $arr = [];
        $totalOrderAcount = 0; //总的应付金额

        //循环每个店铺的购物车
        foreach ($list as $k => $v) {

            $store = Store::find($k);
            $goodsPrice = 0;//店铺商品总额
            $shipperPrice = 0;//店铺物流费用
            $exchangeIntegralPrice = 0; //店铺麦穗兑换金额
            $goodsNum = 0;

            foreach ($v as $k0 => $vo) {
                $goodsPrice += $vo['price'];
                $goods = Goods::withTrashed()->find($vo['goods_id']);
                $shipperPrice += $goods->shipper_fee;
                $exchangeIntegralPrice += $goods->exchange_integral_price*$vo['goods_num'];
                $goodsNum += $vo['goods_num'];
            }

            //店铺应付金额
            $totalAmount = price_format($goodsPrice + $shipperPrice - $exchangeIntegralPrice);
            $totalOrderAcount += $totalAmount;
            $arr[] = [
                'storeinfo' => [
                    'id' => $store->id,
                    'send_type' => $store->send_type,
                    'customer_service' => $store->customer_service,
                    'shop_name' => $store->shop_name,
                    'goods_price' => price_format($goodsPrice),
                    'shipper_price' => price_format($shipperPrice),
                    'exchange_integral_price' => price_format($exchangeIntegralPrice),
                    'goods_num' => $goodsNum,
                    'total_amount' => $totalAmount
                ],
                'cartlist' => $v
            ];
        }

        return ['totalOrderAmount' => price_format($totalOrderAcount),'data' => $arr];
    }

    public function getUserSelectedTotalPrice()
    {
        $cartdata = Cart::where(['user_id' => $this->user_id, 'selected' => 1])->select('goods_num', 'goods_price')->get();
        $totalprice = 0;
        foreach ($cartdata as $k => $v) {
            $totalprice += intval($v['goods_num']) * $v['goods_price'];
        }
        return $totalprice;
    }

    public function addCart($goods_id, $goods_num, $spec_key = '')
    {
        $param['goods_id'] = $goods_id;
        $param['goods_num'] = $goods_num;
        $param['spec_key'] = $spec_key;
        $param['user_id'] = $this->user_id;
        $goods = Goods::find($param['goods_id']);
//        if (!$goods) return ['code' => 1, 'msg' => '商品不存在','data' => ''];
        $param['store_id'] = $goods['store_id'];
        $param['goods_name'] = $goods['name'];
        if($goods->prom_type || $goods->type == 1){
            $this->setError('活动商品或代理版块商品不能加入购物车');
            return false;
        }
        if (isset($param['spec_key']) && $param['spec_key']) {
            //有规格
            $param['spec_key'] = GoodsSpec::getSpecKey($param['spec_key']);
            $goods_specs = GoodsSpec::getSpecValueByKey($param['goods_id'], $param['spec_key']);

            if($goods_num - $goods_specs->goods_stock > 0 ){
                $this->setError('此规格库存不足！');
                return false;
            }
            $param['spec_key_name'] = $goods_specs['goods_specs'];
            $goods_price = $goods_specs['goods_price'];

            $condition = ['user_id' => auth('api')->user()->id, 'goods_id' => $param['goods_id'], 'spec_key' => $param['spec_key']];

        } else {
            if($goods_num - $goods->store_count > 0 ){
                $this->setError('商品库存不足！');
                return false;
            }

            $goods_price = $goods['shop_price'];
            $condition = ['user_id' => auth('api')->user()->id, 'goods_id' => $param['goods_id']];
            unset($param['spec_key']);
        }

        $param['selected'] = 1;
        $param['goods_price'] = $goods_price;

        $exists = Cart::where($condition)->first();
        if ($exists) {
            //购物车中已存在此单品
            $param['goods_num'] = intval($exists['goods_num']) + $param['goods_num'];
            $exists->goods_num = $param['goods_num'];
            $exists->save();
            return $exists->id;
        } else {
            $res = Cart::create($param);
            return $res->id;
        }
    }

    /**
     * 获取用户某一店铺购物车列表
     * @param int $selected 1=选中，0=全部
     */
    public function getStoreCartList($selected = 0)
    {
        $list = Cart::where(function ($query) use ($selected) {
            $query->where('user_id', $this->user_id);
            $query->where('store_id', $this->store_id);
            $query->where('selected', $selected);
        })->get();
        if ($list->isEmpty()) {
            $this->setError('您的购物车没有选中商品');
            return false;
        }
        return $list;
    }

    /**
     * 获取用户购物车
     * @param int $selected 1=选中，0=全部
     * @return bool
     */
    public function getUserCart($selected = 0)
    {
        $list = Cart::where(function ($query) use ($selected) {
            $query->where('user_id', $this->user_id);
            $query->where('selected', $selected);
        })->get();
        if ($list->isEmpty()) {
            $this->setError('您的购物车没有选中商品');
            return false;
        }
        return $list;
    }

    /**
     * 立即购买
     */
    public function buyNow()
    {
        $buy_goods = [
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'goods_id' => $this->goods['id'],
            'goods_name' => $this->goods['name'],
            'goods_price' => $this->goods['shop_price'],
            'goods_num' => $this->goodsBuyNum,
            'spec_key' => '',
            'spec_key_name' => '',
            'selected' => 1,
            'goods_cover' => $this->goods['cover'],
            'price' => price_format($this->goods['shop_price'] * $this->goodsBuyNum)
        ];

        if ($this->goodsSpec) {
            //有规格
            $buy_goods['goods_price'] = $this->goodsSpec['goods_price'];
            $buy_goods['spec_key'] = $this->goodsSpec['spec_keys'];
            $buy_goods['spec_key_name'] = $this->goodsSpec['goods_specs'];
            $buy_goods['price'] = price_format($this->goodsSpec['goods_price'] * $this->goodsBuyNum);
            $store_count = $this->goodsSpec['goods_stock'];
        } else {
            $store_count = $this->goods['store_count'];
        }

        //判断库存
        if ($this->goodsBuyNum - $store_count > 0) {
            $this->setError('商品库存不足');
            return false;
        }



        return $buy_goods;
    }


}

