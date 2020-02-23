<?php

namespace App\Exports;

use App\Models\Order;
use Encore\Admin\Facades\Admin;
use Maatwebsite\Excel\Concerns\FromCollection;

class OrderExporter implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $data = [];
        $title = ['ID', '订单号', '活动类型', '支付时间', '支付状态', '订单状态', '发货状态', '订单总价'];
        array_unshift($data, $title);
        $orders = Order::where('store_id',Admin::user()->store_id)->orderBy('id','desc')->select('id', 'order_sn', 'order_prom_type', 'pay_time', 'pay_status', 'order_status', 'shipping_status', 'total_amount')
            ->get();
        $orders = json_decode(json_encode($orders),true);
        foreach ($orders as $order) {
            $order['pay_status'] = $order['pay_status_text'];
            $order['order_status'] = $order['order_status_text'];
            $order['shipping_status'] = $order['shipping_status_text'];
            $order['order_prom_type'] = $order['order_prom_type_text'];
            unset($order['pay_status_text']);
            unset($order['order_status_text']);
            unset($order['shipping_status_text']);
            unset($order['order_prom_type_text']);
            array_push($data, $order);
        }
        //dd($data);
        return collect($data);
    }
}
