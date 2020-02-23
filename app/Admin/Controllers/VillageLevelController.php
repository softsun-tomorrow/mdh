<?php

namespace App\Admin\Controllers;

use App\Models\VillageLevel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class VillageLevelController extends Controller
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
        $grid = new Grid(new VillageLevel);

        $grid->id('Id');
        $grid->name(trans('village_level.name'));
//        $grid->shop_decrement(trans('village_level.shop_decrement'));
        $grid->rebate(trans('village_level.rebate'));
        $grid->price(trans('village_level.price'));

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
        $show = new Show(VillageLevel::findOrFail($id));

        $show->id('Id');
        $show->name(trans('village_level.name'));
        $show->shop_decrement(trans('village_level.shop_decrement'));
        $show->rebate(trans('village_level.rebate'));
        $show->price(trans('village_level.price'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new VillageLevel);

        $form->text('name', trans('village_level.name'));
//        $form->decimal('shop_decrement', trans('village_level.shop_decrement'))->default(0.00);
        $form->decimal('rebate', trans('village_level.rebate'))->default(0.00);
        $form->decimal('price', trans('village_level.price'))->default(0.00);
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
