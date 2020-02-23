<?php

namespace App\Tenancy\Controllers;


use App\Models\Lottery;
use App\Models\LotteryFollow;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class LotteryFollowController extends Controller
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
        $grid = new Grid(new LotteryFollow);

        $grid->id('Id');
        $grid->user_id(trans('lottery_follow.user_id'))->display(function($userId){
            return optional(User::find($userId))->name;
        });
        $grid->lottery_id(trans('lottery_follow.lottery_id'))->display(function($id){
            return optional(Lottery::find($id))->title;
        });
        $grid->created_at(trans('lottery_follow.created_at'));
        $grid->status(trans('lottery_follow.status'))->using(LotteryFollow::STATUS);;
        $grid->order_id(trans('lottery_follow.order_id'))->display(function($orderId){
            return optional(Order::find($orderId))->order_sn;
        });
        $grid->lottery_time(trans('lottery_follow.lottery_time'));
//        $grid->sku('Sku');
//        $grid->spec_key('Spec key');
//        $grid->goods_id(trans('lottery_follow.goods_id'));
//        $grid->address_id('Address id');

        $grid->filter(function($filter){
            $filter->equal('lottery_id', trans('lottery_follow.lottery_id'))->select('/admin/api/lottery');

            $filter->where(function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%")->orWhere('mobile', 'like', "%{$this->input}%");
                });
            }, '用户昵称或手机号码');

            $filter->equal('status', trans('lottery_follow.status'))->radio(LotteryFollow::STATUS);
        });

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->model()->where(function ($query){
            $query->whereHas('lottery',function($query){
                $query->where('store_id', Admin::user()->store_id);
            });
        })->orderBy('id','desc');
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
        $show = new Show(LotteryFollow::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('lottery_follow.user_id'));
        $show->lottery_id(trans('lottery_follow.lottery_id'));
        $show->created_at(trans('lottery_follow.created_at'));
        $show->status(trans('lottery_follow.status'));
        $show->order_id(trans('lottery_follow.order_id'));
        $show->lottery_time(trans('lottery_follow.lottery_time'));
        $show->sku('Sku');
        $show->spec_key('Spec key');
        $show->goods_id(trans('lottery_follow.goods_id'));
        $show->address_id('Address id');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LotteryFollow);

        $form->number('user_id', trans('lottery_follow.user_id'));
        $form->number('lottery_id', trans('lottery_follow.lottery_id'));
        $form->switch(trans('lottery_follow.status'), trans('lottery_follow.status'));
        $form->number('order_id', trans('lottery_follow.order_id'));
        $form->datetime('lottery_time', trans('lottery_follow.lottery_time'))->default(date('Y-m-d H:i:s'));
        $form->text('sku', 'Sku');
        $form->text('spec_key', 'Spec key');
        $form->number('goods_id', trans('lottery_follow.goods_id'));
        $form->number('address_id', 'Address id');
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
