<?php

namespace App\Tenancy\Controllers;

use App\Models\ShippingType;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ShippingTypeController extends Controller
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
        $grid = new Grid(new ShippingType);

        $grid->id('Id');
//        $grid->store_id(trans('shipping_type.store_id'));
//        $grid->shipping_code(trans('shipping_type.shipping_code'));
        $grid->shipping_name(trans('shipping_type.shipping_name'));
        $grid->shipping_desc(trans('shipping_type.shipping_desc'));
        $grid->status(trans('shipping_type.status'))->using(ShippingType::getStatusArr());
        $grid->shipping_money(trans('shipping_type.shipping_money'));

        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
        });
        $grid->model()->where('store_id',Admin::user()->store_id);
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
        $show = new Show(ShippingType::findOrFail($id));

        $show->id('Id');
        $show->store_id(trans('shipping_type.store_id'));
        $show->shipping_code(trans('shipping_type.shipping_code'));
        $show->shipping_name(trans('shipping_type.shipping_name'));
        $show->shipping_desc(trans('shipping_type.shipping_desc'));
        $show->status(trans('shipping_type.status'));
        $show->shipping_money(trans('shipping_type.shipping_money'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ShippingType);

//        $form->number('store_id', trans('shipping_type.store_id'));
//        $form->text('shipping_code', trans('shipping_type.shipping_code'));
        $form->display('shipping_name', trans('shipping_type.shipping_name'));
        $form->text('shipping_desc', trans('shipping_type.shipping_desc'));
        $form->switch('status', trans('shipping_type.status'))->default(1);
        $form->decimal('shipping_money', trans('shipping_type.shipping_money'))->default(0.00);
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
