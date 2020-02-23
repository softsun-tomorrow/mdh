<?php

namespace App\Tenancy\Controllers;

use App\Models\Goods;
use App\Models\ReturnGoods;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ReturnGoodsController extends Controller
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
        $grid = new Grid(new ReturnGoods);

        $grid->id('Id');
//        $grid->order_goods_id('Order goods id');
//        $grid->order_id('Order id');
        $grid->order_sn(trans('return_goods.order_sn'));
        $grid->goods_id(trans('return_goods.goods_id'))->display(function ($goodsId) {
            if ($goods = Goods::find($goodsId)) return $goods->name;
        });
        $grid->goods_num(trans('return_goods.goods_num'));
        $grid->type(trans('return_goods.type'))->using(ReturnGoods::typeArr());
        $grid->is_receive(trans('return_goods.is_receive'))->using(ReturnGoods::isReceiveArr());
        $grid->reason(trans('return_goods.reason'))->using(ReturnGoods::reasonArr());
        $grid->describe(trans('return_goods.describe'));
//        $grid->evidence(trans('return_goods.evidence'));
//        $grid->imgs(trans('return_goods.imgs'));
        $grid->status(trans('return_goods.status'))->using(ReturnGoods::statusArr());
//        $grid->remark(trans('return_goods.remark'));
        $grid->user_id(trans('return_goods.user_id'))->display(function ($userId) {
            if ($user = User::find($userId)) return $user->name;
        });
//        $grid->store_id(trans('return_goods.store_id'));
//        $grid->spec_key(trans('return_goods.spec_key'));
//        $grid->consignee(trans('return_goods.consignee'));
//        $grid->mobile(trans('return_goods.mobile'));
        $grid->refund_integral(trans('return_goods.refund_integral'));
        $grid->refund_money(trans('return_goods.refund_money'));
//        $grid->return_type(trans('return_goods.return_type'));
//        $grid->refund_mark(trans('return_goods.refund_mark'));
        $grid->created_at(trans('return_goods.created_at'));
        $grid->checktime(trans('return_goods.checktime'));
//        $grid->refund_time(trans('return_goods.refund_time'));
//        $grid->receivetime(trans('return_goods.receivetime'));
        $grid->canceltime(trans('return_goods.canceltime'));
//        $grid->seller_delivery(trans('return_goods.seller_delivery'));
//        $grid->delivery(trans('return_goods.delivery'));
//        $grid->gap(trans('return_goods.gap'));
//        $grid->gap_reason(trans('return_goods.gap_reason'));

        $grid->actions(function ($actions) {
            $actions->disableDelete();
//            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->model()->where('store_id',Admin::user()->store_id)->orderBy('id','desc');

        $grid->filter(function ($filter) {
            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->where(function ($query) {
                    $query->where('order_sn', "{$this->input}")->orWhere('master_order_sn', "{$this->input}");
                }, '订单号');

                $filter->where(function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%")->orWhere('mobile', 'like', "%{$this->input}%");
                    });
                }, '用户名或手机号');
                $filter->in('reason',trans('return_goods.reason'))->checkbox(ReturnGoods::reasonArr());

            });

            $filter->column(1/2,function($filter){
                $filter->in('type',trans('return_goods.type'))->checkbox(ReturnGoods::TypeArr());
                $filter->in('is_receive',trans('return_goods.is_receive'))->checkbox(ReturnGoods::isReceiveArr());
                $filter->in('status',trans('return_goods.status'))->checkbox(ReturnGoods::statusArr());
                $filter->between('created_at', trans('return_goods.created_at'))->datetime();

            });
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ReturnGoods::findOrFail($id));

        $show->id('Id');
//        $show->order_goods_id('Order goods id');
        $show->order_id('Order id');
        $show->order_sn(trans('return_goods.order_sn'));
        $show->goods_id(trans('return_goods.goods_id'));
        $show->goods_num(trans('return_goods.goods_num'));
        $show->type(trans('return_goods.type'));
        $show->reason(trans('return_goods.reason'));
        $show->describe(trans('return_goods.describe'));
        $show->evidence(trans('return_goods.describe'));
        $show->imgs(trans('return_goods.imgs'));
        $show->status(trans('return_goods.status'));
        $show->remark(trans('return_goods.remark'));
        $show->user_id(trans('return_goods.user_id'));
        $show->store_id(trans('return_goods.store_id'));
        $show->spec_key(trans('return_goods.spec_key'));
        $show->consignee(trans('return_goods.consignee'));
        $show->mobile(trans('return_goods.mobile'));
        $show->refund_integral(trans('return_goods.refund_integral'));
        $show->refund_money(trans('return_goods.refund_money'));
//        $show->return_type(trans('return_goods.return_type'));
        $show->refund_mark(trans('return_goods.refund_mark'));
        $show->refund_time(trans('return_goods.refund_time'));
        $show->created_at(trans('return_goods.created_at'));
        $show->checktime(trans('return_goods.checktime'));
        $show->receivetime(trans('return_goods.receivetime'));
        $show->canceltime(trans('return_goods.canceltime'));
        $show->seller_delivery(trans('return_goods.seller_delivery'));
        $show->delivery(trans('return_goods.delivery'));
        $show->gap(trans('return_goods.gap'));
        $show->gap_reason(trans('return_goods.gap_reason'));
        $show->is_receive(trans('return_goods.is_receive'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ReturnGoods);

        $form->tab('售后单信息',function($form){
            $form->display('order_sn', trans('return_goods.order_sn'));
            $form->display('user_id', trans('return_goods.user_id'))->with(function ($userId) {
                if ($user = User::find($userId)) return $user->name;
            });

            $form->display('goods_id', trans('return_goods.goods_id'))->with(function ($goodsId) {
                if ($goods = Goods::find($goodsId)) return $goods->name;
            });
            $form->display('goods_num', trans('return_goods.goods_num'))->default(1);
            $form->display('type', trans('return_goods.type'))->with(function ($type) {
                return ReturnGoods::TypeArr()[$type];
            });
            $form->display('is_receive', trans('return_goods.is_receive'))->with(function($isReceive){
                return ReturnGoods::isReceiveArr()[$isReceive];
            });

            $form->display('reason', trans('return_goods.reason'))->with(function ($reason) {
                return ReturnGoods::reasonArr()[$reason];
            });
            $form->display('describe', trans('return_goods.describe'));
            $form->display('imgs', trans('return_goods.imgs'))->with(function ($imgs) {
                $html = '';
                foreach ($imgs as $k => $v) {
                    $html .= '<img style="margin-top=10px;" src="/uploads/'.$v.'"/>';
                }
                return $html;
            });
            $form->display('refund_integral', trans('return_goods.refund_integral'));
            $form->display('refund_money', trans('return_goods.refund_money'))->default(0.00);


        })->tab('审核操作',function($form){
            //-2用户取消-1不同意0待审核1通过2已发货3已收货4换货完成5退款完成6申诉仲裁
//            $form->radio('status', trans('return_goods.status'))->options([ -1 => '不同意', 0 => '待审核', 1 => '通过',5=> '退款完成']);
            $form->radio('status', trans('return_goods.status'))->options([ 0 => '待审核', 1 => '通过',5=> '退款完成']);
            $form->text('refund_mark', trans('return_goods.refund_mark'));
        });

        $form->saved(function (Form $form) {
            if($form->model()->status == 5){
                //审核通过，退款至用户余额
                ReturnGoods::refund($form->model(),$form->model()->status);

            }else{
                $form->model()->checktime = date('Y-m-d H:i:s');
                $form->model()->save();
            }
        });

//        $form->number('order_goods_id', 'Order goods id');
//        $form->number('order_id', 'Order id');



//        $form->text(trans('return_goods.remark'), trans('return_goods.remark'));
//        $form->number('store_id', trans('return_goods.store_id'));
//        $form->text('spec_key', trans('return_goods.spec_key'));
//        $form->text('consignee', trans('return_goods.consignee'));
//        $form->mobile(trans('return_goods.mobile'), trans('return_goods.mobile'));

//        $form->switch('return_type', trans('return_goods.return_type'));
//        $form->datetime('refund_time', trans('return_goods.refund_time'))->default(date('Y-m-d H:i:s'));
//        $form->datetime(trans('return_goods.checktime'), trans('return_goods.checktime'))->default(date('Y-m-d H:i:s'));
//        $form->datetime(trans('return_goods.receivetime'), trans('return_goods.receivetime'))->default(date('Y-m-d H:i:s'));
//        $form->datetime(trans('return_goods.canceltime'), trans('return_goods.canceltime'))->default(date('Y-m-d H:i:s'));
//        $form->textarea('seller_delivery', trans('return_goods.seller_delivery'));
//        $form->textarea(trans('return_goods.delivery'), trans('return_goods.delivery'));
//        $form->decimal(trans('return_goods.gap'), trans('return_goods.gap'))->default(0.00);
//        $form->text('gap_reason', trans('return_goods.gap_reason'));

        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        return $form;
    }
}
