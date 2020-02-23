<?php

namespace App\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreNotice;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Widgets\Table;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        //最新一条公告
        $lasted = StoreNotice::orderBy('id','desc')->first();

        admin_info($lasted->title, $lasted->content);
        if (Admin::user()->enabled == 0) {
            $msg = '账号已禁用，请联系管理员！';
            admin_toastr($msg, 'error', ['timeOut' => 5000]);
        }
        $wechat = '<img src=" ' . get_config_by_name('site_wechat') .' "/>' ;

        //. '，平台公众号: ' . $wechat
        return $content
            ->header('提示')
            ->description('平台客服电话：' . get_config_by_name('service_telephone') . '，平台客服邮箱：' . get_config_by_name('service_email')  )
            ->row(function (Row $row){
//                $row->column(12, function (Column $column) {
//                    $headers = ['公告标题', '公告内容', '发布时间'];
//                    $rows = StoreNotice::orderBy('id','desc')->limit(3)->get(['title','content','created_at']);
//                    $rows = json_decode(json_encode($rows),true);
//                    $table = new Table($headers, $rows);
//                    $column->append($table->render());
//                });
            })
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $today = DB::table('order')->where(function ($query) {
                        $query->where('pay_status', 1);
                        $query->where('store_id', Admin::user()->store_id);

                        $query->whereDate('created_at', Carbon::today()->toDateString());
                    })->sum('order_amount');

                    $yestoday = DB::table('order')->where(function ($query) {
                        $query->where('pay_status', 1);
                        $query->where('store_id', Admin::user()->store_id);

                        $query->whereDate('created_at', Carbon::yesterday()->toDateString());
                    })->sum('order_amount');

                    $mouth = DB::table('order')->where(function ($query) {
                        $query->where('pay_status', 1);
                        $query->where('store_id', Admin::user()->store_id);

                        $monthFirst = Carbon::now()->startOfMonth();
                        $monthLast = Carbon::now()->endOfMonth();
                        $query->whereBetween('created_at', [$monthFirst, $monthLast]);
                    })->sum('order_amount');

                    $store = Store::find(Admin::user()->store_id);
                    $html = '<table class="layui-table">
                              <colgroup>
                                  <col width="200">
                                  <col width="200">
                                  <col width="200">
                                  <col width="200">
                                </colgroup>
                             
                              <tbody>
                                <tr>
                                  <td>' . $today . '</td>
                                    <td>' . $yestoday . '</td>
                                    <td>' . $mouth . '</td>
                                    <td>' . $store->reserve_account . '</td>
                                </tr>
                                <tr>
                                  <td>今日店铺营业额</td>
                                  <td>昨日店铺营业额</td>
                                  <td>本月店铺营业额</td>
                                  <td>预留金额</td>
                                  
                                </tr>
                              </tbody>
                            </table>';


                    $column->append($html);
                });
            })->row(function (Row $row) {
                $row->column(12, function (Column $column) {

                    $order = [
                        'pay' => DB::table('order')->where(function($query){
                            $query->where('store_id', Admin::user()->store_id);
                            $query->where('pay_status',0)->where('order_status',0);
                        })->count(),
                        'share' => Order::where(function($query){
                            $query->where('store_id', Admin::user()->store_id);
                            $query->where('order_prom_type', 2);
                            $query->whereIn('order_status', [0, 1]);
                            $query->where('shipping_status', 0);
                            $query->where('pay_status', 1);

                            $query->whereHas('team_found', function ($query) {
                                $query->where(function ($query) {
                                    $query->where('status', 1);
                                });
                            });
                        })->count(),

                        'finishTeam' => Order::where(function($query){
                            $query->where('store_id', Admin::user()->store_id);
                            $query->where('order_prom_type', 2);
                            $query->whereIn('order_status', [0, 1]);
                            $query->where('shipping_status', 0);
                            $query->where('pay_status', 1);

                            $query->whereHas('team_found', function ($query) {
                                $query->where(function ($query) {
                                    $query->where('status', 2);
                                });
                            });
                        })->count(),

                        'confirm' => DB::table('order')->where(function($query){
                            $query->where('store_id', Admin::user()->store_id);
                            $query->where('order_status',1)->where('shipping_status',1);

                        })->count(),
                        'waitSend' => DB::table('order')->where(function($query){
                            $query->where('store_id', Admin::user()->store_id);
                            $query->where('order_status',1)->where('shipping_status',0);

                        })->count(),
                        'waitConfirm' => DB::table('order')->where(function($query){
                            $query->where('store_id', Admin::user()->store_id);
                            $query->where('order_status',0)->where('pay_status',1);
                        })->count(),
                        'comment' => DB::table('order')->where(function($query){
                            $query->where('store_id', Admin::user()->store_id);
                            $query->where('order_status',2)->where('shipping_status',1);
                        })->count(),
                        'refund' => DB::table('return_goods')->where(function ($query) {
                            $query->where('store_id', Admin::user()->store_id);
                        })
                            ->count(),
                        'commentCount' => DB::table('comment')->where(function($query){
                            $query->where('store_id', Admin::user()->store_id);

                        })->count(),
                    ];

                    $html = '<table class="layui-table">
                              <colgroup>
                                  <col width="200">
                                  <col width="200">
                                  <col width="200">
                                  <col width="200">
                                </colgroup>
                             
                              <tbody>
                                <tr>
                                  <td>' . $order['pay'] . '</td>
                                  <td>' . $order['waitSend'] . '</td>
                                  <td>' . $order['share'] . '</td>
                                  <td>' . $order['finishTeam'] . '</td>
                                  <td>' . $order['waitConfirm'] . '</td>
                                  <td>' . $order['commentCount'] . '</td>
                                  <td>' . $order['refund'] . '</td>
                                </tr>
                                <tr>
                                  <td><a href="/tenancy/order">待付款</a></td>
                                  <td><a href="/tenancy/order">待发货</a></td>
                                  <td><a href="/tenancy/order">待成团</a></td>
                                  <td><a href="/tenancy/order">成团订单</a></td>
                                  <td><a href="/tenancy/order">待确认</a></td>
                                  <td><a href="/tenancy/order">订单评价</a></td>
                                  <td><a href="/tenancy/order">售后待处理</a></td>
                                </tr>
                              </tbody>
                            </table>';
                    $column->append($html);
                });
            })->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $store = Store::find(Admin::user()->store_id);
                    if (!$store['shop_name']) {
                        $html = '<span style="color: red">请先完善店铺信息！</span>';
                    } else {
                        $html = '';
                    }
                    
                    $column->append($html);
                });
            })
            ->body(new Box('最近一个月销量', view('tenancy.chartjs', ['store_id' => Admin::user()->store_id])));
    }
}
