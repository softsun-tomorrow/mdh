<?php

namespace App\Tenancy\Controllers;

use App\Models\OrderGoods;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OrderGoodsController extends Controller
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
        $grid = new Grid(new OrderGoods);

        $grid->id('Id');
        $grid->order_id('Order id');
        $grid->goods_id('Goods id');
        $grid->goods_name('Goods name');
        $grid->goods_num('Goods num');
        $grid->goods_price('Goods price');
        $grid->spec_key('Spec key');
        $grid->spec_key_name('Spec key name');
        $grid->is_send('Is send');
        $grid->store_id('Store id');
        $grid->is_checkout('Is checkout');
        $grid->distribut('Distribut');
        $grid->deleted_at('Deleted at');
        $grid->give_integral('Give integral');
        $grid->give_account('Give account');
        $grid->is_comment('Is comment');
        $grid->prom_type('Prom type');
        $grid->prom_id('Prom id');
        $grid->delivery_id('Delivery id');

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
        $show = new Show(OrderGoods::findOrFail($id));

        $show->id('Id');
        $show->order_id('Order id');
        $show->goods_id('Goods id');
        $show->goods_name('Goods name');
        $show->goods_num('Goods num');
        $show->goods_price('Goods price');
        $show->spec_key('Spec key');
        $show->spec_key_name('Spec key name');
        $show->is_send('Is send');
        $show->store_id('Store id');
        $show->is_checkout('Is checkout');
        $show->distribut('Distribut');
        $show->deleted_at('Deleted at');
        $show->give_integral('Give integral');
        $show->give_account('Give account');
        $show->is_comment('Is comment');
        $show->prom_type('Prom type');
        $show->prom_id('Prom id');
        $show->delivery_id('Delivery id');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderGoods);

        $form->number('order_id', 'Order id');
        $form->number('goods_id', 'Goods id');
        $form->text('goods_name', 'Goods name');
        $form->number('goods_num', 'Goods num');
        $form->decimal('goods_price', 'Goods price')->default(0.00);
        $form->text('spec_key', 'Spec key');
        $form->text('spec_key_name', 'Spec key name');
        $form->switch('is_send', 'Is send');
        $form->number('store_id', 'Store id');
        $form->switch('is_checkout', 'Is checkout');
        $form->decimal('distribut', 'Distribut')->default(0.00);
        $form->number('give_integral', 'Give integral');
        $form->number('give_account', 'Give account');
        $form->switch('is_comment', 'Is comment');
        $form->switch('prom_type', 'Prom type');
        $form->switch('prom_id', 'Prom id');
        $form->number('delivery_id', 'Delivery id');
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
