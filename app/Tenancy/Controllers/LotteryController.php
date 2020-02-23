<?php

namespace App\Tenancy\Controllers;

use App\Models\Goods;
use App\Models\Lottery;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class LotteryController extends Controller
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
        $grid = new Grid(new Lottery);

        $grid->id('Id');
        $grid->title(trans('lottery.title'));
        $grid->goods_id(trans('lottery.goods_id'))->display(function($id){
            if($model = Goods::find($id)) return $model->name;
        });
//        $grid->store_id(trans('lottery.store_id'));

//        $grid->goods_price(trans('lottery.goods_price'));
        $grid->price(trans('lottery.price'));
        $grid->needer(trans('lottery.needer'));
        $grid->join_num(trans('lottery.join_num'));
//        $grid->weigh(trans('lottery.weigh'));
//        $grid->is_recommend(trans('lottery.is_recommend'));
        $grid->status(trans('lottery.status'))->using(Lottery::getStatusArr());

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
        $show = new Show(Lottery::findOrFail($id));

        $show->id('Id');
        $show->goods_id(trans('lottery.goods_id'));
        $show->store_id(trans('lottery.store_id'));
        $show->title(trans('lottery.title'));
        $show->goods_price(trans('lottery.goods_price'));
        $show->price(trans('lottery.price'));
        $show->needer(trans('lottery.needer'));
        $show->join_num(trans('lottery.join_num'));
        $show->weigh(trans('lottery.weigh'));
        $show->is_recommend(trans('lottery.is_recommend'));
        $show->status(trans('lottery.status'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Lottery);
        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->hidden('status',0);
        if(request()->route('lottery')){
            //编辑
            $form->select('goods_id', trans('flash_sale.goods_id'))->options('/tenancy/api/editGoods')->readOnly()->disable();
        }else{
            $form->select('goods_id', trans('flash_sale.goods_id'))->options('/tenancy/api/goods')->required();
        }
        $form->text('title', trans('lottery.title'))->required();
        $form->textarea('description', trans('lottery.description'));
        $form->decimal('price', trans('lottery.price'))->default(0.00)->required();
        $form->number('needer', trans('lottery.needer'))->min(2)->required();

//        $form->number('weigh', trans('lottery.weigh'));
//        $form->switch('is_recommend', trans('lottery.is_recommend'));
//        $form->switch('status', trans('lottery.status'));

        $form->saved(function(Form $form){
            //活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
            DB::table('goods')->where('id',$form->model()->goods_id)->update(['prom_type' =>3, 'prom_id' => $form->model()->id ]);
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
