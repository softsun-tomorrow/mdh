<?php

namespace App\Tenancy\Controllers;

use App\Models\Store;
use App\Models\StoreAccountLog;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class StoreAccountLogController extends Controller
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
        $grid = new Grid(new StoreAccountLog);


        $grid->id('Id');
//        $grid->store_id(trans('store_account_log.store_id'))->display(function ($storeId) {
//            if ($store = Store::find($storeId)) {
//                return $store->shop_name;
//            }
//        });
        $grid->before_money(trans('store_account_log.before_money'));
        $grid->change_money(trans('store_account_log.change_money'));
        $grid->after_money(trans('store_account_log.after_money'));
        $grid->created_at(trans('store_account_log.created_at'));
        $grid->desc(trans('store_account_log.desc'));
        $grid->order_sn(trans('store_account_log.order_sn'));
//        $grid->order_id(trans('store_account_log.order_id'));
        $grid->type(trans('store_account_log.type'))->using(StoreAccountLog::getTypeArr());
        $grid->source(trans('store_account_log.source'))->using(StoreAccountLog::getSourceArr());


        $grid->disableCreateButton();
        $grid->disableActions();


        $grid->model()->where('store_id', Admin::user()->store_id);


        $grid->filter(function ($filter) {
            $filter->in('type', trans('store_account_log.type'))->checkbox(StoreAccountLog::getTypeArr());
            $filter->in('source', trans('store_account_log.source'))->checkbox(StoreAccountLog::getSourceArr());
            $filter->between('created_at', trans('store_account_log.created_at'))->datetime();
        });

        $grid->footer(function ($query) {
            // 查询出已支付状态的订单总金额
            $in = DB::table('store_account_log')->where(['store_id' => Admin::user()->store_id, 'type' => 0])->sum('change_money');
            $out = DB::table('store_account_log')->where(['store_id' => Admin::user()->store_id, 'type' => 1])->sum('change_money');

            return "<span style='padding: 10px;'>总收入 ： $in</span><span style='padding: 10px;'>总支出 ：" . abs($out) . " </span>";
        });

        $grid->model()->where('store_id', Admin::user()->store_id)->orderBy('id', 'desc');

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
        $show = new Show(StoreAccountLog::findOrFail($id));

        $show->id('Id');
        $show->store_id(trans('store_account_log.store_id'));
        $show->before_money(trans('store_account_log.before_money'));
        $show->change_money(trans('store_account_log.change_money'));
        $show->after_money(trans('store_account_log.after_money'));
        $show->created_at(trans('store_account_log.created_at'));
        $show->desc(trans('store_account_log.desc'));
        $show->order_sn(trans('store_account_log.order_sn'));
        $show->order_id(trans('store_account_log.order_id'));
        $show->type(trans('store_account_log.type'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StoreAccountLog);

        $form->number('store_id', trans('store_account_log.store_id'));
        $form->decimal('before_money', trans('store_account_log.before_money'))->default(0.00);
        $form->decimal('change_money', trans('store_account_log.change_money'))->default(0.00);
        $form->decimal('after_money', trans('store_account_log.after_money'))->default(0.00);
        $form->text(trans('store_account_log.desc'), trans('store_account_log.desc'));
        $form->text('order_sn', trans('store_account_log.order_sn'));
        $form->number('order_id', trans('store_account_log.order_id'));
        $form->switch(trans('store_account_log.type'), trans('store_account_log.type'));
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
