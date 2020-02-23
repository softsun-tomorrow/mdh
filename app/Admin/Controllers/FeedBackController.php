<?php

namespace App\Admin\Controllers;

use App\Models\Feedback;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class FeedBackController extends Controller
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
        $grid = new Grid(new Feedback);

        $grid->id('Id');
        $grid->user_id(trans('feedback.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
        $grid->content(trans('feedback.content'));
//        $grid->replay(trans('feedback.replay'));
//        $grid->is_read(trans('feedback.is_read'));
        $grid->created_at(trans('admin.created_at'));
//        $grid->updated_at('Updated at');
        $grid->imgs(trans('feedback.imgs'))->image('',50,50);

        $grid->disableCreateButton();
        $grid->actions(function($actions){
            $actions->disableEdit();
            $actions->disableDelete();
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
        $show = new Show(Feedback::findOrFail($id));
        $show->id('Id');
        $show->user_id(trans('feedback.user_id'))->as(function($userId){
            if($user = User::find($userId)) return $user->name;

        });
        $show->content(trans('feedback.content'));
//        $show->replay(trans('feedback.replay'));
//        $show->is_read(trans('feedback.is_read'));
        $show->created_at(trans('admin.created_at'));
//        $show->updated_at('Updated at');
        $show->imgs(trans('feedback.imgs'))->unescape()->as(function ($imgs) {

            $html = '';
            foreach($imgs as $k => $v){
                $src = 'http://'. request()->getHost() . '/uploads/' . $v;
                $html .= "<img src='{$src}' />";
            }
            return $html;
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Feedback);

        $form->number('user_id', trans('feedback.user_id'));
        $form->textarea(trans('feedback.content'), trans('feedback.content'));
        $form->textarea(trans('feedback.replay'), trans('feedback.replay'));
        $form->switch('is_read', trans('feedback.is_read'));
        $form->textarea(trans('feedback.imgs'), trans('feedback.imgs'));
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
