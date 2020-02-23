<?php

namespace App\Admin\Controllers;

use App\Models\ExpenseLog;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class ExpenseLogController extends Controller
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
        $grid = new Grid(new ExpenseLog);
        $grid->footer(function ($query) {
            $in = DB::table('expense_log')->where('type', 0)->sum('money');
            $out = DB::table('expense_log')->where('type', 1)->sum('money');
            return "<span style='padding: 10px;'>总收入 ： $in</span><span style='padding: 10px;'>总支出 ：".abs($out)." </span>";

        });

        $grid->id('Id');
//        $grid->admin_id(trans('expense_log.admin_id'));
        $grid->money(trans('expense_log.money'));
        $grid->type(trans('expense_log.type'))->using(ExpenseLog::getTypeArr());
        $grid->source(trans('expense_log.source'))->using(ExpenseLog::getSourceArr());
//        $grid->expenseable_id(trans('expense_log.expense_id'));
        $grid->order_sn(trans('expense_log.order_sn'));
        $grid->remark(trans('expense_log.remark'));
        $grid->created_at(trans('expense_log.created_at'));


        $grid->filter(function ($filter) {
            $filter->in('type', trans('expense_log.type'))->checkbox(ExpenseLog::getTypeArr());
            $filter->in('source', trans('expense_log.source'))->checkbox(ExpenseLog::getSourceArr());
            $filter->like('order_sn', trans('expense_log.order_sn'));
            $filter->between('created_at', trans('expense_log.created_at'))->datetime();
        });

        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->model()->orderBy('id', 'desc');
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
        $show = new Show(ExpenseLog::findOrFail($id));

        $show->id('Id');
        $show->admin_id(trans('expense_log.admin_id'));
        $show->money(trans('expense_log.money'));
        $show->type(trans('expense_log.type'));
        $show->source(trans('expense_log.source'));
        $show->created_at(trans('expense_log.created_at'));
        $show->expenseable_id(trans('expense_log.expense_id'));
        $show->order_sn(trans('expense_log.order_sn'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ExpenseLog);

        $form->number('admin_id', trans('expense_log.admin_id'));
        $form->decimal(trans('expense_log.money'), trans('expense_log.money'))->default(0.00);
        $form->switch(trans('expense_log.type'), trans('expense_log.type'));
        $form->switch(trans('expense_log.source'), trans('expense_log.source'));
        $form->number('expenseable_id', trans('expense_log.expense_id'));
        $form->text('order_sn', trans('expense_log.order_sn'));
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
