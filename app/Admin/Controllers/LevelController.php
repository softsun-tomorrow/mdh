<?php

namespace App\Admin\Controllers;

use App\Models\Level;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class LevelController extends Controller
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
        $grid = new Grid(new Level);

        $grid->id('Id');
        $grid->name(trans('level.name'));
        $grid->child_count(trans('level.child_count'));
        $grid->train_count(trans('level.train_count'));
        $grid->team_count(trans('level.team_count'));
        $grid->rebate_money(trans('level.rebate_money'));
        $grid->rebate_coin(trans('level.rebate_coin'));
        $grid->child_invite_money(trans('level.child_invite_money'));
        $grid->team_rate(trans('level.team_rate'));

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableColumnSelector();
        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableDelete();
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
        $show = new Show(Level::findOrFail($id));

        $show->id('Id');
        $show->name(trans('level.name'));
        $show->child_count(trans('level.child_count'));
        $show->train_count(trans('level.train_count'));
        $show->team_count(trans('level.team_count'));
        $show->rebate_money(trans('level.rebate_money'));
        $show->rebate_coin(trans('level.rebate_coin'));
        $show->child_invite_money(trans('level.child_invite_money'));
        $show->team_rate(trans('level.team_rate'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Level);

        $form->text('name', trans('level.name'));
        $form->number('child_count', trans('level.child_count'))->help('0为不限制');
        $form->number('train_count', trans('level.train_count'))->help('0为不限制');
        $form->number('team_count', trans('level.team_count'))->help('0为不限制');
        $form->decimal('rebate_money', trans('level.rebate_money'))->default(0.00);
        $form->number('rebate_coin', trans('level.rebate_coin'));
        $form->decimal('child_invite_money', trans('level.child_invite_money'))->default(0.00);
        $form->rate('team_rate', trans('level.team_rate'))->default(0.00);
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
