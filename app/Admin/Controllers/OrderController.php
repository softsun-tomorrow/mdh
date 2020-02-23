<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
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
            })->sum('order_amount');

            $today = DB::table('order')->where(function($query){
                $query->where('pay_status',1);
                $query->whereDate('created_at',Carbon::today()->toDateString());
            })->sum('order_amount');

            $lastMouth = DB::table('order')->where(function($query){
                $query->where('pay_status',1);
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
//        $grid->shipping_name(trans('order.shipping_name'));

        $grid->user_note(trans('order.user_note'));
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
//            $filter->expand();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $query->where('order_sn', "{$this->input}")->orWhere('master_order_sn', "{$this->input}");
                }, '订单号');

                $filter->where(function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%")->orWhere('mobile', 'like', "%{$this->input}%");
                    });
                }, '用户名或手机号');

                $filter->where(function($query){
                    $query->whereHas('store',function($query){
                        $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
                    });
                },'店铺名或手机号');



            });

            $filter->column(1/2, function ($filter) {
                $filter->in('order_prom_type', trans('order.order_prom_type'))->checkbox(Order::getOrderPromTypeArr());

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
                        case 7: //已退款
                            $query->where('pay_status',3);
                            break;
                        default: //全部订单
                    }

                }, '订单状态')->select([0 => '全部订单', 1 => '待确认', 2 => '待发货', 3 => '待收货', 4 => '已收货', 5 => '已取消', 6 => '已完成', 7 => '已退款']);

                $filter->between('created_at', trans('order.created_at'))->datetime();

            });






//            $filter->where(function($query){
//                $query->whereHas('store',function($query){
//                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
//                });
//            },'店铺名或手机号');


        });

        $grid->model()->orderBy('id', 'desc');
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
        $show->order_amount(trans('order.order_amount'));
        $show->created_at(trans('order.created_at'));
        $show->pay_time(trans('order.pay_time'));
        $show->confirm_time(trans('order.confirm_time'));
        $show->transaction_id(trans('order.transaction_id'));
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
        $form->decimal('order_amount', trans('order.order_amount'))->default(0.00);
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
        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });
        return $form;
    }


}
