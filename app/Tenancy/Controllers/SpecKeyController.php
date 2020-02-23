<?php

namespace App\Tenancy\Controllers;

use App\Models\Category;
use App\Models\SpecKey;
use App\Http\Controllers\Controller;
use App\Models\SpecValue;
use App\Models\Store;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class SpecKeyController extends Controller
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
        $grid = new Grid(new SpecKey);

        $grid->id('Id');
        $grid->cat1(trans('spec_key.cat1'))->display(function($cat){
            if($category = Category::find($cat)){
                return $category->name;
            }
        });
        $grid->cat2(trans('spec_key.cat2'))->display(function($cat){
            if($category = Category::find($cat)){
                return $category->name;
            }
        });
//        $grid->cat3(trans('spec_key.cat3'))->display(function($cat){
//            if($category = Category::find($cat)){
//                return $category->name;
//            }
//        });
        $grid->spec_name(trans('spec_key.spec_name'));
        $grid->column('attribute_value_list','属性值')->display(function(){
            $spec_value = DB::table('spec_value')->where('spec_key_id',$this->id)->pluck('spec_value')->toArray();
            $value = implode(',',$spec_value);
            return $value;
        });
        $grid->weigh(trans('spec_key.weigh'));
//        dd(Admin::user()->store_id);
        $grid->model()->where('store_id',Admin::user()->store_id)->orderBy('id','desc');

        $grid->filter(function($filter){
            $filter->where(function($query){
                $query->where('cat1',"{$this->input}")->orWhere('cat2',"{$this->input}")->orWhere('cat3',"{$this->input}");
            },'分类')->select(Category::selectOptions());
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
        $show = new Show(SpecKey::findOrFail($id));

        $show->id('Id');
        $show->cat1(trans('spec_key.cat1'));
        $show->cat2(trans('spec_key.cat2'));
        $show->cat3(trans('spec_key.cat3'));
        $show->store_id(trans('spec_key.store_id'));
        $show->spec_name(trans('spec_key.spec_name'));
        $show->weigh(trans('spec_key.weigh'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SpecKey);

        $form->select('cat1', trans('spec_key.cat1'))->options(
            Category::where('pid' , 0)->pluck('name' , 'id')
        )->load('cat2','/api/backend/getChildCategory');
        $form->select('cat2', trans('spec_key.cat2'))->options(function($id){
            return Category::where('id' , $id)->pluck('name' , 'id');//回显
        })->load('cat3','/api/backend/getChildCategory');
//        $form->select('cat3', trans('spec_key.cat3'))->options(function($id){
//            return Category::where('id' , $id)->pluck('name' , 'id');//回显
//        });

        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->text('spec_name', trans('spec_key.spec_name'));
        $form->number('weigh', trans('spec_key.weigh'))->default(0);
        $form->hasMany('spec_value', '规格值' , function (Form\NestedForm $form) {
            $form->text('spec_value','规格值');
            $form->number('weigh', trans('spec_value.weigh'))->default(0);
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
