<?php

namespace App\Logic;

use App\Models\Goods;
use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreLogic extends Model
{
    protected $store;
    protected $store_id;

    public function setStoreId($storeId)
    {
        $this->store_id = intval($storeId);
    }

    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * 获取本店推荐商品
     * @param int $type 1=本店推荐，2=本店商品，3=本店活动，4=本店上新
     */
    public function getStoreRecGoods($type = 1, $keywords = '', $offset = 0, $limit = 10, $store_cat2 = 0)
    {
//        DB::connection()->enableQueryLog();  // 开启QueryLog
        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($type, $keywords,$store_cat2) {
            $query->where(['is_on_sale' => 1, 'status' => 1]);
            $query->where('type',0);
            $query->whereIn('prom_type', [0, 1, 2]);
            $query->where('store_id', $this->store_id);
            if ($keywords) $query->where('name', 'like', '%' . $keywords . '%');
            if($store_cat2) $query->where('store_cat2', $store_cat2);

            switch ($type) {
                case 1:
                    $query->where('is_store_rec', 1);
                    break;
                case 2:
                    $query->where('prom_type', 0);
                    break;
                case 3:
                    $query->whereIn('prom_type', [1, 2]);
                    break;
                case 4:
                    $oneMonth = date('Y-m-d H:i:s', strtotime('-1 week'));
                    $query->whereDate('created_at', '>', $oneMonth);
                    break;
                default:

            }

        })
            ->select('id', 'store_id', 'name', 'type', 'cover', 'share_rebate', 'self_rebate', 'shop_price', 'sale_nums', 'collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id', 'cover_width', 'cover_height', 'shipper_fee', 'exchange_integral', 'cover_height', 'cover_width')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $list;
    }



    /**
     * 自动给商家结算
     */
    public function autoTransfer()
    {
        //$sql = "select order_id,confirm_time from __PREFIX__order where store_id = $store_id and order_status in(2,4) and confirm_time <  $time and order_statis_id = 0 order by order_id ASC";
        $list = Order::where(function ($query) {
            $query->where('store_id', $this->store_id);
            $query->whereIn('order_status', [2, 4]);
            $query->where('confirm_time', '<', Carbon::parse('-14 days'));
            $query->where('order_statis_id',0);
        })->get();
        Log::info('商家结算订单',$list->toArray());
        if (!$list->count()) return false; //没有数据直接跳过

        $data = array(
            'start_date' => $list[0]['confirm_time'], // 结算开始时间
            'end_date' => Carbon::parse('-14 days'), //结算截止时间
            'create_date' => date('Y-m-d H:i:s'), // 记录创建时间
            'store_id' => $this->store_id, // 店铺id
            'shipping_totals' => 0,

            'commis_totals' => 0,
            'result_totals' => 0,
            'order_prom_amount' => 0,
            'coupon_price' => 0,
            'return_totals' => 0,
            'order_totals' => 0,


        );

        foreach ($list as $k => $order) {
            //如果有售后申请未完成，则不结算
            $returnGoodsCount = DB::table('return_goods')->where(function ($query) use ($order) {
                $query->where('order_id', $order->id);
                $query->whereNotIn('status', [-2, 5]);
            })->count();
            if ($returnGoodsCount) continue;
            $order_settlement = order_settlement($order['id']); // 调用全局结算方法
            Log::info('调用全局结算方法',$order_settlement);
            $data['order_totals'] += $order_settlement['goods_amount'];// 订单商品金额
            $data['shipping_totals'] += $order_settlement['shipping_price'];// 运费
            $data['commis_totals'] += $order_settlement['settlement'];// 平台抽成
            $data['result_totals'] += $order_settlement['store_settlement'];// 本期应结
            $data['order_prom_amount'] += $order_settlement['order_prom_amount'];// 优惠价
            $data['coupon_price'] += $order_settlement['coupon_price'];// 优惠券抵扣
            $data['return_totals'] += $order_settlement['return_totals'];//若订单商品退款，退还金额
            $order_id_arr[] = $order['id'];
        }
        $order_statis_id = DB::table('order_statis')->insertGetId($data); // 添加一笔结算统计

        Log::info('商家订单结算：', ['order_statis_id' => $order_statis_id, 'order_id_arr' => $order_id_arr ]);
        DB::table('order')->whereIn('id', $order_id_arr)->update(array('order_statis_id' => $order_statis_id)); // 标识为已经结算
        // 给商家加钱 记录日志
        Store::storeAccountLog(0, 0, $this->store_id, $data['result_totals'], '平台订单结算');


    }

    /**
     * 商户订单销售额
     */
    public function getStoreOrderAmount() : array
    {
        $totalAmount = Order::where(function($query) {
            $query->where('store_id', $this->store_id);
            $query->where('pay_status',1);
        })->sum('order_amount');

        $lastMouthAmount = Order::where(function($query){
            $query->where('store_id', $this->store_id);
            $query->where('pay_status',1);
            $query->where('created_at','>',Carbon::parse('-1 months')->toDateTimeString());

        })->sum('order_amount');

        return [
            'totalAmount' => $totalAmount,
            'lastMouthAmount' => $lastMouthAmount
        ];
    }


}