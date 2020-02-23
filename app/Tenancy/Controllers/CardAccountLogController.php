<?php

namespace App\Tenancy\Controllers;

use App\Models\Card;
use App\Models\CardAccountLog;
use App\Http\Controllers\Controller;

use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class CardAccountLogController extends Controller
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
        $grid = new Grid(new CardAccountLog);

        $grid->id('Id');
        $grid->user_id(trans('card_account_log.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
        $grid->card_id(trans('card_account_log.card_id'))->display(function($cardId){
            if($card = Card::find($cardId)) return $card->card_no;
        });
        $grid->before_money(trans('card_account_log.before_money'));
//        $grid->frozen_money(trans('card_account_log.frozen_money'));
        $grid->change_money(trans('card_account_log.change_money'));
        $grid->after_money(trans('card_account_log.after_money'));
        $grid->change_time(trans('card_account_log.change_time'));
        $grid->desc(trans('card_account_log.desc'));
        $grid->order_sn(trans('card_account_log.order_sn'));
        $grid->type(trans('card_account_log.type'))->using(CardAccountLog::getTypeArr());
        $grid->source(trans('card_account_log.source'))->using(CardAccountLog::getSourceArr());

        $cardIds = DB::table('card')->where('store_id',Admin::user()->store_id)->pluck('id');
        $grid->model()->whereIn('card_id',$cardIds);
        $grid->disableCreateButton();
        $grid->disableActions();

        $grid->filter(function($filter){
            $filter->in('type',trans('card_account_log.type'))->checkbox(CardAccountLog::getTypeArr());
            $filter->in('source',trans('card_account_log.source'))->checkbox(CardAccountLog::getSourceArr());
            $filter->where(function($query){
                $query->whereHas('user',function($query){
                    $query->where('name','like', "%{$this->input}%")->orWhere('mobile','like',"%{$this->input}%");
                });
            },'用户名或手机号');

            $filter->where(function($query){
                $query->whereHas('card',function($query){
                    $query->where('card_no', "{$this->input}");
                });
            },'会员卡号');
            $filter->between('change_time',trans('card_account_log.change_time'))->datetime();
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
        $show = new Show(CardAccountLog::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('card_account_log.user_id'));
        $show->card_id(trans('card_account_log.card_id'));
        $show->before_money(trans('card_account_log.before_money'));
//        $show->frozen_money(trans('card_account_log.frozen_money'));
        $show->change_money(trans('card_account_log.change_money'));
        $show->after_money(trans('card_account_log.after_money'));
        $show->change_time(trans('card_account_log.change_time'));
        $show->desc(trans('card_account_log.desc'));
        $show->order_sn(trans('card_account_log.order_sn'));
        $show->type(trans('card_account_log.type'));
        $show->source(trans('card_account_log.source'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CardAccountLog);

        $form->number('user_id', trans('card_account_log.user_id'));
        $form->number('card_id', trans('card_account_log.card_id'));
        $form->decimal('before_money', trans('card_account_log.before_money'))->default(0.00);
//        $form->decimal('frozen_money', trans('card_account_log.frozen_money'))->default(0.00);
        $form->decimal('change_money', trans('card_account_log.change_money'))->default(0.00);
        $form->decimal('after_money', trans('card_account_log.after_money'))->default(0.00);
        $form->datetime('change_time', trans('card_account_log.change_time'))->default(date('Y-m-d H:i:s'));
        $form->text(trans('card_account_log.desc'), trans('card_account_log.desc'));
        $form->text('order_sn', trans('card_account_log.order_sn'));
        $form->switch(trans('card_account_log.type'), trans('card_account_log.type'));
        $form->switch(trans('card_account_log.source'), trans('card_account_log.source'));

        return $form;
    }
}
