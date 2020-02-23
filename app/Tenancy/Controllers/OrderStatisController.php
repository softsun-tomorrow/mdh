<?php

namespace App\Tenancy\Controllers;

use App\Models\OrderStatis;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OrderStatisController extends Controller
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
        $grid = new Grid(new OrderStatis);

        $grid->id('Id');
        $grid->store_id(trans('order_statis.store_id'))->display(function($storeId){
            return optional(Store::find($storeId))->shop_name;
        });
        $grid->start_date(trans('order_statis.start_date'));
        $grid->end_date(trans('order_statis.end_date'));
        $grid->order_totals(trans('order_statis.order_totals'));
        $grid->shipping_totals(trans('order_statis.shipping_totals'));
        $grid->return_totals(trans('order_statis.return_totals'));
//        $grid->return_integral(trans('order_statis.return_integral'));
        $grid->commis_totals(trans('order_statis.commis_totals'));
        $grid->give_integral(trans('order_statis.give_integral'));
        $grid->result_totals(trans('order_statis.result_totals'));
        $grid->create_date(trans('order_statis.create_date'));
//        $grid->order_prom_amount(trans('order_statis.order_prom_amount'));
//        $grid->coupon_price(trans('order_statis.coupon_price'));
//        $grid->integral(trans('order_statis.integral'));
//        $grid->distribut(trans('order_statis.distribut'));

        $grid->disableActions();
        $grid->disableCreateButton();

        $grid->model()->where('store_id',Admin::user()->store_id)->orderBy('id','desc');
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
        $show = new Show(OrderStatis::findOrFail($id));

        $show->id('Id');
        $show->start_date(trans('order_statis.start_date'));
        $show->end_date(trans('order_statis.end_date'));
        $show->order_totals(trans('order_statis.order_totals'));
        $show->shipping_totals(trans('order_statis.shipping_totals'));
        $show->return_totals(trans('order_statis.return_totals'));
        $show->return_integral(trans('order_statis.return_integral'));
        $show->commis_totals(trans('order_statis.commis_totals'));
        $show->give_integral(trans('order_statis.give_integral'));
        $show->result_totals(trans('order_statis.result_totals'));
        $show->create_date(trans('order_statis.create_date'));
        $show->store_id(trans('order_statis.store_id'));
        $show->order_prom_amount(trans('order_statis.prom_amount'));
        $show->coupon_price(trans('order_statis.coupon_price'));
        $show->integral(trans('order_statis.integral'));
        $show->distribut(trans('order_statis.distribut'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderStatis);

        $form->datetime('start_date', trans('order_statis.start_date'))->default(date('Y-m-d H:i:s'));
        $form->datetime('end_date', trans('order_statis.end_date'))->default(date('Y-m-d H:i:s'));
        $form->decimal('order_totals', trans('order_statis.order_totals'))->default(0.00);
        $form->decimal('shipping_totals', trans('order_statis.shipping_totals'))->default(0.00);
        $form->decimal('return_totals', trans('order_statis.return_totals'))->default(0.00);
        $form->number('return_integral', trans('order_statis.return_integral'));
        $form->decimal('commis_totals', trans('order_statis.commis_totals'))->default(0.00);
        $form->decimal('give_integral', trans('order_statis.give_integral'))->default(0.00);
        $form->decimal('result_totals', trans('order_statis.result_totals'))->default(0.00);
        $form->datetime('create_date', trans('order_statis.create_date'))->default(date('Y-m-d H:i:s'));
        $form->number('store_id', trans('order_statis.store_id'));
        $form->decimal('order_prom_amount', trans('order_statis.prom_amount'))->default(0.00);
        $form->decimal('coupon_price', trans('order_statis.coupon_price'))->default(0.00);
        $form->number(trans('order_statis.integral'), trans('order_statis.integral'));
        $form->decimal(trans('order_statis.distribut'), trans('order_statis.distribut'))->default(0.00);
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
