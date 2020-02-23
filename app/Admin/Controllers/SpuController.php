<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Spu;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class SpuController extends Controller
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
        $grid = new Grid(new Spu);

        $grid->id('Id');
        $grid->name(trans('spu.name'));
        $grid->cat2(trans('spu.cat2'))->display(function($cat2){
            return optional(Category::find($cat2))->name;
        });
//        $grid->values(trans('spu.values'));
//        $grid->weigh(trans('spu.weigh'));


        $grid->actions(function($actions){
            $actions->disableView();
        });
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
        $show = new Show(Spu::findOrFail($id));

        $show->id('Id');
        $show->name(trans('spu.name'));
        $show->cat2(trans('spu.cat2'));
        $show->values(trans('spu.values'));
//        $show->weigh(trans('spu.weigh'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Spu);

        $form->text('name', trans('spu.name'))->rules('required');
        $form->select('cat1', trans('goods.cat1'))->options(function (){
            return Category::where('pid' , 0)->pluck('name' , 'id');
        }
        )->load('cat2','/api/backend/getChildCategory')->rules('required');
        $form->select('cat2', trans('goods.cat2'))->options(function($id){
            return Category::where('id' , $id)->pluck('name' , 'id');//回显
        })->rules('required');
//        $form->textarea('values', trans('spu.values'))->rules('required')->help('多个属性值需要用英文逗号","隔开,商家发布商品是即可下拉选择属性值');
//        $form->number('weigh', trans('spu.weigh'))->default(Spu::max('weigh')+1);

        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
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
