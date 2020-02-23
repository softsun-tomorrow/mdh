<?php

namespace App\Admin\Controllers;

use App\Models\AccountLog;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use App\Models\User;

class AccountLogController extends Controller
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
        $grid = new Grid(new AccountLog);

        $grid->id('Id');
        $grid->user_id(trans('account_log.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
//        $grid->before_money(trans('account_log.before_money'));
//        $grid->frozen_money(trans('account_log.frozen_money'));
        $grid->change_money(trans('account_log.change_money'));
//        $grid->after_money(trans('account_log.after_money'));
        $grid->desc(trans('account_log.desc'));
        $grid->order_sn(trans('account_log.order_sn'));
        $grid->type(trans('account_log.type'))->using(AccountLog::getTypeText());
        $grid->source(trans('account_log.source'))->using(AccountLog::getSourceText());
        $grid->change_time(trans('account_log.change_time'));

        $grid->model()->orderBy('id','desc');

        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->disableExport();
//        $grid->disableTools();
        $grid->filter(function ($filter) {
            $filter->in('type', trans('account_log.type'))->checkbox(AccountLog::getTypeText());
            $filter->in('source', trans('account_log.source'))->checkbox(AccountLog::getSourceText());
            $filter->like('order_sn', trans('account_log.order_sn'));
            $filter->between('change_time', trans('account_log.change_time'))->datetime();
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
        $show = new Show(AccountLog::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('account_log.user_id'));
        $show->before_money(trans('account_log.before_money'));
        $show->frozen_money(trans('account_log.frozen_money'));
        $show->change_money(trans('account_log.change_money'));
        $show->after_money(trans('account_log.after_money'));
        $show->change_time(trans('account_log.change_time'));
        $show->desc(trans('account_log.desc'));
        $show->order_sn(trans('account_log.order_sn'));
        $show->type(trans('account_log.type'));
        $show->source(trans('account_log.source'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AccountLog);

        $form->number('user_id', trans('account_log.user_id'));
        $form->decimal('before_money', trans('account_log.before_money'))->default(0.00);
        $form->decimal('frozen_money', trans('account_log.frozen_money'))->default(0.00);
        $form->decimal('change_money', trans('account_log.change_money'))->default(0.00);
        $form->decimal('after_money', trans('account_log.after_money'))->default(0.00);
        $form->datetime('change_time', trans('account_log.change_time'))->default(date('Y-m-d H:i:s'));
        $form->text(trans('account_log.desc'), trans('account_log.desc'));
        $form->text('order_sn', trans('account_log.order_sn'));
        $form->switch(trans('account_log.type'), trans('account_log.type'));
        $form->switch(trans('account_log.source'), trans('account_log.source'));

        return $form;
    }
}
