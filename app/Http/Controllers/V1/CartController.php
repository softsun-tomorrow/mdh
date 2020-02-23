<?php

namespace App\Http\Controllers\V1;

use App\Logic\CartLogic;
use App\Logic\OrderLogic;
use App\Models\Cart;
use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    //购物车

    public function __construct()
    {
        $this->middleware('refresh', ['except' => []]);
    }

    /**
     * 添加购物车
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $param['goods_id'] = $request->input('goods_id');
        $param['goods_num'] = $request->input('goods_num', 1);
        $param['spec_key'] = $request->input('spec_key', '');

        $cartLogic = new CartLogic();
        $cartLogic->setUserId(auth('api')->user()->id);
        $res  = $cartLogic->addCart($param['goods_id'], $param['goods_num'], $param['spec_key']);
        if ($res) {
            return $this->success($res);
        } else {
            return $this->error($cartLogic->getError());
        }
    }


    public function index()
    {
        $list = Cart::where(['user_id' => auth('api')->user()->id])
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('store_id');

        $arr = [];
        foreach ($list as $k => $v) {
            $store = Store::find($k);
            $arr[] = [
                'storeinfo' => [
                    'id' => $store->id,
                    'shop_name' => $store->shop_name
                ],
                'cartlist' => $v
            ];
        }

        return $this->success($arr);
    }

    /**
     * 修改数量
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeNum(Request $request)
    {
        $id = $request->input('id');
        $num = $request->input('goods_num');
        $cart = Cart::findOrfail($id);
        $cart->goods_num = $num;
        $res = $cart->save();
        if ($res) {
            return $this->success();
        } else {
            return $this->error('修改失败');
        }
    }

    /**
     * 设置购物车选中状态
     */
    public function changeSelect(Request $request)
    {
        $ids = $request->input('ids');
        $selected = $request->input('selected');

        $idsArr = explode(',',$ids);
        $res = Cart::whereIn('id',$idsArr)->update(['selected' => $selected]);
        if ($res) {
            return $this->success();
        } else {
            return $this->error('修改失败');
        }

    }

    /**
     * 购物车删除
     */
    public function del(Request $request)
    {
        $ids = $request->input('ids');
        $idArr = explode(',',$ids);

        $res = Cart::whereIn('id', $idArr)->delete();
        if ($res) {
            return $this->success();
        } else {
            return $this->error('删除失败');
        }

    }

    /**
     * 计算购物车选中商品总价格
     */
    public function computeCartPrice(Request $request)
    {
        $ids = $request->input('ids');

        $totalPrice = 0;
        if ($ids) {
            $idArr = explode(',', $ids);
            foreach ($idArr as $k => $v) {
                $cart = Cart::find($v);
                $totalPrice += $cart->price;
            }
        }

        return $this->success(price_format($totalPrice));

    }



    /**
     * 购物车下单
     * @param int $store_id 店铺id
     * @param int $address_id 收货地址id
     * @param int $pay_type 支付方式:0=余额,1=支付宝,2=微信
     * @param string $note 用户备注
     * @param float $pay_points 麦穗抵扣金额
     *
     * @return  array
     */
    public function addOrder(Request $request)
    {
        $param = $request->all();
        if($param['user_note']??'') {
            $user_note = json_decode($param['user_note'],true);
        }else{
            $user_note = '';
        }
        $user_id = auth('api')->user()->id;
        $coupon_id = [];
        $orderLogic = new OrderLogic();
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);


        $order_goods = [];
        if($param['action'] == 'buy_now'){
            $goods = Goods::find($param['goods_id']);
            $cartLogic->setStoreId($goods->store_id);
            $cartLogic->setGoodsModel($param['goods_id']);
            if($param['spec_key']??''){
                $cartLogic->setGoodsSpecModel($param['goods_id'],$param['spec_key']);
            }
            $cartLogic->setGoodsBuyNum($param['goods_num']);
            $result = $cartLogic->buyNow();
            if(!$result) return $this->error($cartLogic->getError());
            $order_goods[0] = $result;
            $orderLogic->setCartList($order_goods);
        }else{
            $userCartList = $cartLogic->getUserCart(1);//用户选中的购物车
            if(!$userCartList) return $this->error($cartLogic->getError());
            $order_goods = $userCartList->toArray();
        }
//        return $this->success($order_goods);
        //计算各种价格
        $orderLogic->setUserId($user_id);
        $orderLogic->setPayType($param['pay_type']);

        $car_price = $orderLogic->calculatePrice( $order_goods ,$param['pay_points']??0,$coupon_id,$param['pay_type']);
        if(!$car_price) return $this->error($orderLogic->getError());
//        return $this->success($car_price);
        $orderLogic->setAction($param['action']);

        $res = $orderLogic->insertOrder($param['address_id'],$param['pay_type'],$coupon_id,$car_price,$user_note);
        if (!$res) {
            return $this->error($orderLogic->getError());
        } else {
            return $this->success($res);
        }
    }

    /**
     * 清空购物车
     * @return \Illuminate\Http\JsonResponse
     */
    public function delAllCart(){
        $res = Cart::where('user_id',auth('api')->user()->id);
        if($res){
            return $this->success();
        }else{
            return $this->error('清空失败');
        }
    }
}
