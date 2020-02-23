<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\Withdrawals;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class WithdrawalsController extends Controller
{
    use HasResourceActions;
    //状态：-2删除作废-1审核失败0申请中1审核通过2付款成功3付款失败
    protected static $status = [
        '-1' => '审核失败',
        '0' => '申请中',
        '1' => '审核通过',
        '2' => '付款成功',
        '3' => '付款失败'
    ];

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
        $grid = new Grid(new Withdrawals);

        $grid->id('Id');
        $grid->user_id(trans('withdrawals.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->mobile;
        });
        $grid->money(trans('withdrawals.money'));
        $grid->taxfee(trans('withdrawals.taxfee'));
        $grid->column('到账金额')->display(function(){
            return $this->actual_money;
        });
        $grid->created_at(trans('withdrawals.created_at'));
        $grid->check_time(trans('withdrawals.check_time'));
        $grid->pay_time(trans('withdrawals.pay_time'));
//        $grid->refuse_time(trans('withdrawals.refuse_time'));
        $grid->bank_name(trans('withdrawals.bank_name'));
        $grid->bank_card(trans('withdrawals.bank_card'));
        $grid->remark(trans('withdrawals.remark'));
        $grid->status(trans('withdrawals.status'))->display(function($status){
            return self::$status[$status];
        });
        $grid->pay_code(trans('withdrawals.pay_code'));
        $grid->error_code(trans('withdrawals.error_code'));

        $grid->model()->orderBy('id','desc');
        $grid->disableCreateButton();

        $grid->filter(function($filter){
            $filter->where(function($query){
                $query->whereHas('user',function($query){
                    $query->where('name','like', "%{$this->input}%")->orWhere('mobile','like',"%{$this->input}%");
                });
            },'用户名或手机号');

            $filter->in('status',trans('store.status'))->checkbox(Withdrawals::getStatusArr());
            $filter->between('created_at',trans('withdrawals.created_at'))->datetime();


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
        $show = new Show(Withdrawals::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('withdrawals.user_id'));
        $show->money(trans('withdrawals.money'));
        $show->created_at(trans('withdrawals.created_at'));
        $show->check_time(trans('withdrawals.check_time'));
        $show->pay_time(trans('withdrawals.pay_time'));
        $show->refuse_time(trans('withdrawals.refuse_time'));
        $show->bank_name(trans('withdrawals.bank_name'));
        $show->bank_card(trans('withdrawals.bank_card'));
        $show->remark(trans('withdrawals.remark'));
        $show->status(trans('withdrawals.status'));
        $show->pay_code(trans('withdrawals.pay_code'));
        $show->error_code(trans('withdrawals.error_code'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Withdrawals);

        $form->display('user_id', trans('withdrawals.user_id'))->with(function($userId){
            if($user = User::find($userId)) return User::find($userId)->mobile;
        });
        $form->display('真实姓名')->with(function(){
            if($user = User::find($this->user_id)) return User::find($this->user_id)->real_name;
        });
        $form->display('身份证号码')->with(function(){
            if($user = User::find($this->user_id)) return User::find($this->user_id)->real_name;
        });
        $form->display('身份证正面')->with(function(){
            if($user = User::find($this->user_id)){
                $idcard_front = User::find($this->user_id)->idcard_front;
                return "<img src='/uploads/". $idcard_front ."'/>";
            }
        });

        $form->display('身份证反面')->with(function(){
            if($user = User::find($this->user_id)){
                $idcard_back = User::find($this->user_id)->idcard_back;
                return "<img src='/uploads/". $idcard_back ."'/>";
            }
        });
        $form->display('money', trans('withdrawals.money'));
        $form->display('taxfee', trans('withdrawals.taxfee'));
        $form->display('bank_name', trans('withdrawals.bank_name'));
        $form->display('bank_card', trans('withdrawals.bank_card'));
        $form->radio('status', trans('withdrawals.status'))->options([
            '-1' => '审核失败',
            '1' => '审核通过',
            //'2' => '付款成功'
        ])->default(0)->required();
//        $form->text('pay_code',trans('withdrawals.pay_code'));
//        $form->text('remark', trans('withdrawals.remark'));
        $form->hidden('check_time')->default(date('Y-m-d H:i:s'));
        $form->saved(function(Form $form){
            Withdrawals::trans($form->model(),$form->model()->status);
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
