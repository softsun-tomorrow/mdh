<?php

namespace App\Tenancy\Controllers;

use App\Models\Goods;
use App\Models\TeamActivity;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

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
        $grid->goods_id(trans('team_activity.goods_id'))->display(function($id){
            if($model = Goods::find($id)) return $model->name;
        });
        $grid->time_limit(trans('team_activity.time_limit'));
        $grid->price(trans('team_activity.price'));
        $grid->needer(trans('team_activity.needer'));


        $grid->bonus(trans('team_activity.bonus'));
        $grid->buy_limit(trans('team_activity.buy_limit'));
        $grid->sales_sum(trans('team_activity.sales_sum'));
//        $grid->virtual_num(trans('team_activity.virtual_num'));
//        $grid->share_title(trans('team_activity.share_title'));
//        $grid->share_desc(trans('team_activity.share_desc'));
//        $grid->share_img(trans('team_activity.share_img'));

//        $grid->weigh(trans('team_activity.weigh'));
//        $grid->is_recommend(trans('team_activity.is_recommend'));
        $grid->status(trans('team_activity.status'))->using(TeamActivity::getStatusArr());

        $grid->model()->where('store_id',Admin::user()->store_id)->orderBy('id','desc');
        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableDelete();
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
        $show->share_title(trans('team_activity.share_title'));
        $show->share_desc(trans('team_activity.share_desc'));
        $show->share_img(trans('team_activity.share_img'));
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
        $is_edit = request()->route('team_activity') ? 1 : 0;

        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->hidden('status',1);

        $form->text('title', trans('team_activity.title'))->rules('required');
        $form->textarea('description', trans('team_activity.description'));
        $form->number('time_limit', trans('team_activity.time_limit'))->min(120)->default(300)->help('开团后有效时间范围(单位：秒)');
        $form->decimal('price', trans('team_activity.price'))->rules('required')->help('商品拼团价格为该商品参加活动时的拼团价格,必须是0.01~1000000之间的数字(单位：元)');
        $form->number('needer', trans('team_activity.needer'))->min(2)->default(2)->help('需要多少人拼团才能成功(单位：人)');

        if(request()->route('team_activity')){
            //编辑
            $form->select('goods_id', trans('flash_sale.goods_id'))->options('/tenancy/api/editGoods')->readOnly();
        }else{
            $form->select('goods_id', trans('flash_sale.goods_id'))->options('/tenancy/api/goods')->required();
        }


        $form->decimal('bonus', trans('team_activity.bonus'))->default(0.00);
        $form->number('buy_limit', trans('team_activity.buy_limit'))->default(0)->help('限制购买商品个数,0为不限制(单位：个)');
//        $form->number('sales_sum', trans('team_activity.sales_sum'));
//        $form->number('virtual_num', trans('team_activity.virtual_num'));
        $form->text('share_title', trans('team_activity.share_title'));
        $form->text('share_desc', trans('team_activity.share_desc'));
        $form->image('share_img', trans('team_activity.share_img'));
        $form->number('weigh', trans('team_activity.weigh'))->default(TeamActivity::max('weigh')+1);
//        $form->switch('is_recommend', trans('team_activity.is_recommend'));
//        $form->radio('status', trans('team_activity.status'));

        $form->saved(function(Form $form){
            //活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
            DB::table('goods')->where('id',$form->model()->goods_id)->update(['prom_type' =>2, 'prom_id' => $form->model()->id ]);
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
