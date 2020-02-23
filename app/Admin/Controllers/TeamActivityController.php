<?php

namespace App\Admin\Controllers;

use App\Models\Goods;
use App\Models\TeamActivity;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class TeamActivityController extends Controller
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
        $grid = new Grid(new TeamActivity);

        $grid->id(trans('team_activity.id'));
        $grid->title(trans('team_activity.title'));
        $grid->time_limit(trans('team_activity.time_limit'));
        $grid->price(trans('team_activity.price'));
        $grid->needer(trans('team_activity.needer'));
        $grid->goods_id(trans('team_activity.goods_id'))->display(function($id){
            if($model = Goods::find($id)) return $model->name;
        });

        $grid->bonus(trans('team_activity.bonus'));
        $grid->buy_limit(trans('team_activity.buy_limit'));
        $grid->sales_sum(trans('team_activity.sales_sum'));
//        $grid->virtual_num(trans('team_activity.virtual_num'));
//        $grid->share_title(trans('team_activity.share_title'));
//        $grid->share_desc(trans('team_activity.share_desc'));
//        $grid->share_img(trans('team_activity.share_img'));

        $grid->weigh(trans('team_activity.weigh'));
        $grid->is_recommend(trans('team_activity.is_recommend'))->switch();
//        $grid->status(trans('team_activity.status'))->editable('select',TeamActivity::getStatusArr());
        $grid->status(trans('team_activity.status'))->using(TeamActivity::getStatusArr());

        $grid->filter(function($filter){

            $filter->like('title', trans('team_activity.title'));
            $filter->where(function($query){
                $query->whereHas('store',function($query){
                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
                });
            },'店铺名或手机号');

            $filter->equal('status', trans('team_activity.status'))->radio(TeamActivity::getStatusArr());
            $filter->equal('is_rec', '是否推荐')->radio(TeamActivity::getIsRecArr());
        });

        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->disableCreateButton();
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
        $show = new Show(TeamActivity::findOrFail($id));

        $show->id(trans('team_activity.id'));
        $show->title(trans('team_activity.title'));
        $show->team_type(trans('team_activity.team_type'));
        $show->time_limit(trans('team_activity.time_limit'));
        $show->price(trans('team_activity.price'));
        $show->needer(trans('team_activity.needer'));
        $show->goods_name(trans('team_activity.goods_name'));
        $show->goods_id(trans('team_activity.goods_id'));
        $show->item_id('Item id');
        $show->bonus(trans('team_activity.bonus'));
        $show->stock_limit(trans('team_activity.stock_limit'));
        $show->buy_limit(trans('team_activity.buy_limit'));
        $show->sales_sum(trans('team_activity.sales_sum'));
        $show->virtual_num(trans('team_activity.virtual_num'));
//        $show->share_title(trans('team_activity.share_title'));
//        $show->share_desc(trans('team_activity.share_desc'));
//        $show->share_img(trans('team_activity.share_img'));
        $show->store_id(trans('team_activity.store_id'));
        $show->weigh(trans('team_activity.weigh'));
        $show->is_recommend(trans('team_activity.is_recommend'));
        $show->status(trans('team_activity.status'));
        $show->is_lottery('Is lottery');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TeamActivity);

        $form->text('title', trans('team_activity.title'))->readOnly();
        $form->textarea('description', trans('team_activity.description'))->readOnly();

        $form->number('time_limit', trans('team_activity.time_limit'))->min(120)->default(300)->help('开团后有效时间范围(单位：秒)')->readOnly();
        $form->decimal('price', trans('team_activity.price'))->readOnly()->help('商品拼团价格为该商品参加活动时的拼团价格,必须是0.01~1000000之间的数字(单位：元)');
        $form->number('needer', trans('team_activity.needer'))->min(2)->default(2)->help('需要多少人拼团才能成功(单位：人)')->readOnly();
        $form->select('goods_id', trans('team_activity.goods_id'))->options('/admin/api/goods')->readOnly();
        $form->decimal('bonus', trans('team_activity.bonus'))->default(0.00)->readOnly();
        $form->number('buy_limit', trans('team_activity.buy_limit'))->default(0)->help('限制购买商品个数,0为不限制(单位：个)')->readOnly();
//        $form->number('sales_sum', trans('team_activity.sales_sum'));
//        $form->number('virtual_num', trans('team_activity.virtual_num'));
//        $form->text('share_title', trans('team_activity.share_title'));
//        $form->text('share_desc', trans('team_activity.share_desc'));
//        $form->image('share_img', trans('team_activity.share_img'));
        $form->number('weigh', trans('team_activity.weigh'))->default(TeamActivity::max('weigh')+1);
        $form->switch('is_recommend', trans('team_activity.is_recommend'));
        $form->radio('status', trans('team_activity.status'))->options(TeamActivity::getStatusArr())->required()->default(0);

        $form->ignore('goods_id');

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
