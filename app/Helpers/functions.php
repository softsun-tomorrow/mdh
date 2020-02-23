<?php
/**
 * 生成订单号
 * @param string $prefix
 * @return string
 */
function build_order_sn($prefix = '')
{
    $str = $prefix . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    return $str;
}


function update_pay_status($order_sn)
{
    if (stripos($order_sn, 'vl') !== false) {
        //小区长升级订单
        $isPay = \DB::table('village_order')->where(['order_sn' => $order_sn, 'pay_status' => 1])->count();
        if ($isPay) return true;

        \DB::table('village_order')->where('order_sn', $order_sn)->update(['pay_status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
        \App\Models\VillageOrder::doAfterPay($order_sn);
//        echo 'recharge';


    } elseif (stripos($order_sn, 'store') !== false) {
        //店铺入驻订单
        $isPay = \DB::table('other_order')->where(['order_sn' => $order_sn, 'pay_status' => 1])->count();
        if ($isPay) return true;

        \DB::table('other_order')->where('order_sn', $order_sn)->update(['pay_status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
        \App\Models\OtherOrder::doAfterPay($order_sn);
//        echo 'recharge';


    } elseif (stripos($order_sn, 'cp') !== false) {
        //资金管理订单
        $isPay = \DB::table('capital_order')->where(['order_sn' => $order_sn, 'pay_status' => 1])->count();
        if ($isPay) return true;

        \DB::table('capital_order')->where('order_sn', $order_sn)->update(['pay_status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
        \App\Models\CapitalOrder::doAfterPay($order_sn);
//        echo 'recharge';


    } else {
        //商品购买
        // 先查看一下 是不是 合并支付的主订单号
        $orderlist = \App\Models\Order::where('master_order_sn', $order_sn)->get();
        if ($orderlist->count()) {
            foreach ($orderlist as $k => $v) {
                update_pay_status($v['order_sn']);
            }
            return;
        }

        $order = \App\Models\Order::where(['order_sn' => $order_sn, 'pay_status' => 0])->first();
        if (empty($order)) return false;//这笔订单已经处理过了

        //修改订单支付状态
        $order->pay_status = 1;
        $order->pay_time = date('Y-m-d H:i:s');
        $order->save();

        if ($order->order_prom_type == 1) {
            //限时抢购
            $flashSaleOrderLogic = new \App\Logic\FlashSaleOrderLogic();
            $flashSaleOrderLogic->doOrderPayAfter($order);

        } elseif ($order->order_prom_type == 2) {
            //拼团
            $teamOrderLogic = new App\Logic\TeamOrderLogic();
            $teamOrderLogic->setTeam(\App\Models\TeamActivity::find($order->order_prom_id));
            $teamOrderLogic->doOrderPayAfter($order);
        } elseif ($order->order_prom_type == 3) {
            //抽奖
            $lotteryLogic = new \App\Logic\LotteryOrderLogic();
            $lotteryLogic->setLottery(\App\Models\Lottery::find($order->order_prom_id));
            $lotteryLogic->doOrderPayAfter($order);
        }

        //代理升级商品
        $orderGoods = \App\Models\OrderGoods::where('order_id', $order->id)->first();
//        dd($orderGoods->toArray());
        if ($orderGoods) {

            if ($orderGoods->goods_type == 1) {
                //升级赚钱
                $userLogic = new \App\Logic\UserLogic();
                $userLogic->setUserId($order->user_id);
                $userLogic->setUser(\App\Models\User::find($order->user_id));
                $userLogic->setOrder($order);
                $userLogic->upgrade();
            }
        }

        //减库存
        $orderLogic = new \App\Logic\OrderLogic();
        $orderLogic->minus_stock($order);

        //写入平台资金表
        \App\Models\ExpenseLog::add(0, 0, $order->order_amount, $order->id, $order_sn, '订单收款');
    }

}


//自定义函数手机号隐藏中间四位
function yc_phone($str)
{
    $str = $str;
    $resstr = substr_replace($str, '****', 3, 4);
    return $resstr;
}

function price_format($price)
{
    $res = number_format($price, 2, ".", "");
    return $res;
}

//随机字符串

function randomkeys($length)
{
    $pattern = '1234567890abcdefghijklmnopqrstuvwxyz
               ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $pattern{mt_rand(0, 35)};    //生成php随机数
    }
    return $key;

}


//获取配置
function get_config($name = '')
{
    $base = \App\Models\Config::getConfigValue();
    if ($name) {
        return $base['base.' . $name];

    } else {
        return $base;
    }
}

/**
 * 求两个已知经纬度之间的距离,单位为米
 *
 * @param lng1 $ ,lng2 经度
 * @param lat1 $ ,lat2 纬度
 * @return float 距离，单位米
 */
function getdistance($lng1, $lat1, $lng2, $lat2)
{
    // 将角度转为狐度
    $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
    return round($s);
}

function nonceStr()
{
    static $seed = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
    $str = '';
    for ($i = 0; $i < 8; $i++) {
        $rand = rand(0, count($seed) - 1);
        $temp = $seed[$rand];
        $str .= $temp;
        unset($seed[$rand]);
        $seed = array_values($seed);
    }
    return $str;
}

/**
 * 获取本周所有日期
 */
function get_week($time = '', $format = 'Y-m-d')
{
    $time = $time != '' ? $time : time();
    $week = date('w', $time);
    $date = [];
    for ($i = 1; $i <= 7; $i++) {
        $date[$i] = date($format, strtotime('+' . $i - $week . ' days', $time));
    }
    return array_values($date);
}

/**
 * 获取7天时间
 * @param $startDate 开始日期
 */
function getSevenDays($startDate)
{
    $date = [];
    for ($i = 0; $i <= 6; $i++) {
        $date[$i] = date('Y-m-d', strtotime('+' . $i . ' days', $startDate));
    }
    return array_values($date);
}


function get_config_by_name($name)
{
    return \App\Models\Config::getConfigValueByName($name);
}

function str2json($key)
{
    if (!empty(trim($key))) {
        $key = explode(',', $key);
        sort($key);
        foreach ($key as $k => $v) {
            $key[$k] = (int)$v;

        }
        $keyJson = json_encode($key);
    }
    return $keyJson;
}

function strKey2json($keyStr)
{
    $key = explode(',', $keyStr);
    sort($key);
    foreach ($key as $k => $v) {
        $key[$k] = (int)$v;
    }
    $key = json_encode($key);
    return $key;
}

//判断是否是json
function is_not_json($str)
{
    return is_null(json_decode($str));
}

//获取升级配置
function get_upgrade_config()
{

    $list = \App\Models\Config::where('name', 'like', 'upgrade.' . '%')->get();
    $data = [];
    foreach ($list as $k => $v) {
        $key = substr($v['name'], 8);
        $data[$key] = $v['value'];
    }
    return $data;
}

/**
 * 订单结算
 * @param $order_id  订单order_id
 * @param $rec_id 需要退款商品rec_id
 */

function order_settlement($order_id)
{
    $order = \App\Models\Order::where(array('id' => $order_id, 'pay_status' => 1))->first()->toArray();//订单详情
    if ($order) {
        $order['store_settlement'] = $order['shipping_price'];//商家待结算初始金额
        $order_goods = \App\Models\OrderGoods::where(array('order_id' => $order_id))->get();//订单商品
        $order['goods_amount'] = $order['return_totals'] = $prom_and_coupon = $order['settlement'] = $distribut = 0;
        $give_integral = $order['store_settlement'] = $order['refund_integral'] = 0;
        /* 商家订单商品结算公式(独立商家一笔订单计算公式)
        *  均摊比例 = 这个商品总价/订单商品总价
        *  均摊优惠金额  = 均摊比例 *(代金券抵扣金额 + 优惠活动优惠金额)
        *  商品实际售卖金额  =  商品总价 - 购买此商品赠送积分 - 此商品分销分成 - 均摊优惠金额
        *  商品结算金额  = 商品实际售卖金额 - 商品实际售卖金额*此类商品平台抽成比例
        *  订单实际支付金额  =  订单商品总价 - 代金券抵扣金额 - 优惠活动优惠金额(跟用户使用积分抵扣，使用余额支付无关,积分在商家赠送时平台已经扣取)
        *
        *  整个订单商家结算所得金额  = 所有商品结算金额之和 + 物流费用(商家发货，物流费直接给商家)
        *  平台所得提成  = 所有商品提成之和
        *  商品退款说明 ：如果使用了积分，那么积分按商品均摊退回给用户，但使用优惠券抵扣和优惠活动优惠的金额此商品均摊的就不退了
        *  积分说明：积分在商家赠送时，直接从订单结算金中扣取该笔赠送积分可抵扣的金额
        *  优惠券赠送使用说明 ：优惠券在使用的时直接抵扣商家订单金额,无需跟平台结算，全场通用劵只有平台可以发放，所以由平台自付
        *  交易费率：例如支付宝，微信都会征收交易的千分之六手续费
        */

        $point_rate = 100;
        $point_rate = 1 / $point_rate; //积分换算比例

        foreach ($order_goods as $k => $val) {
            $settlement = $goods_amount = $val['goods_price'] * $val['goods_num']; //此商品该结算金额初始值
            $settlement_rate = round($goods_amount / $order['goods_price'], 4);//此商品占订单商品总价比例

            if ($val['distribut'] > 0) {
                $settlement = $settlement - $val['distribut'] * $val['goods_num'];//减去分销分成金额
            }

            //均摊优惠金额  = 此商品总价/订单商品总价*优惠总额
            if ($order['order_prom_amount'] > 0 || $order['coupon_price'] > 0) {
                $prom_and_coupon = $settlement_rate * ($order['order_prom_amount'] + $order['coupon_price']);
                $settlement = $settlement - $prom_and_coupon;//减去优惠券抵扣金额和优惠折扣
            }

            if ($val['is_send'] == 3) {
                $return_info = \Illuminate\Support\Facades\DB::table('return_goods')->where(array('id' => $val['id']))->first();
                $order['return_totals'] +=  $return_info['refund_money']; //退款退还金额

                $order_goods[$k]['settlement'] = 0;
                $order_goods[$k]['goods_settlement'] = 0;
            } else {
                $order_goods[$k]['settlement'] = round($settlement * $val['commission'] / 100, 2);//每件商品平台抽成所得
                $order_goods[$k]['goods_settlement'] = round($settlement, 2) - $order_goods[$k]['settlement'];//每件商品该结算金额

                $distribut = $val['distribut'] * $val['goods_num'];//订单分销分成
            }

            $order['store_settlement'] += $order_goods[$k]['goods_settlement']; //订单所有商品结算所得金额之和
            $order['settlement'] += $order_goods[$k]['settlement'];//平台抽成之和
            $order['goods_amount'] += $goods_amount;//订单商品总价

        }

        $order['store_settlement'] += $order['shipping_price'];//整个订单商家结算所得金额
    }

    return $order;
}

/**
 * 文件下载
 * @param $url
 * @param string $absolute_path
 */
function download($url, $absolute_path = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $file = curl_exec($ch);
    curl_close($ch);
    $resource = fopen($absolute_path, 'a');
    fwrite($resource, $file);
    fclose($resource);
}

//curl 没有做错误处理
function getImage(string $url, $path)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, ""); //加速 这个地方留空就可以了
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    $resource = fopen($path, 'a');
    fwrite($resource, $output);
    fclose($resource);
//    return $output;
}

