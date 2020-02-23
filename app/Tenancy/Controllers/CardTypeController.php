<?php

namespace App\Tenancy\Controllers;

use App\Models\CardType;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CardTypeController extends Controller
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
        $grid = new Grid(new CardType);

        $grid->id('Id');
//        $grid->store_id(trans('card_type.store_id'));
        $grid->name(trans('card_type.name'));

        $grid->pay_account(trans('card_type.pay_account'));
        $grid->account(trans('card_type.account'));
        $grid->expire(trans('card_type.expire'))->using(CardType::getExpireArr());
        $grid->rate(trans('card_type.rate'));
        $grid->rate_expire(trans('card_type.rate_expire'))->using(CardType::getExpireArr());
        $grid->created_at(trans('card_type.created_at'));
        $grid->updated_at(trans('card_type.updated_at'));

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
        $show = new Show(CardType::findOrFail($id));

        $show->id('Id');
        $show->store_id(trans('card_type.store_id'));
        $show->pay_account(trans('card_type.pay_account'));
        $show->account(trans('card_type.account'));
        $show->name(trans('card_type.name'));
        $show->expire(trans('card_type.expire'));
        $show->rate(trans('card_type.rate'));
        $show->rate_expire(trans('card_type.rate_expire'));
        $show->created_at(trans('card_type.created_at'));
        $show->updated_at(trans('card_type.updated_at'));
        $show->deleted_at('Deleted at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CardType);
        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->text('name', trans('card_type.name'))->rules('required|max:3');
        $form->decimal('pay_account', trans('card_type.pay_account'))->default(0.00);
        $form->decimal('account', trans('card_type.account'))->default(0.00);
        $form->select('expire', trans('card_type.expire'))->options(CardType::getExpireArr());
        $form->decimal('rate', trans('card_type.rate'))->default(0.00);
        $form->select('rate_expire', trans('card_type.rate_expire'))->options(CardType::getExpireArr())->rules('required');
        $form->color('color',trans('card_type.color'))->default('#ce2eb4');
        return $form;
    }

}
