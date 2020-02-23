<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Exchange;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ExchangeController extends Controller
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
        $grid = new Grid(new Exchange);

        $grid->id('Id');
//        $grid->store_id(trans('exchange.store_id'));
        $grid->category_id(trans('exchange.category_id'))->display(function($categoryId){
            if($category = Category::find($categoryId)) return $category->name;
        });

        $grid->name(trans('exchange.name'));
        $grid->image(trans('exchange.image'))->image('',50,50);
        $grid->money(trans('exchange.money'));
        $grid->created_at(trans('admin.created_at'));
        $grid->updated_at(trans('admin.updated_at'));

        $grid->filter(function($filter){
            $filter->equal('category_id',trans('exchange.category_id'))->select('/api/backend/topCategory');
            $filter->like('name',trans('exchange.name'));
        });

        $grid->model()->where('store_id',0)->orderBy('id','desc');

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
        $show = new Show(Exchange::findOrFail($id));

        $show->id('Id');
        $show->store_id(trans('exchange.store_id'));
        $show->category_id(trans('exchange.category_id'));
        $show->name(trans('exchange.name'));
        $show->image(trans('exchange.image'));
        $show->money(trans('exchange.money'));
        $show->created_at(trans('admin.created_at'));
        $show->updated_at(trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Exchange);

//        $form->number('store_id', trans('exchange.store_id'));
        $form->select('category_id', trans('exchange.category_id'))->options('/api/backend/topCategory');
        $form->text('name', trans('exchange.name'));
        $form->image('image', trans('exchange.image'))->uniqueName();
        $form->number('money', trans('exchange.money'))->default(1)->help('兑换所需麦穗数量');
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
