<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use App\Models\Store;
use App\Models\TeamActivity;
use App\Models\TeamFound;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class TeamFoundController extends Controller
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
        $grid = new Grid(new TeamFound);

        $grid->id('Id');
        $grid->team_id(trans('team_found.team_id'))->display(function($teamId){
            return optional(TeamActivity::find($teamId))->title;
        })->expand(function ($model) {

            $teamFollows = $model->team_follow()->take(10)->get()->map(function ($teamFollow) {
                return $teamFollow->only(['id', 'follow_user_nickname','follow_time','order_sn','status_text']);
            });

            return new Table(['ID', '团员昵称', '参团时间', '订单', '状态'], $teamFollows->toArray());
        });
        $grid->found_time(trans('team_found.found_time'));
        $grid->found_end_time(trans('team_found.found_end_time'));
//        $grid->user_id(trans('team_found.user_id'))->display(function($userId){
//            return optional(User::find($userId))->name;
//        });
        $grid->nickname(trans('team_found.nickname'));
//        $grid->head_pic(trans('team_found.head_pic'));
        $grid->order_id(trans('team_found.order_id'))->display(function($orderId){
            return optional(Order::find($orderId))->order_sn;
        });
        $grid->join(trans('team_found.join'));
        $grid->need(trans('team_found.need'));
        $grid->price(trans('team_found.price'));
        $grid->goods_price(trans('team_found.goods_price'));
        $grid->status(trans('team_found.status'))->using(TeamFound::STATUS);
//        $grid->bonus_status(trans('team_found.bonus_status'))->using(TeamFound::BONUS_STATUS);
        $grid->store_id(trans('team_found.store_id'))->display(function ($storeId){
            return optional(Store::find($storeId))->shop_name;
        });

        $grid->filter(function($filter){
            $filter->equal('team_id', trans('team_found.team_id'))->select('/admin/api/team');

            $filter->where(function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%")->orWhere('mobile', 'like', "%{$this->input}%");
                });
            }, '团长昵称或手机号码');

            $filter->where(function($query){
                $query->whereHas('store',function($query){
                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
                });
            },'店铺名或手机号');

            $filter->equal('status', trans('team_found.status'))->radio(TeamFound::STATUS);
        });

        $grid->disableCreateButton();
        $grid->disableActions();
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
        $show = new Show(TeamFound::findOrFail($id));

        $show->id('Id');
        $show->team_id(trans('team_found.team_id'));
        $show->found_time(trans('team_found.found_time'));
        $show->found_end_time(trans('team_found.found_end_time'));
        $show->user_id(trans('team_found.user_id'));
        $show->nickname(trans('team_found.nickname'));
        $show->head_pic(trans('team_found.head_pic'));
        $show->order_id(trans('team_found.order_id'));
        $show->join(trans('team_found.join'));
        $show->need(trans('team_found.need'));
        $show->price(trans('team_found.price'));
        $show->goods_price(trans('team_found.goods_price'));
        $show->status(trans('team_found.status'));
        $show->bonus_status(trans('team_found.bonus_status'));
        $show->store_id(trans('team_found.store_id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TeamFound);

        $form->number('team_id', trans('team_found.team_id'));
        $form->datetime('found_time', trans('team_found.found_time'))->default(date('Y-m-d H:i:s'));
        $form->datetime('found_end_time', trans('team_found.found_end_time'))->default(date('Y-m-d H:i:s'));
        $form->number('user_id', trans('team_found.user_id'));
        $form->text(trans('team_found.nickname'), trans('team_found.nickname'));
        $form->text('head_pic', trans('team_found.head_pic'));
        $form->number('order_id', trans('team_found.order_id'));
        $form->number(trans('team_found.join'), trans('team_found.join'))->default(1);
        $form->number(trans('team_found.need'), trans('team_found.need'))->default(1);
        $form->decimal(trans('team_found.price'), trans('team_found.price'))->default(0.00);
        $form->decimal('goods_price', trans('team_found.goods_price'))->default(0.00);
        $form->switch(trans('team_found.status'), trans('team_found.status'));
        $form->switch('bonus_status', trans('team_found.bonus_status'));
        $form->number('store_id', trans('team_found.store_id'));
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
