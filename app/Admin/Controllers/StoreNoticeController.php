<?php

namespace App\Admin\Controllers;

use App\Models\StoreNotice;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class StoreNoticeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '店铺公告';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StoreNotice);

        $grid->column('id', trans('store_notice.id'));
        $grid->column('title', trans('store_notice.Title'));
        $grid->column('content', trans('store_notice.Content'));
        $grid->column('created_at', trans('store_notice.Created At'));
        //$grid->column('updated_at', __('Updated at'));

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
        $show = new Show(StoreNotice::findOrFail($id));

        $show->field('id', trans('store_notice.id'));
        $show->field('title', trans('store_notice.Title'));
        $show->field('content', trans('store_notice.Content'));
        $show->field('created_at', trans('store_notice.Created At'));
//        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StoreNotice);

        $form->text('title', trans('store_notice.Title'));
        $form->textarea('content', trans('store_notice.Content'));

        return $form;
    }
}
