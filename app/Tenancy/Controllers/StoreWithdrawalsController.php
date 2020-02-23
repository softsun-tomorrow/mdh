<?php

namespace App\Tenancy\Controllers;

use App\Models\Config;
use App\Models\ExpenseLog;
use App\Models\Store;
use App\Models\StoreWithdrawals;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class StoreWithdrawalsController extends Controller
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
        $grid = new Grid(new StoreWithdrawals);

        $grid->id('Id');
        $grid->store_id(trans('store_withdrawals.store_id'))->display(function($storeId){
            if($store = Store::find($storeId)){
                return $store->shop_name;
            }
        });

        $grid->created_at(trans('store_withdrawals.created_at'));
//        $grid->pay_time(trans('store_withdrawals.pay_time'));
        $grid->check_time(trans('store_withdrawals.check_time'));
        $grid->money(trans('store_withdrawals.money'));
//        $grid->bank_name(trans('store_withdrawals.bank_name'));
        $grid->bank_card(trans('store_withdrawals.bank_card'));
        $grid->realname(trans('store_withdrawals.realname'));
//        $grid->remark(trans('store_withdrawals.remark'));
        $grid->status(trans('store_withdrawals.status'))->using(StoreWithdrawals::getStatusArr());
        $grid->pay_code(trans('store_withdrawals.pay_code'));
        $grid->taxfee(trans('store_withdrawals.taxfee'));
//        $grid->error_code(trans('store_withdrawals.error_code'));

        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->tools(function ($tools) {

                $account = Store::find(Admin::user()->store_id)->account;
                $tools->append("<a href='/tenancy/store_withdrawals/create' class='btn btn-sm btn-success' style='float: right;'>
<i class='fa fa-save'></i>&nbsp;&nbsp;余额：". $account ." 提现</a>");

        });


        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();

        });



        $grid->filter(function($filter){
            $filter->where(function($query){
                $query->whereHas('store',function($query){
                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
                });
            },'店铺名或手机号');

            $filter->in('status',trans('store_withdrawals.status'))->checkbox(StoreWithdrawals::getStatusArr());
            $filter->between('created_at',trans('store_withdrawals.created_at'))->datetime();


        });

        $grid->model()->where('store_id',Admin::user()->store_id);


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
        $show = new Show(StoreWithdrawals::findOrFail($id));

        $show->id('Id');
        $show->store_id(trans('store_withdrawals.store_id'));
        $show->created_at(trans('store_withdrawals.created_at'));
        $show->pay_time(trans('store_withdrawals.pay_time'));
        $show->check_time(trans('store_withdrawals.check_time'));
        $show->money(trans('store_withdrawals.money'));
        $show->bank_name(trans('store_withdrawals.bank_name'));
        $show->bank_card(trans('store_withdrawals.bank_card'));
        $show->realname(trans('store_withdrawals.realname'));
//        $show->remark(trans('store_withdrawals.remark'));
        $show->status(trans('store_withdrawals.status'));
        $show->pay_code(trans('store_withdrawals.pay_code'));
        $show->taxfee(trans('store_withdrawals.taxfee'));
        $show->error_code(trans('store_withdrawals.error_code'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StoreWithdrawals);
        $store = Store::find(Admin::user()->store_id);

        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->hidden('bank_name')->default('');
        $form->hidden('bank_card')->default($store->bank_num);
        $form->hidden('realname')->default($store->contacts_name);

        $form->decimal('money', trans('store_withdrawals.money'))->default(0.00);
//        $form->text('bank_name', trans('store_withdrawals.bank_name'));
//        $form->text('bank_card', trans('store_withdrawals.bank_card'));
//        $form->text('realname', trans('store_withdrawals.realname'));

        $form->saved(function(Form $form){
            //$config = Config::getConfigValue();
            $store_withdrawals_taxfee = get_config_by_name('store_withdrawals_taxfee');

            $taxfee = price_format($form->model()->money * $store_withdrawals_taxfee/100);
            DB::table('store_withdrawals')->where('id',$form->model()->id)->update(['taxfee' => $taxfee,'created_at' => date('Y-m-d H:i:s')]);

            //店铺资金记录
            Store::storeAccountLog(1,2,Admin::user()->store_id,'-'.$form->model()->money,'提现');
        });


        $form->saving(function (Form $form) {

            $storeId = Admin::user()->store_id;
            if($store = Store::find($storeId))
            {
                // 验证资金是否冻结
                if($store['is_frozen'] == 1){
                    $error = new MessageBag([
                        'title'   => '错误提示',
                        'message' => '资金被冻结，请联系管理员',
                    ]);

                    return back()->with(compact('error'));
                }

                //保留金额
                $storeAccount = round(($store->account*100 - $form->money*100)/100, 2);
//                dd($storeAccount);
                if($store['reserve_account']*100 - $storeAccount*100 >= 0){
                    $error = new MessageBag([
                        'title'   => '错误提示',
                        'message' => '您的账户预留资金为' . $store['reserve_account'],
                    ]);

                    return back()->with(compact('error'));
                }

            }



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
