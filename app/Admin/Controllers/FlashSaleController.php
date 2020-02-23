<?php

namespace App\Admin\Controllers;

use App\Models\FlashSale;
use App\Http\Controllers\Controller;
use App\Models\Goods;
use App\Models\Store;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

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
        $grid->store_id(trans('flash_sale.store_id'))->display(function($id){
            if($model = Store::find($id)) return $model->shop_name;
        });
        $grid->is_recommend(trans('flash_sale.is_recommend'))->switch();
//        $grid->status(trans('flash_sale.status'))->editable('select',FlashSale::getStatusArr());
        $grid->status(trans('flash_sale.status'))->using(FlashSale::getStatusArr());
        $grid->scene(trans('flash_sale.scene'))->using(FlashSale::getSceneArr());
        $grid->weigh(trans('flash_sale.weigh'))->editable();

        $grid->filter(function($filter){

            $filter->like('title', trans('flash_sale.title'));
            $filter->where(function($query){
                $query->whereHas('store',function($query){
                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
                });
            },'店铺名或手机号');

            $filter->equal('status', trans('flash_sale.status'))->radio(FlashSale::getStatusArr());
            $filter->equal('scene', trans('flash_sale.scene'))->radio(FlashSale::getSceneArr());
            $filter->equal('is_rec', '是否推荐')->radio(FlashSale::getIsRecArr());
        });

        $grid->model()->orderBy('id','desc');
        $grid->disableCreateButton();
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
        $show->is_recommend(trans('flash_sale.is_recommend'));
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

        $form->text('title',trans('flash_sale.title'))->readOnly();
        $form->textarea('description', trans('flash_sale.description'))->readOnly();
        $form->select('goods_id', trans('flash_sale.goods_id'))->options('/admin/api/goods')->readOnly();
//        $form->number('item_id', trans('flash_sale.item_id'));
        $form->decimal('price', trans('flash_sale.price'))->readOnly();
        $form->number('goods_num', trans('flash_sale.goods_num'))->default(1)->readOnly();
        $form->number('buy_limit', trans('flash_sale.buy_limit'))->default(1)->readOnly();
        $form->select('scene', trans('flash_sale.scene'))->options(FlashSale::getSceneArr())->readOnly();

//        $form->number('buy_num', trans('flash_sale.buy_num'));
//        $form->number('order_num', trans('flash_sale.order_num'));
//        $form->number('start_time', trans('flash_sale.start_time'));
//        $form->number('end_time', trans('flash_sale.end_time'));
//        $form->switch('is_end', trans('flash_sale.is_end'));
//        $form->text('goods_name', trans('flash_sale.goods_name'));

        $form->switch('is_recommend', trans('flash_sale.is_recommend'));
        $form->radio('status', trans('flash_sale.status'))->options(FlashSale::getStatusArr())->default(0);
        $form->number('weigh', trans('flash_sale.weigh'))->default(FlashSale::max('weigh')+1);

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
