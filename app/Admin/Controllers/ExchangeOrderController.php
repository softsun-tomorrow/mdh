<?php

namespace App\Admin\Controllers;

use App\Models\Exchange;
use App\Models\ExchangeOrder;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ExchangeOrderController extends Controller
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
        $grid = new Grid(new ExchangeOrder);

        $grid->id('Id');
        $grid->exchange_id(trans('exchange_order.exchange_id'))->display(function($exchangeId){
            if($exchange = Exchange::withTrashed()->find($exchangeId)) return $exchange->name;
        });
//        $grid->store_id(trans('exchange_order.store_id'))->display(function($storeId){
//            if($store = Store::find($storeId)) return $store->shop_name;
//        });
        $grid->user_id(trans('exchange_order.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
        $grid->money(trans('exchange_order.money'));
        $grid->consignee(trans('exchange_order.consignee'));
        $grid->mobile(trans('exchange_order.mobile'));
        $grid->area(trans('exchange_order.area'));
        $grid->address(trans('exchange_order.address'));
        $grid->created_at(trans('exchange_order.created_at'));
        $grid->status(trans('exchange_order.status'))->editable('select',ExchangeOrder::getStatusArr());
        $grid->remark(trans('exchange_order.remark'))->editable('textarea');

        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableActions();

        $grid->filter(function($filter){
            $filter->where(function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%")->orWhere('mobile', 'like', "%{$this->input}%");
                });
            }, '用户名或手机号');

            $filter->in('status',trans('exchange_order.status'))->radio(ExchangeOrder::getStatusArr());
        });

        $grid->model()->where('store_id',0)->orderBy('id','desc');

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
        $show = new Show(ExchangeOrder::findOrFail($id));

        $show->id('Id');
        $show->exchange_id(trans('exchange_order.exchange_id'));
        $show->store_id(trans('exchange_order.store_id'));
        $show->user_id(trans('exchange_order.user_id'));
        $show->money(trans('exchange_order.money'));
        $show->created_at(trans('exchange_order.created_at'));
        $show->status(trans('exchange_order.status'));
        $show->remark(trans('exchange_order.remark'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ExchangeOrder);

        $form->number('exchange_id', trans('exchange_order.exchange_id'));
        $form->number('store_id', trans('exchange_order.store_id'));
        $form->number('user_id', trans('exchange_order.user_id'));
        $form->number(trans('exchange_order.money'), trans('exchange_order.money'));
        $form->select('status', trans('exchange_order.status'))->options(ExchangeOrder::getStatusArr());
        $form->textarea('remark', trans('exchange_order.remark'));
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
