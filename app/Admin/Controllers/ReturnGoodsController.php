<?php

namespace App\Admin\Controllers;

use App\Models\ReturnGoods;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
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
        $grid->order_goods_id('Order goods id');
        $grid->order_id('Order id');
        $grid->order_sn('Order sn');
        $grid->goods_id('Goods id');
        $grid->goods_num('Goods num');
        $grid->type('Type');
        $grid->reason('Reason');
        $grid->describe('Describe');
        $grid->evidence('Evidence');
        $grid->imgs('Imgs');
        $grid->status('Status');
        $grid->remark('Remark');
        $grid->user_id('User id');
        $grid->store_id('Store id');
        $grid->spec_key('Spec key');
        $grid->consignee('Consignee');
        $grid->mobile('Mobile');
        $grid->refund_integral('Refund integral');
        $grid->refund_money('Refund money');
        $grid->return_type('Return type');
        $grid->refund_mark('Refund mark');
        $grid->refund_time('Refund time');
        $grid->created_at('Created at');
        $grid->checktime('Checktime');
        $grid->receivetime('Receivetime');
        $grid->canceltime('Canceltime');
        $grid->seller_delivery('Seller delivery');
        $grid->delivery('Delivery');
        $grid->gap('Gap');
        $grid->gap_reason('Gap reason');
        $grid->is_receive('Is receive');

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
        $show->order_goods_id('Order goods id');
        $show->order_id('Order id');
        $show->order_sn('Order sn');
        $show->goods_id('Goods id');
        $show->goods_num('Goods num');
        $show->type('Type');
        $show->reason('Reason');
        $show->describe('Describe');
        $show->evidence('Evidence');
        $show->imgs('Imgs');
        $show->status('Status');
        $show->remark('Remark');
        $show->user_id('User id');
        $show->store_id('Store id');
        $show->spec_key('Spec key');
        $show->consignee('Consignee');
        $show->mobile('Mobile');
        $show->refund_integral('Refund integral');
        $show->refund_money('Refund money');
        $show->return_type('Return type');
        $show->refund_mark('Refund mark');
        $show->refund_time('Refund time');
        $show->created_at('Created at');
        $show->checktime('Checktime');
        $show->receivetime('Receivetime');
        $show->canceltime('Canceltime');
        $show->seller_delivery('Seller delivery');
        $show->delivery('Delivery');
        $show->gap('Gap');
        $show->gap_reason('Gap reason');
        $show->is_receive('Is receive');

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

        $form->number('order_goods_id', 'Order goods id');
        $form->number('order_id', 'Order id');
        $form->text('order_sn', 'Order sn');
        $form->number('goods_id', 'Goods id');
        $form->number('goods_num', 'Goods num')->default(1);
        $form->switch('type', 'Type');
        $form->text('reason', 'Reason');
        $form->textarea('describe', 'Describe');
        $form->text('evidence', 'Evidence')->default('1');
        $form->textarea('imgs', 'Imgs');
        $form->switch('status', 'Status');
        $form->text('remark', 'Remark');
        $form->number('user_id', 'User id');
        $form->number('store_id', 'Store id');
        $form->text('spec_key', 'Spec key');
        $form->text('consignee', 'Consignee');
        $form->mobile('mobile', 'Mobile');
        $form->number('refund_integral', 'Refund integral');
        $form->decimal('refund_money', 'Refund money')->default(0.00);
        $form->switch('return_type', 'Return type');
        $form->text('refund_mark', 'Refund mark');
        $form->datetime('refund_time', 'Refund time')->default(date('Y-m-d H:i:s'));
        $form->datetime('checktime', 'Checktime')->default(date('Y-m-d H:i:s'));
        $form->datetime('receivetime', 'Receivetime')->default(date('Y-m-d H:i:s'));
        $form->datetime('canceltime', 'Canceltime')->default(date('Y-m-d H:i:s'));
        $form->textarea('seller_delivery', 'Seller delivery');
        $form->textarea('delivery', 'Delivery');
        $form->decimal('gap', 'Gap')->default(0.00);
        $form->text('gap_reason', 'Gap reason');
        $form->switch('is_receive', 'Is receive');
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
