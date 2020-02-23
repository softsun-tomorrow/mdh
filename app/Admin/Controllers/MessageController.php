<?php

namespace App\Admin\Controllers;

use App\Models\Message;
use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class MessageController extends Controller
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
        $grid = new Grid(new Message);

        $grid->id('Id');
//        $grid->admin_id(trans('message.admin_id'));
//        $grid->store_id(trans('message.store_id'));
        $grid->message(trans('message.message'));
//        $grid->type(trans('message.type'));
//        $grid->category(trans('message.category'));
//        $grid->updated_at(trans('message.updated_at'));
        $grid->created_at(trans('message.created_at'));
//        $grid->data(trans('message.data'));

        $grid->model()->where('type',1)->orderBy('id','desc');
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
        $show = new Show(Message::findOrFail($id));

        $show->id('Id');
//        $show->admin_id(trans('message.admin_id'));
//        $show->store_id(trans('message.store_id'));
        $show->message(trans('message.message'));
//        $show->type(trans('message.type'));
//        $show->category(trans('message.category'));
//        $show->updated_at(trans('message.updated_at'));
//        $show->created_at(trans('message.created_at'));
//        $show->data(trans('message.data'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Message);
        $form->hidden('admin_id')->default(Admin::user()->id);
        $form->hidden('type')->default(1);

//        $form->number('admin_id', trans('message.admin_id'));
//        $form->number('store_id', trans('message.store_id'));
        $form->textarea('message', trans('message.message'));
//        $form->select(trans('message.type'), trans('message.type'));
//        $form->select(trans('message.category'), trans('message.category'));
//        $form->textarea(trans('message.data'), trans('message.data'));
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
