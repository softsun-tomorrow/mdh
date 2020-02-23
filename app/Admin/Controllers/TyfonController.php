<?php

namespace App\Admin\Controllers;

use App\Models\Config;
use App\Models\Goods;
use App\Models\Store;
use App\Models\Tyfon;
use App\Http\Controllers\Controller;
use App\Models\TyfonCategory;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\MessageBag;


class TyfonController extends Controller
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
            ->description('')
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
        $grid = new Grid(new Tyfon);

        $grid->id('Id');
        $grid->title(trans('tyfon.title'));
        $grid->content(trans('tyfon.content'))->limit(20);
        $grid->images(trans('tyfon.images'))->image('',50,50);

        $grid->click_num(trans('tyfon.click_num'));
        $grid->created_at(trans('tyfon.created_at'));

        $grid->model()->orderBy('id','desc');

        $grid->filter(function($filter){

            $filter->like('title',trans('tyfon.title'));
            $filter->between('created_at',trans('admin.created_at'))->datetime();
        });

        $grid->actions(function($actions){
            $actions->disableView();
            $id = $actions->getKey();
            $a = "/admin/tyfon_comment?tyfon_id={$id}";
//                dump($a);exit;
            $actions->prepend('<a href='.$a.' > 评论 <i class=""></i>&nbsp;</a>');
        });

        $grid->disableCreateButton();
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
        $show = new Show(Tyfon::findOrFail($id));
        $show->id('Id');
        $show->commentable_id(trans('tyfon.commentable_id'));
        $show->title(trans('tyfon.title'));
        $show->content(trans('tyfon.content'));
        $show->images(trans('tyfon.images'))->unescape()->as(function($images){
            $str = '';
            foreach($images as $image){
                $str .= '<img src="/uploads/'. $image .'"/>';
            }
            return $str;
        });
        $show->click_num(trans('tyfon.click_num'));
        $show->created_at(trans('tyfon.created_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Tyfon);
        $form->text('title', trans('tyfon.title'))->rules('required');
        $form->textarea('content', trans('tyfon.content'))->rules('required');
        $form->multipleImage('images', trans('tyfon.images'))->removable()->help(trans('admin.ctrl'))->uniqueName();
        $form->number('click_num', trans('tyfon.click_num'));
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
