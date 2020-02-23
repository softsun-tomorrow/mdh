<?php

namespace App\Tenancy\Controllers;

use App\Tenancy\Extensions\SendSms;
use App\Models\Area;
use App\Models\Card;
use App\Http\Controllers\Controller;
use App\Models\CardType;
use App\Models\Store;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CardController extends Controller
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
        $grid = new Grid(new Card);

        $grid->id('Id');
        $grid->user_id(trans('card.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
//        $grid->store_id(trans('card.store_id'));
        $grid->type(trans('card.type'))->using(Card::getTypeArr());
        $grid->card_no(trans('card.card_no'));
        $grid->card_type_id(trans('card.card_type_id'))->display(function($cardTypeId){
            if($cardType = CardType::find($cardTypeId)) return $cardType->name;
        });

        $grid->account(trans('card.account'));
        $grid->score(trans('card.score'));
//        $grid->name(trans('card.name'));
//        $grid->gender(trans('card.gender'));
//        $grid->birthday(trans('card.birthday'));
//        $grid->mobile(trans('card.mobile'));
//        $grid->user_num(trans('card.user_num'));
//        $grid->email(trans('card.email'));
//        $grid->address(trans('card.address'));
//        $grid->idcard_no(trans('card.idcard_no'));
//        $grid->idcard_front(trans('card.idcard_front'));
//        $grid->idcard_back(trans('card.idcard_back'));
//        $grid->idcard_hold(trans('card.idcard_hold'));
//        $grid->store_password(trans('card.store_password'));
//        $grid->rate(trans('card.rate'));
//        $grid->rate_end_time(trans('card.rate_end_time'));
        $grid->created_at(trans('card.created_at'));
//        $grid->updated_at(trans('card.updated_at'));
        $grid->end_time(trans('card.end_time'));

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();
            // 添加操作
            $actions->append(new SendSms($actions->getKey(),$actions->row->mobile));
        });

        $grid->filter(function($filter){
            $filter->equal('card_no',trans('card.card_no'));
            $filter->in('type',trans('card.type'))->checkbox(Card::getTypeArr());
            $filter->where(function($query){
                $query->whereHas('user',function($query){
                    $query->where('name','like', "%{$this->input}%")->orWhere('mobile','like',"%{$this->input}%");
                });
            },'用户名或手机号');
            $filter->between('created_at',trans('admin.created_at'))->datetime();
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
        $show = new Show(Card::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('card.user_id'));
        $show->store_id(trans('card.store_id'));
        $show->type(trans('card.type'));
        $show->card_no(trans('card.card_no'));
        $show->card_type_id(trans('card.card_type_id'));
        $show->created_at(trans('card.created_at'));
        $show->updated_at(trans('card.updated_at'));
        $show->end_time(trans('card.end_time'));
        $show->account(trans('card.account'));
        $show->score(trans('card.score'));
        $show->name(trans('card.name'));
        $show->gender(trans('card.gender'));
        $show->birthday(trans('card.birthday'));
        $show->mobile(trans('card.mobile'));
        $show->user_num(trans('card.user_num'));
        $show->email(trans('card.email'));
        $show->address(trans('card.address'));
        $show->idcard_no(trans('card.idcard_no'));
        $show->idcard_front(trans('card.idcard_front'));
        $show->idcard_back(trans('card.idcard_back'));
        $show->idcard_hold(trans('card.idcard_hold'));
        $show->store_password(trans('card.store_password'));
        $show->rate(trans('card.rate'));
        $show->rate_end_time(trans('card.rate_end_time'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Card);
//        $form->number('user_id', trans('card.user_id'));
        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->select('card_type_id', trans('card.card_type_id'))->options('/tenancy/api/cardType')->rules('required');
        $form->datetime('end_time', trans('card.end_time'))->default(date('Y-m-d H:i:s'));
        $form->text('store_password', trans('card.store_password'))->rules('required');
//        $form->decimal('rate', trans('card.rate'))->rules('required');
//        $form->datetime('rate_end_time', trans('card.rate_end_time'))->default(date('Y-m-d H:i:s'));
        $form->text('name', trans('card.name'))->rules('required');
        $form->mobile('mobile', trans('card.mobile'))->rules('required');
        $form->decimal('account', trans('card.account'))->rules('required');

        $form->number('score', trans('card.score'))->default(0);
        $form->radio('gender', trans('card.gender'))->options(User::getGenderArr());
        $form->date('birthday', trans('card.birthday'))->default(date('Y-m-d'));
//        $form->text('user_num', trans('card.user_num'));
        $form->email('email', trans('card.email'))->default('');
        $form->text('address', trans('card.address'));
        $form->text('idcard_no', trans('card.idcard_no'));
        $form->image('idcard_front', trans('card.idcard_front'))->uniqueName();
        $form->image('idcard_back', trans('card.idcard_back'))->uniqueName();
        $form->image('idcard_hold', trans('card.idcard_hold'))->uniqueName();
        return $form;
    }
}
