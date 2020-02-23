<?php

namespace App\Tenancy\Controllers;

use App\Models\StoreGoodsCategory;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use ModelForm;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;


class StoreGoodsCategoryController extends Controller
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
            ->row(function (Row $row) {
                $row->column(6, StoreGoodsCategory::tree(function($tree){
                    $tree->query(function($model){
                        return $model->where('store_id',Admin::user()->store_id);
                    });
                }));
//                dd(StoreGoodsCategory::getFirstLevel(Admin::user()->store_id));
                $row->column(6, function (Column $column) {
                    $form = new \Encore\Admin\Widgets\Form();
                    $form->action(admin_base_path('store_goods_category'));
                    $form->hidden('store_id')->default(Admin::user()->store_id);
                    $form->select('pid', trans('category.pid'))->options('/tenancy/api/getTopStoreGoodsCategory');
                    $form->text('name', trans('category.name'));
                    $form->number('weigh', trans('category.weigh'))->default(StoreGoodsCategory::max('weigh') + 1);
                    $form->hidden('_token')->default(csrf_token());

                    $column->append((new Box(trans('admin.new'), $form))->style('success'));
                });
            });
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
        $grid = new Grid(new StoreGoodsCategory);

        $grid->id('Id');
        $grid->pid(trans('category.pid'));
        $grid->name(trans('category.name'));
        $grid->weigh(trans('category.weigh'));
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
        $show = new Show(StoreGoodsCategory::findOrFail($id));

        $show->id('Id');
        $show->pid(trans('category.pid'));
        $show->name(trans('category.name'));
        $show->weigh(trans('category.weigh'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StoreGoodsCategory);

        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->select('pid', trans('category.pid'))->options('/tenancy/api/getTopStoreGoodsCategory');
        $form->text('name', trans('category.name'));
        $form->number('weigh', trans('category.weigh'))->default(StoreGoodsCategory::max('weigh') + 1);
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
