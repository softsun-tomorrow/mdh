<?php

namespace App\Admin\Controllers;

use App\Models\Goods;
use App\Models\Lottery;
use App\Http\Controllers\Controller;
use App\Models\Store;
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
        $grid->goods_id(trans('lottery.goods_id'))->display(function($id){
            if($model = Goods::find($id)) return $model->name;
        });
        $grid->store_id(trans('lottery.store_id'))->display(function($storeId){
            if($store = Store::find($storeId)) return $store->shop_name;
        });
        $grid->title(trans('lottery.title'));
//        $grid->goods_price(trans('lottery.goods_price'));
        $grid->price(trans('lottery.price'));
        $grid->needer(trans('lottery.needer'));
        $grid->join_num(trans('lottery.join_num'));

        $grid->weigh(trans('lottery.weigh'))->editable();
        $grid->is_recommend(trans('lottery.is_recommend'))->switch();
//        $grid->status(trans('lottery.status'))->editable('select',Lottery::getStatusArr());//有bug
        $grid->status(trans('lottery.status'))->using(Lottery::getStatusArr());

        $grid->filter(function($filter){

            $filter->like('title', trans('lottery.title'));
            $filter->where(function($query){
                $query->whereHas('store',function($query){
                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
                });
            },'店铺名或手机号');

            $filter->equal('status', trans('lottery.status'))->radio(Lottery::getStatusArr());
            $filter->equal('is_rec', '是否推荐')->radio(Lottery::getIsRecArr());
        });

        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableDelete();

        });
        $grid->model()->orderBy('id','desc');
        $grid->disableCreateButton();
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
        $form->select('goods_id', trans('lottery.goods_id'))->options('/admin/api/goods')->readOnly();

        $form->text('title', trans('lottery.title'))->readOnly();
        $form->textarea('description', trans('lottery.description'))->readOnly();
        $form->decimal('price', trans('lottery.price'))->default(0.00)->readOnly();
        $form->number('needer', trans('lottery.needer'))->min(2)->readOnly();

        $form->number('weigh', trans('lottery.weigh'))->default(Lottery::max('weigh')+1);
        $form->switch('is_recommend', trans('lottery.is_recommend'));
        $form->radio('status', trans('lottery.status'))->options(Lottery::getStatusArr())->default(0);
        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->ignore('goods_id');

        return $form;
    }
}
