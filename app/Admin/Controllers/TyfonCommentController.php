<?php

namespace App\Admin\Controllers;

use App\Models\Tyfon;
use App\Models\TyfonComment;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class TyfonCommentController extends Controller
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
        $grid = new Grid(new TyfonComment);

        $grid->id('Id');
        $grid->user_id(trans('tyfon_comment.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
        $grid->tyfon_id(trans('tyfon_comment.tyfon_id'))->display(function($tyfonId){
            if($tyfon = Tyfon::find($tyfonId)) return $tyfon->title;
        });
        $grid->content(trans('tyfon_comment.content'));

        $grid->created_at(trans('tyfon_comment.created_at'));

        $grid->filter(function($filter){
            $filter->between('created_at',trans('admin.created_at'))->datetime();
        });

        $grid->disableCreateButton();
        $grid->model()->where('tyfon_id',request()->get('tyfon_id'))->orderBy('id','desc');
        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableEdit();
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
        $show = new Show(TyfonComment::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('tyfon_comment.user_id'));
        $show->tyfon_id(trans('tyfon_comment.tyfon_id'));
        $show->content(trans('tyfon_comment.content'));
        $show->created_at(trans('tyfon_comment.created_at'));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TyfonComment);

//        $form->number('user_id', trans('tyfon_comment.user_id'));
//        $form->number('tyfon_id', trans('tyfon_comment.tyfon_id'));
        $form->display('content', trans('tyfon_comment.content'));

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
