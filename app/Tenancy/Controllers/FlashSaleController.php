<?php

namespace App\Tenancy\Controllers;

use App\Models\FlashSale;
use App\Http\Controllers\Controller;
use App\Models\Goods;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class FlashSaleController extends Controller
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
            ->description(trans('flash_sale.description'))
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
            ->description(trans('flash_sale.description'))
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
            ->description(trans('flash_sale.description'))
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
            ->description(trans('flash_sale.description'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FlashSale);

        $grid->id('Id');
        $grid->title(trans('flash_sale.title'));
        $grid->goods_id(trans('flash_sale.goods_id'))->display(function($id){
            if($model = Goods::find($id)) return $model->name;
        });
//        $grid->item_id(trans('flash_sale.item_id'));
        $grid->price(trans('flash_sale.price'));
        $grid->goods_num(trans('flash_sale.goods_num'));
        $grid->buy_limit(trans('flash_sale.buy_limit'));
        $grid->buy_num(trans('flash_sale.buy_num'));
        $grid->order_num(trans('flash_sale.order_num'));
//        $grid->description(trans('flash_sale.description'));
//        $grid->start_time(trans('flash_sale.start_time'));
//        $grid->end_time(trans('flash_sale.end_time'));
//        $grid->is_end(trans('flash_sale.is_end'));
//        $grid->goods_name(trans('flash_sale.goods_name'));
//        $grid->store_id(trans('flash_sale.store_id'));
//        $grid->recommend(trans('flash_sale.recommend'));
        $grid->status(trans('flash_sale.status'))->using(FlashSale::getStatusArr());
        $grid->scene(trans('flash_sale.scene'))->using(FlashSale::getSceneArr());
//        $grid->weigh(trans('flash_sale.weigh'));

        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->model()->where('store_id',Admin::user()->store_id)->orderBy('id','desc');

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
        $show = new Show(FlashSale::findOrFail($id));

        $show->id('Id');
        $show->title(trans('flash_sale.title'));
        $show->goods_id(trans('flash_sale.goods_id'));
        $show->item_id(trans('flash_sale.item_id'));
        $show->price(trans('flash_sale.price'));
        $show->goods_num(trans('flash_sale.goods_num'));
        $show->buy_limit(trans('flash_sale.buy_limit'));
        $show->buy_num(trans('flash_sale.buy_num'));
        $show->order_num(trans('flash_sale.order_num'));
        $show->description(trans('flash_sale.description'));
        $show->start_time(trans('flash_sale.start_time'));
        $show->end_time(trans('flash_sale.end_time'));
        $show->is_end(trans('flash_sale.is_end'));
        $show->goods_name(trans('flash_sale.goods_name'));
        $show->store_id(trans('flash_sale.store_id'));
        $show->recommend(trans('flash_sale.recommend'));
        $show->status(trans('flash_sale.status'));
        $show->scene(trans('flash_sale.scene'));
        $show->weigh(trans('flash_sale.weigh'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new FlashSale);
        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->hidden('status',1);

        $form->text('title',trans('flash_sale.title'));
        $form->textarea('description', trans('flash_sale.description'));

        if(request()->route('flash_sale')){
            //编辑
            $form->select('goods_id', trans('flash_sale.goods_id'))->options('/tenancy/api/editGoods')->readOnly();
        }else{
            $form->select('goods_id', trans('flash_sale.goods_id'))->options('/tenancy/api/goods')->required();
        }

        $form->decimal('price', trans('flash_sale.price'));
        $form->number('goods_num', trans('flash_sale.goods_num'))->default(1);
        $form->number('buy_limit', trans('flash_sale.buy_limit'))->default(1);
        $form->select('scene', trans('flash_sale.scene'))->options(FlashSale::getSceneArr());

//        $form->number('buy_num', trans('flash_sale.buy_num'));
//        $form->number('order_num', trans('flash_sale.order_num'));
//        $form->number('start_time', trans('flash_sale.start_time'));
//        $form->number('end_time', trans('flash_sale.end_time'));
//        $form->switch('is_end', trans('flash_sale.is_end'));
//        $form->text('goods_name', trans('flash_sale.goods_name'));

//        $form->switch('recommend', trans('flash_sale.recommend'));
//        $form->radio('status', trans('flash_sale.status'));
//        $form->number(trans('flash_sale.weigh'), trans('flash_sale.weigh'));


        $form->saving(function(Form $form){
            $goods = Goods::find($form->goods_id);
            if($goods->type > 0){
                $error = new MessageBag([
                    'title'   => '提示',
                    'message' => '活动商品的类型必须为普通商品',
                ]);

                return back()->with(compact('error'));
            }
        });

        $form->saved(function(Form $form){
            //活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
            DB::table('goods')->where('id',$form->model()->goods_id)->update(['prom_type' =>1, 'prom_id' => $form->model()->id ]);
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
