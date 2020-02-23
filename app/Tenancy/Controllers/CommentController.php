<?php

namespace App\Tenancy\Controllers;

use App\Models\Comment;
use App\Http\Controllers\Controller;
use App\Models\Goods;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

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
        $grid->goods_id(trans('comment.goods_id'))->display(function($goodsId){
            if($goods = Goods::find($goodsId)) return $goods->name;
        });
//        $grid->order_id(trans('comment.order_id'));
//        $grid->order_goods_id(trans('comment.order_goods_id'));
//        $grid->store_id(trans('comment.store_id'));
        $grid->user_id(trans('comment.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
        $grid->content(trans('comment.content'));
        $grid->created_at(trans('comment.created_at'));
        $grid->is_show(trans('comment.is_show'))->switch(Comment::getIsShowArr());
        $grid->images(trans('comment.images'))->image('',50,50);
//        $grid->spec_key_name(trans('comment.spec_key_name'));
        $grid->price_rank(trans('comment.price_rank'));
        $grid->service_rank(trans('comment.service_rank'));
        $grid->goods_rank(trans('comment.goods_rank'));
//        $grid->zan_num(trans('comment.zan_num'));
//        $grid->zan_userid(trans('comment.zan_userid'));

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->model()->where('store_id',Admin::user()->store_id)->orderBy('id','desc');

        $grid->filter(function($filter){
            $filter->where(function($query){
                $query->whereHas('user',function($query){
                    $query->where('name','like', "%{$this->input}%")->orWhere('mobile','like',"%{$this->input}%");
                });
            },'用户名或手机号');

            $filter->equal('goods_id',trans('comment.goods_id'))->select('api/goods');
            $filter->between('created_at',trans('admin.created_at'))->datetime();
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
        $show = new Show(Comment::findOrFail($id));

        $show->id('Id');
        $show->goods_id(trans('comment.goods_id'));
        $show->order_id(trans('comment.order_id'));
        $show->order_goods_id(trans('comment.order_goods_id'));
        $show->store_id(trans('comment.store_id'));
        $show->user_id(trans('comment.user_id'));
        $show->content(trans('comment.content'));
        $show->created_at(trans('comment.created_at'));
        $show->is_show(trans('comment.is_show'));
        $show->images(trans('comment.images'));
        $show->spec_key_name(trans('comment.spec_key_name'));
        $show->price_rank(trans('comment.price_rank'));
        $show->service_rank(trans('comment.service_rank'));
        $show->goods_rank(trans('comment.goods_rank'));
        $show->zan_num(trans('comment.zan_num'));
        $show->zan_userid(trans('comment.zan_userid'));
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

        $form->number('goods_id', trans('comment.goods_id'));
        $form->number('order_id', trans('comment.order_id'));
        $form->number('order_goods_id', trans('comment.order_goods_id'));
        $form->number('store_id', trans('comment.store_id'));
        $form->number('user_id', trans('comment.user_id'));
        $form->textarea(trans('comment.content'), trans('comment.content'));
//        $form->switch('is_show', trans('comment.is_show'))->default(1);
        $form->textarea(trans('comment.images'), trans('comment.images'));
        $form->text('spec_key_name', trans('comment.spec_key_name'));
        $form->switch('price_rank', trans('comment.price_rank'))->default(5);
        $form->switch('service_rank', trans('comment.service_rank'))->default(5);
        $form->switch('goods_rank', trans('comment.goods_rank'))->default(5);
        $form->number('zan_num', trans('comment.zan_num'));
        $form->text('zan_userid', trans('comment.zan_userid'));

        return $form;
    }
}
