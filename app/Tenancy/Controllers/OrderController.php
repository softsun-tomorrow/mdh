<?php

namespace App\Tenancy\Controllers;

use App\Tenancy\Extensions\OrderExporter;
use App\Models\Order;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Symfony\Component\Console\Helper\Table;

class OrderController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Index')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);
        $grid->id('Id');

        $grid->header(function($query){
            //总营业额，今日营业额，上月营业额
            $total = DB::table('order')->where(function($query){
                $query->where('pay_status',1);
                $query->where('store_id',Admin::user()->store_id);
            })->sum('order_amount');

            $today = DB::table('order')->where(function($query){
                $query->where('pay_status',1);
                $query->where('store_id',Admin::user()->store_id);

                $query->whereDate('created_at',Carbon::today()->toDateString());
            })->sum('order_amount');

            $lastMouth = DB::table('order')->where(function($query){
                $query->where('pay_status',1);
                $query->where('store_id',Admin::user()->store_id);

                //上个月第一天
                $lastMonthFirst = Carbon::now()->subMonth()->firstOfMonth();
                $lastMonthLast = Carbon::now()->subMonth()->lastOfMonth();
                $query->whereBetween('created_at',[$lastMonthFirst,$lastMonthLast]);
            })->sum('order_amount');

            return '总金额：'. $total . '，今日金额：' . $today . '，上月金额：'. $lastMouth;
        });

        $grid->column('订单号')->display(function () {
            return $this->master_order_sn ? $this->master_order_sn : $this->order_sn;
        })->expand(function ($model) {

            $orderGoods = $model->order_goods()->take(20)->get()->map(function ($orderGoods) {
                return $orderGoods->only(['goods_name', 'goods_price', 'goods_num', 'spec_key_name']);
            });
            return new \Encore\Admin\Widgets\Table(['商品名称', '商品价格', '商品数量', '规格'], $orderGoods->toArray());
        });
        $grid->order_prom_type(trans('order.order_prom_type'))->using(Order::getOrderPromTypeArr());

        $grid->pay_time(trans('order.pay_time'));
        $grid->pay_status(trans('order.pay_status'))->using(Order::getPayStatusArr());
        $grid->order_status(trans('order.order_status'))->using(Order::getOrderStatusArr());
        $grid->shipping_status(trans('order.shipping_status'))->using(Order::getShippingStatusArr());
        //$grid->shipping_name(trans('order.shipping_name'));

        //$grid->user_note(trans('order.user_note'));
        $grid->order_amount(trans('order.order_amount'))->editable();
        $grid->total_amount(trans('order.total_amount'));
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->model()->orderBy('id', 'desc');
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
//            $actions->disableView();
        });
        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->expand();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $query->where('order_sn', "{$this->input}")->orWhere('master_order_sn', "{$this->input}");
                }, '订单号');

                $filter->where(function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%")->orWhere('mobile', 'like', "%{$this->input}%");
                    });
                }, '用户名或手机号');

                $filter->in('order_prom_type', trans('order.order_prom_type'))->checkbox(Order::getOrderPromTypeArr());
            });

            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    switch ($this->input) {
                        case 1: //待确认
                            $query->where('pay_status',1)->where('order_status',0);
                            break;
                        case 2: //已确认，待发货
                            $query->where('order_status',1)->where('shipping_status',0);
                            break;
                        case 3: //已发货，待收货
                            $query->where('order_status',1)->where('shipping_status',1);
                            break;
                        case 4: //已收货
                            $query->where('order_status',2)->where('shipping_status',1);
                            break;
                        case 5: //已取消
                            $query->where('order_status',3);
                            break;
                        case 6: //已完成
                            $query->where('order_status',4);
                            break;
                        default: //全部订单

                    }

                }, '订单状态')->select([0 => '全部订单', 1 => '待确认', 2 => '待发货', 3 => '待收货', 4 => '已收货', 5 => '已取消', 6 => '已完成']);

                $filter->between('created_at', trans('order.created_at'))->datetime();

            });






//            $filter->where(function($query){
//                $query->whereHas('store',function($query){
//                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
//                });
//            },'店铺名或手机号');


        });

        $grid->model()->where('store_id', Admin::user()->store_id)->orderBy('id', 'desc');
//        $grid->disableExport(true);
//        $grid->exporter(new OrderExporter());

        $grid->tools(function ($tools) {
            $tools->append("<a href='/tenancy/api/exportOrder' class='btn btn-sm btn-success' style='float: right;' target='_blank'>
<i class='fa fa-save'></i> 导出</a>");

        });
        return $grid;

//        $grid->order_sn(trans('order.order_sn'));
//        $grid->master_order_sn(trans('order.master_order_sn'));
//        $grid->user_id(trans('order.user_id'));
//        $grid->order_status(trans('order.order_status'));
//        $grid->shipping_status(trans('order.shipping_status'));
//        $grid->pay_status(trans('order.pay_status'));
//        $grid->consignee(trans('order.consignee'));
//        $grid->province_id(trans('order.province'));
//        $grid->city_id(trans('order.city_id'));
//        $grid->district_id(trans('order.district_id'));
//        $grid->address(trans('order.address'));
//        $grid->mobile(trans('order.mobile'));
//        $grid->pay_type(trans('order.pay_type'));
//        $grid->goods_price(trans('order.goods_price'));
//        $grid->order_amount(trans('order.order_amount'));
//        $grid->created_at(trans('order.created_at'));
//        $grid->pay_time(trans('order.pay_time'));
//        $grid->confirm_time(trans('order.confirm_time'));
//        $grid->transaction_id(trans('order.transaction_id'));
//        $grid->user_note(trans('order.user_note'));
//        $grid->admin_note(trans('order.admin_note'));
//        $grid->store_id(trans('order.store_id'));
//        $grid->deleted_at(trans('order.deleted_at'));
//        $grid->area(trans('order.area'));
//        $grid->shipping_code(trans('order.shipping_code'));
//        $grid->shipping_name(trans('order.shipping_name'));
//        $grid->shipping_price(trans('order.shipping_price'));
//        $grid->user_account(trans('order.user_account'));
//        $grid->user_account_money(trans('order.user_account_money'));
//        $grid->coupon_price(trans('order.coupon_price'));
//        $grid->total_amount(trans('order.total_amount'));
//        $grid->shipping_time(trans('order.shipping_time'));
//        $grid->is_comment(trans('order.is_comment'));
//        $grid->order_prom_id(trans('order.order_prom_id'));
//        $grid->order_prom_type(trans('order.order_prom_type'));
//        $grid->order_prom_amount(trans('order.order_prom_amount'));
//        $grid->coupon_ids(trans('order.coupon_ids'));
//        $grid->shipper_code(trans('order.shipper_code'));
//        $grid->logistic_code(trans('order.logistic_code'));
//        $grid->shipper_name(trans('order.shipper_name'));
//

    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show($order = Order::findOrFail($id));

        $show->id('Id');
        $show->order_sn(trans('order.order_sn'));
        $show->master_order_sn(trans('order.master_order_sn'));
        $show->user_id(trans('order.user_id'))->as(function ($userId) {
            if ($user = User::find($userId)) return $user->name;
        });

        $show->order_status(trans('order.order_status'))->using(Order::getOrderStatusArr());
        $show->shipping_status(trans('order.shipping_status'))->using(Order::getShippingStatusArr());
        $show->pay_status(trans('order.pay_status'))->using(Order::getPayStatusArr());
        $show->consignee(trans('order.consignee'));
//        $show->province_id(trans('order.province'));
//        $show->city_id(trans('order.city_id'));
//        $show->district_id(trans('order.district_id'));
        $show->address(trans('order.address'));
        $show->mobile(trans('order.mobile'));
        $show->pay_type(trans('order.pay_type'))->using(Order::getPayTypeArr());
        $show->goods_price(trans('order.goods_price'));

        $show->created_at(trans('order.created_at'));
        $show->pay_time(trans('order.pay_time'));
        $show->confirm_time(trans('order.confirm_time'));
//        $show->transaction_id(trans('order.transaction_id'));
        $show->user_note(trans('order.user_note'));
//        $show->admin_note(trans('order.admin_note'));
        $show->store_id(trans('order.store_id'))->as(function ($storeId) {
            if ($store = Store::find($storeId)) return $store->shop_name;
        });
        $show->area(trans('order.area'));
//        $show->shipping_code(trans('order.shipping_code'))->using(Order::getShippingCodeArr());
//        $show->shipping_name(trans('order.shipping_name'));
        $show->shipping_price(trans('order.shipping_price'));
        $show->user_account(trans('order.user_account'));
        $show->user_account_money(trans('order.user_account_money'));
        $show->coupon_price(trans('order.coupon_price'));
        $show->total_amount(trans('order.total_amount'));
        $show->shipping_time(trans('order.shipping_time'));
        $show->is_comment(trans('order.is_comment'))->using(Order::getIsCommentArr());
//        $show->order_prom_id(trans('order.order_prom_id'));
//        $show->order_prom_type(trans('order.order_prom_type'));
//        $show->order_prom_amount(trans('order.order_prom_amount'));
//        $show->coupon_ids(trans('order.coupon_ids'));
        $show->shipper_code(trans('order.shipper_code'));
        $show->logistic_code(trans('order.logistic_code'));
        $show->shipper_name(trans('order.shipper_name'));
        $show->order_amount(trans('order.order_amount'));
//        $show->pick_code(trans('order.pick_code'));

        $show->order_goods('订单商品', function ($orderGoods) {

            $orderGoods->resource('/tenancy/order_goods');

            $orderGoods->id();
            $orderGoods->goods_name(trans('order_goods.goods_name'));
            $orderGoods->goods_price(trans('order_goods.goods_price'));
            $orderGoods->goods_num(trans('order_goods.goods_num'));
            $orderGoods->spec_key_name(trans('order_goods.spec_key_name'));

            $orderGoods->disableCreateButton();
            $orderGoods->disableRowSelector();
            $orderGoods->disablePagination();
            $orderGoods->disableFilter();
            $orderGoods->disableExport();
            $orderGoods->disableActions();
        });

        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
//                $tools->disableList();
                $tools->disableDelete();
            });

        $show->divider();

        $show->column('操作')->unescape()->as(function () use ($id, $order) {
            $html = '';
            if ($order->pay_status == 1 && $order->order_status == 0) {
                //确认订单按钮
                $html = '<button class="btn  btn-primary confirm_order" data-id="' . $id . '">确认订单</button>';
            }
            if ($order->order_status == 1 && $order->shipping_status == 0) {
                //去发货
                if($order->shipping_code == 'same_city'){
                    //同城配送
                    $html = '<div id="ac" data-storeid="' . Admin::user()->store_id . '"><button class="btn  btn-primary confirm_give" data-id="' . $id . '">确认送货</button>';

                }elseif($order->shipping_code == 'in_shop'){
                    //到店自取
                    $html = '<div id="ac" data-storeid="' . Admin::user()->store_id . '"><button class="btn  btn-primary confirm_code" data-id="' . $id . '">生成取货码</button>';

                }else{
                    //快递
                    $html = '<div id="ac" data-storeid="' . Admin::user()->store_id . '"><label>选择快递</label><select id="shipper_id" class="form-control " name="shipper_id"></select><br><label>快递单号</label><input id="logistic_code" type="text" class="form-control " name="logistic_code"/><br><button class="btn  btn-primary confirm_send" data-id="' . $id . '">确认发货</button>';
                }
            }
            if($order->order_status ==1 && $order->shipping_status == 1){
                if($order->shipping_code == 'in_shop'){
                    $html = '<div id="ac" data-storeid="' . Admin::user()->store_id . '"><button class="btn  btn-primary confirm_get" data-id="' . $id . '">用户已取货</button>';
                }elseif ($order->shipping_code == 'ems'){
                    $html = '<div id="ac" data-storeid="' . Admin::user()->store_id . '"><button class="btn  btn-primary view_shipping" data-id="' . $id . '">查看物流</button>';
                }
            }
            return $html;
        });
        Admin::script($this->getConfirmOrder());
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Order);

        $form->text('order_sn', trans('order.order_sn'));
        $form->text('master_order_sn', trans('order.master_order_sn'));
        $form->select('user_id', trans('order.user_id'))->options('/api/backend/getUser');
        $form->switch('order_status', trans('order.order_status'));
        $form->switch('shipping_status', trans('order.shipping_status'));
        $form->switch('pay_status', trans('order.pay_status'));
        $form->text(trans('order.consignee'), trans('order.consignee'));
        $form->number('province_id', trans('order.province'));
        $form->number('city_id', trans('order.city_id'));
        $form->number('district_id', trans('order.district_id'));
        $form->text(trans('order.address'), trans('order.address'));
        $form->mobile(trans('order.mobile'), trans('order.mobile'));
        $form->switch('pay_type', trans('order.pay_type'));
        $form->decimal('goods_price', trans('order.goods_price'))->default(0.00);
        $form->decimal('order_amount', trans('order.order_amount'));
        $form->datetime('pay_time', trans('order.pay_time'))->default(date('Y-m-d H:i:s'));
        $form->datetime('confirm_time', trans('order.confirm_time'))->default(date('Y-m-d H:i:s'));
        $form->text('transaction_id', trans('order.transaction_id'));
        $form->text('user_note', trans('order.user_note'));
        $form->text('admin_note', trans('order.admin_note'));
        $form->number('store_id', trans('order.store_id'));
        $form->text(trans('order.area'), trans('order.area'));
        $form->text('shipping_code', trans('order.shipping_code'));
        $form->text('shipping_name', trans('order.shipping_name'));
        $form->decimal('shipping_price', trans('order.shipping_price'))->default(0.00);
        $form->number('user_account', trans('order.user_account'));
        $form->decimal('user_account_money', trans('order.user_account_money'))->default(0.00);
        $form->decimal('coupon_price', trans('order.coupon_price'))->default(0.00);
        $form->decimal('total_amount', trans('order.total_amount'))->default(0.00);
        $form->datetime('shipping_time', trans('order.shipping_time'))->default(date('Y-m-d H:i:s'));
        $form->switch('is_comment', trans('order.is_comment'));
        $form->number('order_prom_id', trans('order.order_prom_id'));
        $form->switch('order_prom_type', trans('order.order_prom_type'));
        $form->decimal('order_prom_amount', trans('order.order_prom_amount'))->default(0.00);
        $form->text('coupon_ids', trans('order.coupon_ids'));
        $form->text('shipper_code', trans('order.shipper_code'));
        $form->text('logistic_code', trans('order.logistic_code'));
        $form->text('shipper_name', trans('order.shipper_name'));

        $form->saving(function (Form $form) {

        });

        return $form;
    }

    /**
     * 确认订单
     */
    public function getConfirmOrder()
    {
        return <<<EOF
//快递列表
$(document).ready(function(){
    var store_id = $('#ac').data('storeid');

//    console.log(store_id);
    var url = '/api/backend/getStoreShipper?store_id=' + store_id;
    $.ajax({
        method:'get',
        url:url,
        async:false,
        success:function(ret){
//            console.log(ret);
            var options = '';
            $.each(ret.data,function(i,val){
                options = '<option value="'+val.id+'">'+val.shipper_name+'</optoin>';
                $('#shipper_id').append(options);
            });
        }
    })

});

//确认订单
$(document).on('click','.confirm_order',function(){
    var id = $(this).data('id');
    var url = '/api/backend/confirmOrder';
    $.ajax({
        method:'post',
        url:url,
        data:{id:id},
        success:function(ret){
//            console.log(ret);
            if(ret.code == 1){
                layer.msg('保存成功', {
                    icon: 1,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.reload();
                });
            }else{
                layer.msg('保存失败', {
                    icon: 5,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                   window.location.reload();

                });
            }
        }
    })
});

//确认发货
$(document).on('click','.confirm_send',function(){
    var id = $(this).data('id');
    var shipper_id = $('#shipper_id').val();
    var logistic_code = $('#logistic_code').val();
    var store_id = $('#ac').data('storeid');

    if(shipper_id == '' || logistic_code == ''){
        layer.msg('快递和单号必填',{icon:5});
    }

    console.log({id:id,shipper_id:shipper_id,logistic_code:logistic_code,store_id:store_id});

    var url = '/api/backend/confirmSend';
    $.ajax({
        method:'post',
        url:url,
        data:{id:id,shipper_id:shipper_id,logistic_code:logistic_code,store_id:store_id},
        success:function(ret){
            console.log(ret);
            if(ret.code == 1){
                layer.msg('保存成功', {
                    icon: 1,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.reload();
                });
            }else{
                layer.msg('保存失败', {
                    icon: 5,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.reload();
                });
            }
        }
    })
});


//确认送货
$(document).on('click','.confirm_give',function(){
    var id = $(this).data('id');
    var url = '/api/backend/confirmGiveOrder';
    $.ajax({
        method:'post',
        url:url,
        data:{id:id},
        success:function(ret){
//            console.log(ret);
            if(ret.code == 1){
                layer.msg('保存成功', {
                    icon: 1,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.reload();
                });
            }else{
                layer.msg('保存失败', {
                    icon: 5,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                   window.location.reload();

                });
            }
        }
    })
});

//生成取货码
$(document).on('click','.confirm_code',function(){
    var id = $(this).data('id');
    var url = '/api/backend/confirmCode';
    $.ajax({
        method:'post',
        url:url,
        data:{id:id},
        success:function(ret){
//            console.log(ret);
            if(ret.code == 1){
                layer.msg('生成成功', {
                    icon: 1,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.reload();
                });
            }else{
                layer.msg('生成失败', {
                    icon: 5,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                   window.location.reload();

                });
            }
        }
    })
});

//用户已取货
$(document).on('click','.confirm_get',function(){
    var id = $(this).data('id');
    var url = '/api/backend/confirmGet';
    $.ajax({
        method:'post',
        url:url,
        data:{id:id},
        success:function(ret){
//            console.log(ret);
            if(ret.code == 1){
                layer.msg('操作成功', {
                    icon: 1,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                    window.location.reload();
                });
            }else{
                layer.msg('操作失败', {
                    icon: 5,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                   window.location.reload();

                });
            }
        }
    })
});


//查看物流
$(document).on('click','.view_shipping',function(){
    var id = $(this).data('id');
    var url = '/api/order/queryEms';
    $.ajax({
        method:'post',
        url:url,
        data:{id:id},
        success:function(ret){
            console.log(ret);
            if(ret.code == 1){
                var content = '<div>';
                $.each(ret.data.Traces, function(k,v){
                    content += '<li>'+ v.AcceptStation +'</li>';
                });
                content += '</div>';
                
                console.log(content);
                layer.open({
                  type: 1,
                  area: ['600px', '360px'],
                  shadeClose: true,   //点击遮罩关闭
                  content: content
                });
            }else{
                layer.msg('操作失败', {
                    icon: 5,
                    offset: ['50%'],
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function () {
                   window.location.reload();

                });
            }
        }
    })
});

EOF;

    }
}
