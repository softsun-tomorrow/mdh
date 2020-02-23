<?php

namespace App\Http\Controllers\V1;

use App\Jobs\CloseOrder;
use App\Logic\CartLogic;
use App\Logic\CouponLogic;
use App\Logic\FlashSaleLogic;
use App\Logic\FlashSaleOrderLogic;
use App\Logic\LotteryLogic;
use App\Logic\LotteryOrderLogic;
use App\Logic\OrderLogic;
use App\Logic\TeamActivityLogic;
use App\Logic\TeamOrderLogic;
use App\Models\Address;
use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\ReturnGoods;
use App\Models\ShippingType;
use App\Models\Store;
use App\Models\TeamFound;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Xu42\KuaiDiNiao\KuaiDiNiao;

class OrderController extends Controller
{
    //订单

    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['queryEms']]);
    }

    public function autoCloseOrder()
    {
        $order = Order::find(1);
        $this->dispatch(new CloseOrder($order, config('app.order_ttl')));
    }

    /**
     * 确认订单页面
     */
    public function confirmOrder(Request $request)
    {
        $type = $request->input('type', 0);//类型:0=购物车下单，1=限时抢购，2=发起拼团，3=抽奖，9=立即购买,，
        if ($type == 9) {
            //立即购买
            $param['goods_id'] = $request->input('goods_id');
            $param['goods_num'] = $request->input('goods_num', 1);
            $param['spec_key'] = $request->input('spec_key', '');
            $orderLogic = new OrderLogic();
            $result = $orderLogic->buyNow($param['goods_id'], $param['spec_key'], $param['goods_num']);
            $totalPrice = $result['total_amount'];
            $data = $result['cartdata'];

        } elseif ($type == 1) {
            //限时抢购
            $param['goods_id'] = $request->input('goods_id');
            $param['goods_num'] = $request->input('goods_num', 1);
            $param['spec_key'] = $request->input('spec_key', '');
            $goods = Goods::find($param['goods_id']);
            if ($goods->prom_type != 1) return $this->error('该商品抢购活动不存在或者已下架');

            $store = Store::find($goods->store_id);
            $flashSaleLogic = new FlashSaleLogic($goods);
            $flashSale = $flashSaleLogic->getPromModel();
            $flashSaleOrderLogic = new FlashSaleOrderLogic();
            $flashSaleOrderLogic->setGoods($goods);
            $flashSaleOrderLogic->setStore($store);
            $flashSaleOrderLogic->setGoodsBuyNum($param['goods_num']);
            $flashSaleOrderLogic->setSpecKey($param['spec_key']);
            $flashSaleOrderLogic->setFlashSale($flashSale);
            $result = $flashSaleOrderLogic->getConfirmOrder();//获取确认订单信息
            $totalPrice = $result['total_amount'];
            $data = $result['cartdata'];

        } elseif ($type == 2) {
            //发起拼团
            $param['goods_id'] = $request->input('goods_id');
            $param['goods_num'] = $request->input('goods_num', 1);
            $param['spec_key'] = $request->input('spec_key', '');
            $goods = Goods::find($param['goods_id']);
            if ($goods->prom_type != 2) return $this->error('该商品拼团活动不存在或者已下架');

            $store = Store::find($goods->store_id);
            $teamLogic = new TeamActivityLogic($goods);
            $team = $teamLogic->getPromModel();
            $teamOrderLogic = new TeamOrderLogic();
            $teamOrderLogic->setGoods($goods);
            $teamOrderLogic->setStore($store);
            $teamOrderLogic->setGoodsBuyNum($param['goods_num']);
            $teamOrderLogic->setSpecKey($param['spec_key']);
            $teamOrderLogic->setTeam($team);
            $result = $teamOrderLogic->getConfirmOrder();//获取确认订单信息
            $totalPrice = $result['total_amount'];
            $data = $result['cartdata'];

        } elseif ($type == 3) {
            //抽奖
            $param['goods_id'] = $request->input('goods_id');
            $param['goods_num'] = $request->input('goods_num', 1);
            $param['spec_key'] = $request->input('spec_key', '');
            $goods = Goods::find($param['goods_id']);
            if ($goods->prom_type != 3) return $this->error('该商品抽奖活动不存在或者已下架');

            $store = Store::find($goods->store_id);
            $lotteryLogic = new LotteryLogic($goods);
            $lottery = $lotteryLogic->getPromModel();
            $LotteryOrderLogic = new LotteryOrderLogic();
            $LotteryOrderLogic->setGoods($goods);
            $LotteryOrderLogic->setStore($store);

            $LotteryOrderLogic->setSpecKey($param['spec_key']);
            $LotteryOrderLogic->setLottery($lottery);
            $result = $LotteryOrderLogic->getConfirmOrder();//获取确认订单信息
            $totalPrice = $result['total_amount'];
            $data = $result['cartdata'];

        } else {
            //购物车下单
            $cart_ids = $request->input('cart_ids');//购物车ids
            $store_id = $request->input('store_id'); //店铺id
//        return $this->error('m',$cart_ids);
            $cartLogic = new CartLogic();
            $cartLogic->setUserId(auth('api')->user()->id);
            $cartLogic->setStoreId($store_id);

            $cartdata = $cartLogic->getCartByCartIds($cart_ids);
//            $totalPrice = $cartLogic->getUserSelectedTotalPrice();
            $totalPrice = $cartdata['totalOrderAmount'];
            $data = $cartdata['data'];

        }

        return $this->success(['total_amount' => $totalPrice, 'cartdata' => $data]);
    }

    /**
     * 获取店铺收货方式
     */
    public function getStoreShippingType(Request $request)
    {
        $address_id = $request->input('address_id');
        $store_id = $request->input('store_id');
        $address = Address::find($address_id);
        $store = Store::find($store_id);

        $list = ShippingType::where(function ($query) use ($store_id) {
            $query->where('store_id', $store_id);
            $query->where('status', 1);
        })->get();

        $distance = getdistance($address['lng'], $address['lat'], $store->lng, $store->lat);
        if ($distance - $store->same_city_range > 0) {
            //超出同城配送范围
            $filtered = $list->reject(function ($value, $key) {
                return $value->shipping_code == 'same_city';
            });
            $list = array_values($filtered->toArray());
        }

        return $this->success($list);
    }


    /**
     * 订单列表
     */
    public function index(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $scene = $request->input('scene', 0);
        $list = Order::with([
            'order_goods' => function ($query) {
                $query->select('id', 'order_id', 'goods_id', 'goods_name', 'goods_num', 'goods_price', 'spec_key_name', 'store_id', 'is_comment');

            },
            'store' => function ($query) {
                $query->select('id', 'shop_name', 'logo');
            },
            'order_goods.goods' => function ($query) {
                $query->select('id', 'cover', 'shipper_fee');
            }
        ])
            ->where(function ($query) use ($scene) {
                $query->where('user_id', auth('api')->user()->id);
                $query->where('deleted', 0);

                //1=待付款，2=待发货， 3=待收货，4=待评价，5=已取消，6=已完成, 7=待分享
                switch ($scene) {
                    case 1: //待付款
                        $query->where('pay_status', 0)->where('order_status', 0);
                        break;
                    case 2: //待发货
                        $query->whereIn('order_status', [0, 1])->where('shipping_status', 0)->where('pay_status', 1);
                        break;
                    case 3: //待收货
                        $query->where('order_status', 1)->where('shipping_status', 1);
                        break;
                    case 4: //待评价
                        $query->where('order_status', 2)->where('shipping_status', 1);
                        break;
                    case 5: //已取消
                        $query->where('order_status', 3);
                        break;
                    case 6: //已完成
                        $query->where('order_status', 4);
                        break;
                    case 7: //待分享 (开团成功，未完成的团的订单)
                        $query->where('order_prom_type', 2);
                        $query->whereIn('order_status', [0, 1]);
                        $query->where('shipping_status', 0);
                        $query->where('pay_status', 1);

                        $query->whereHas('team_found', function ($query) {
                            $query->where(function ($query) {
                                $query->where('status', 1);
                            });
                        });
                        break;
                    default: //全部订单
                }
            })
            ->orderBy('created_at', 'desc')
            ->offset($offset ?? 0)
            ->limit($limit ?? 10)
            ->select('id', 'order_sn', 'master_order_sn', 'user_id', 'order_status', 'shipping_status', 'pay_status', 'order_amount', 'created_at', 'store_id', 'shipping_price', 'pay_type')
            ->get();

        foreach ($list as $k => $v) {
            $list[$k]['total_goods_num'] = $v->total_goods_num;

            foreach ($list[$k]['order_goods'] as $ko => $vo){
                $isRefund = DB::table('return_goods')->where('order_goods_id',$vo->id)->count();
                //是否申请退款
                $list[$k]['order_goods'][$ko]['is_refund'] = $isRefund ? 1 : 0;
            }
        }

        return $this->success($list);
    }

    /**
     * 取消订单
     */
    public function cancel(Request $request)
    {
        $id = $request->input('id');
        $order = Order::find($id);
        if ($order->pay_status > 0) return $this->error('只有未支付状态下的订单才能取消');
        $orderLogic = new OrderLogic();
        $orderLogic->setOrder($order);
        $orderLogic->setUserId(auth('api')->user()->id);
        $res = $orderLogic->cancelOrder();
        if ($res) {
            return $this->success();
        } else {
            return $this->error('取消失败');
        }
    }

    /**
     * 会员卡支付时，可用的会员卡优惠券
     */
    public function getStoreCardCoupon(Request $request)
    {
        $store_id = $request->input('store_id');
        $goodsPrice = $request->input('goods_price');
        $pay_type = $request->input('pay_type'); //pay_type : 支付方式:0=会员卡,1=支付宝,2=微信
        if ($pay_type > 0) return $this->error('只有会员卡支付时，才能使用会员卡优惠券');

        //店铺优惠券列表
        $couponLogic = new CouponLogic();
        $couponLogic->setStoreId($store_id);
        $couponLogic->setUserId(auth('api')->user()->id);
        $store_coupon = $couponLogic->getCoupon(2, $goodsPrice);
        return $this->success($store_coupon);
    }

    /**
     * 申请售后 (退款至用户平台麦穗)
     * @param int $order_goods_id 订单商品id
     * @param int $type 退款类型：0仅退款 1退货退款 2换货
     * @param string $reason 退款原因
     * @param string $describe 退款说明
     * @parma string $imgs 凭证图片
     * @param int $is_receive 申请售后时是否已收货 0未收货1已收货
     */
    public function return_goods(Request $request)
    {
        $param = $request->all();
        $order_goods = OrderGoods::find($param['order_goods_id']);
        $order = DB::table('order')->where('id', $order_goods->order_id)->first();

        //商品是否支持退款
        if ($order_goods->goods['prom_type'] == 3) {
            return $this->error('抽奖商品不支持退款退货');
        }

        if ($order_goods->goods['type'] == 1) {
            return $this->error('升级赚钱商品不支持退款退货');
        }

        if (ReturnGoods::where('order_goods_id', $param['order_goods_id'])->count()) return $this->error('请勿重复申请售后');
        //是否提交过申请
        $return_goods = DB::table('return_goods')->where('order_goods_id', $order_goods)->first();
        if ($return_goods) return $this->error('请勿重复提交售后申请');

        //是否发货
        if ($order->shipping_time) {
            if (!$order_goods->goods['is_return']) return $this->error('该商品不支持退货');

            //是否已过7天退货时间(发货时间算)
            $shipping_time = new Carbon($order->shipping_time);
            $now = Carbon::now();
            $difference = $shipping_time->diffInDays($now);
            if (!empty($order->shipping_time) && $difference > 7) return $this->error('您已超过7天无理由退款期限');
        }

        //该商品占订单金额比例
        $rate = round($order_goods->goods_price * $order_goods->goods_num / $order->goods_price, 2);
        //退款金额
        $refund_money = round($rate * $order->order_amount, 2);
        //退还麦穗数量
        $refund_coin = floor($rate * $order->user_account);

        //确认收货后，小于7天，才能申请售后
        $res = DB::table('return_goods')->insert([
            'order_goods_id' => $order_goods->id,
            'order_id' => $order_goods->order_id,
            'order_sn' => $order->order_sn,
            'goods_id' => $order_goods->goods_id,
            'goods_num' => $order_goods->goods_num,
            'reason' => $param['reason'],
            'describe' => $param['describe'],
            'imgs' => $param['imgs'] ?? '',
            'user_id' => auth('api')->user()->id,
            'store_id' => $order->store_id,
            'spec_key' => $order_goods->spec_key,
            'consignee' => $order->consignee,
            'mobile' => $order->mobile,
            'refund_money' => $refund_money, //退款金额
            'refund_integral' => $refund_coin, //退还麦穗
            'type' => $param['type'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($res) {
            //退收益
            DB::table('user_rebate_log')->where(function ($query) use ($order) {
                $query->where('order_sn', $order->order_sn);
                $query->where('type', 0);
            })->update([
                'is_settle' => 2 //已失效
            ]);
            return $this->success();
        } else {
            return $this->error('提交失败');
        }
    }

    /**
     * 申请退款页面信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnGoodsInfo(Request $request)
    {
        $order_goods_id = $request->input('order_goods_id');
        $order_goods = OrderGoods::find($order_goods_id);
        return $this->success([
            'type' => ReturnGoods::typeArr(),
            'is_receive' => ReturnGoods::isReceiveArr(),
            'reason' => ReturnGoods::reasonArr(),
            'order_goods' => $order_goods
        ]);
    }

    /**
     * 我的退款列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnGoodsList(Request $request)
    {
        //status: -2用户取消-1不同意0待审核1通过2已发货3已收货4换货完成5退款完成6申诉仲裁
        $param = $request->all();
        $list = ReturnGoods::with([
            'order_goods' => function ($query) {
                $query->select('id', 'goods_id', 'goods_name');
            },
            'store' => function ($query) {
                $query->select('id', 'logo', 'shop_name', 'contacts_name', 'contacts_mobile', 'address');
            }
        ])
            ->where(function ($query) use ($param) {
                $query->where('user_id', auth('api')->user()->id);
                if ($param['scene'] ?? 0) { // 1=待处理，2=已退款
                    switch ($param['scene']) {
                        case 1:
                            $query->whereIn('status', [0, 1]);
                            break;
                        case 2:
                            $query->where('status', 5);
                            break;
                        default;
                    }
                }
            })
            ->orderBy('id', 'desc')
            ->offset($param['offset'] ?? 0)
            ->limit($param['limit'] ?? 10)
            ->get();

        return $this->success($list);
    }

    /**
     * 查询物流
     * @parm int $id 订单id
     * @param Request $request
     */
    public function queryEms(Request $request)
    {
        $orderId = $request->input('id');
        $order = Order::find($orderId);

//        $info = KuaiDiNiao::follow('YTO', '805161538463990041', 'E201712170000001234'); //物理跟踪
//        dd($info);

        try {
            $info = KuaiDiNiao::track($order->shipper_code, $order->logistic_code, $order->order_sn); //即时查询
            if (!$info['State']) return $this->error($info['Reason']);
            return $this->success($info);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 用户删除订单
     */
    public function del(Request $request)
    {
        $id = $request->input('id');
        $res = Order::where('id', $id)->update([
            'deleted' => 1
        ]);
        if ($res) {
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }


    //用户确认收货
    public function orderConfirm(Request $request)
    {
        $id = $request->input('id');
        $orderLogic = new OrderLogic();
        $orderLogic->setUserId(auth('api')->user()->id);
        $orderLogic->setOrderId($id);
        $res = $orderLogic->order_confirm();
        if (!$res) {
            return $this->error($orderLogic->getError());
        } else {
            return $this->success();
        }

    }

    /**
     * 上门取货获取取货码
     */
    public function goShop(Request $request)
    {
        $order_id = $request->input('id');
        $order = Order::find($order_id);
        $store = Store::find($order->store_id);
        return $this->success(['pick_code' => $order->pick_code, 'store_address' => $store->address]);
    }


    /**
     * 余额支付
     */
    public function userPay(Request $request)
    {
        //支付方式为余额支付时
        $order_sn = $request->input('order_sn');
        $total_amount = $request->input('total_amount');
        $pay_pwd = $request->input('pay_pwd');
        if (empty(auth('api')->user()->payment_password)) return $this->error('请先设置支付密码');
        if (!Hash::check($pay_pwd, auth('api')->user()->payment_password)) return $this->error('支付密码不正确');

        //余额支付时，验证会员余额
        $user = User::find(auth('api')->user()->id);
        if ($user->money * 100 - $total_amount * 100 < 0) {
            return $this->error('账户余额不足，请更换支付方式');
        }

        User::moneyLog(auth('api')->user()->id, $total_amount * -1, $order_sn, '购物支付', 1, 0);
        update_pay_status($order_sn, 2);

        return $this->success('支付成功');
    }


}
