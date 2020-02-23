<?php

namespace App\Tenancy\Extensions;

use App\Models\Order;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid\Exporters\ExcelExporter;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Facades\Excel;

class OrderExporter extends ExcelExporter implements FromCollection
{
    protected $fileName = 'order.xlsx';

//    protected $headings = ['ID', '订单号', '活动类型', '支付时间', '支付状态', '订单状态', '发货状态', '订单总价'];

//    protected $columns = [
//        'id' => 'ID',
//        'order_sn' => '订单号',
//        'order_prom_type' => '活动类型',
//        'pay_time' => '支付时间',
//        'pay_status' => '支付状态',
//        'order_status' => '订单状态',
//        'shipping_status' => '发货状态',
//        'total_amount' => '订单总价',
//    ];

    public function collection2()
    {
        $result = Order::select('id', 'order_sn', 'order_prom_type', 'pay_time', 'pay_status', 'order_status', 'shipping_status', 'total_amount')
            ->get();
        //dd(json_decode(json_encode($result),true));
//        $filtered = $order->only(['id','order_sn','order_prom_type_text','pay_time','pay_status_text','order_status_text','shipping_status_text','total_amount']);
//        $filtered = $order->only(['id','order_sn','order_prom_type','pay_time','pay_status','order_status','shipping_status','total_amount']);
//        $result = $filtered->all();
//        dd($filtered->all());

        if (!empty($result)) {
            foreach ($result as $k => &$value) {
                /**
                 * [▼
                 * "id" => 104
                 * "order_sn" => "2019051355100564"
                 * "order_prom_type" => 0
                 * "pay_time" => null
                 * "pay_status" => 0
                 * "order_status" => 3
                 * "shipping_status" => 0
                 * "total_amount" => "2603.00"
                 * "pay_status_text" => "待支付"
                 * "order_status_text" => "已取消"
                 * "shipping_status_text" => "未发货"
                 * "order_prom_type_text" => "默认"
                 * ]
                 */
                // 处理导出数据 - 增删改
                $result[$k]['order_sn'] = 'abc';
            }
        }
        return $result;
    }


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