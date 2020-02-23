<?php

namespace App\Tenancy\Controllers;

use App\Models\StoreShipper;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class StoreShipperController extends Controller
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
        $grid = new Grid(new StoreShipper);

        $grid->id('Id');
//        $grid->store_id(trans('store_shipper.store_id'));
        $grid->shipper_name(trans('store_shipper.shipper_name'));
        $grid->shipper_code(trans('store_shipper.shipper_code'));
        $grid->shipper_desc(trans('store_shipper.shipper_desc'));
        $grid->status(trans('store_shipper.status'))->switch(StoreShipper::getStatusArr());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableFilter();
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
        $show = new Show(StoreShipper::findOrFail($id));

        $show->id('Id');
        $show->store_id(trans('store_shipper.store_id'));
        $show->shipper_name(trans('store_shipper.shipper_name'));
        $show->shipper_code(trans('store_shipper.shipper_code'));
        $show->shipper_desc(trans('store_shipper.shipper_desc'));
        $show->status(trans('store_shipper.status'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StoreShipper);

        $form->number('store_id', trans('store_shipper.store_id'));
        $form->text('shipper_name', trans('store_shipper.shipper_name'));
        $form->text('shipper_code', trans('store_shipper.shipper_code'));
        $form->text('shipper_desc', trans('store_shipper.shipper_desc'));
        $form->switch('status', trans('store_shipper.status'));
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
