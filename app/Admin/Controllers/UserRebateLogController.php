<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\UserRebateLog;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class UserRebateLogController extends Controller
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
        $grid = new Grid(new UserRebateLog);

        $grid->id('Id');
        $grid->user_id(trans('user_rebate_log.user_id'))->display(function($userId){
            return optional(User::find($userId))->name;
        });
        $grid->before_money(trans('user_rebate_log.before_money'));
//        $grid->frozen_money(trans('user_rebate_log.frozen_money'));
        $grid->change_money(trans('user_rebate_log.change_money'));
        $grid->after_money(trans('user_rebate_log.after_money'));
        $grid->change_time(trans('user_rebate_log.change_time'));
        $grid->desc(trans('user_rebate_log.desc'));
        $grid->order_sn(trans('user_rebate_log.order_sn'));
        $grid->type(trans('user_rebate_log.type'))->using(UserRebateLog::TYPE);
        $grid->source(trans('user_rebate_log.source'))->using(UserRebateLog::SOURCE);
        $grid->is_settle(trans('user_rebate_log.is_settle'))->using(UserRebateLog::IS_SETTLE);

        $grid->filter(function ($filter) {
            $filter->in('type', trans('user_rebate_log.type'))->checkbox(UserRebateLog::TYPE);
            $filter->in('source', trans('user_rebate_log.source'))->checkbox(UserRebateLog::SOURCE);
            $filter->in('is_settle', trans('user_rebate_log.is_settle'))->checkbox(UserRebateLog::IS_SETTLE);
            $filter->like('order_sn', trans('user_rebate_log.order_sn'));
            $filter->between('change_time', trans('user_rebate_log.change_time'))->datetime();
        });

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->model()->orderBy('id','desc');

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
        $show = new Show(UserRebateLog::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('user_rebate_log.user_id'));
        $show->before_money(trans('user_rebate_log.before_money'));
        $show->frozen_money(trans('user_rebate_log.frozen_money'));
        $show->change_money(trans('user_rebate_log.change_money'));
        $show->after_money(trans('user_rebate_log.after_money'));
        $show->change_time(trans('user_rebate_log.change_time'));
        $show->desc(trans('user_rebate_log.desc'));
        $show->order_sn(trans('user_rebate_log.order_sn'));
        $show->type(trans('user_rebate_log.type'));
        $show->source(trans('user_rebate_log.source'));
        $show->is_settle(trans('user_rebate_log.is_settle'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserRebateLog);

        $form->number('user_id', trans('user_rebate_log.user_id'));
        $form->decimal('before_money', trans('user_rebate_log.before_money'))->default(0.00);
        $form->decimal('frozen_money', trans('user_rebate_log.frozen_money'))->default(0.00);
        $form->decimal('change_money', trans('user_rebate_log.change_money'))->default(0.00);
        $form->decimal('after_money', trans('user_rebate_log.after_money'))->default(0.00);
        $form->datetime('change_time', trans('user_rebate_log.change_time'))->default(date('Y-m-d H:i:s'));
        $form->text(trans('user_rebate_log.desc'), trans('user_rebate_log.desc'));
        $form->text('order_sn', trans('user_rebate_log.order_sn'));
        $form->switch(trans('user_rebate_log.type'), trans('user_rebate_log.type'));
        $form->switch(trans('user_rebate_log.source'), trans('user_rebate_log.source'));
        $form->switch('is_settle', trans('user_rebate_log.is_settle'));
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
