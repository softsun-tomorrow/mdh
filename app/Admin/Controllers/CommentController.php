<?php

namespace App\Admin\Controllers;

use App\Models\Comment;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CommentController extends Controller
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
        $grid = new Grid(new Comment);

        $grid->id('Id');
        $grid->goods_id('Goods id');
        $grid->order_id('Order id');
        $grid->order_goods_id('Order goods id');
        $grid->store_id('Store id');
        $grid->user_id('User id');
        $grid->content('Content');
        $grid->created_at('Created at');
        $grid->is_show('Is show');
        $grid->images('Images');
        $grid->spec_key_name('Spec key name');
        $grid->price_rank('Price rank');
        $grid->service_rank('Service rank');
        $grid->goods_rank('Goods rank');
        $grid->zan_num('Zan num');
        $grid->zan_userid('Zan userid');
        $grid->deleted_at('Deleted at');

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
        $show = new Show(Comment::findOrFail($id));

        $show->id('Id');
        $show->goods_id('Goods id');
        $show->order_id('Order id');
        $show->order_goods_id('Order goods id');
        $show->store_id('Store id');
        $show->user_id('User id');
        $show->content('Content');
        $show->created_at('Created at');
        $show->is_show('Is show');
        $show->images('Images');
        $show->spec_key_name('Spec key name');
        $show->price_rank('Price rank');
        $show->service_rank('Service rank');
        $show->goods_rank('Goods rank');
        $show->zan_num('Zan num');
        $show->zan_userid('Zan userid');
        $show->deleted_at('Deleted at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Comment);

        $form->number('goods_id', 'Goods id');
        $form->number('order_id', 'Order id');
        $form->number('order_goods_id', 'Order goods id');
        $form->number('store_id', 'Store id');
        $form->number('user_id', 'User id');
        $form->textarea('content', 'Content');
        $form->switch('is_show', 'Is show')->default(1);
        $form->textarea('images', 'Images');
        $form->text('spec_key_name', 'Spec key name');
        $form->switch('price_rank', 'Price rank')->default(5);
        $form->switch('service_rank', 'Service rank')->default(5);
        $form->switch('goods_rank', 'Goods rank')->default(5);
        $form->number('zan_num', 'Zan num');
        $form->text('zan_userid', 'Zan userid');
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
