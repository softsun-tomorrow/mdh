<?php

namespace App\Admin\Controllers;

use App\Models\CardType;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
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
        $grid->store_id('Store id');
        $grid->pay_account('Pay account');
        $grid->account('Account');
        $grid->name('Name');
        $grid->expire('Expire');
        $grid->rate('Rate');
        $grid->rate_expire('Rate expire');
        $grid->created_at('Created at');
        $grid->updated_at('Updated at');
        $grid->deleted_at('Deleted at');

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
        $show->store_id('Store id');
        $show->pay_account('Pay account');
        $show->account('Account');
        $show->name('Name');
        $show->expire('Expire');
        $show->rate('Rate');
        $show->rate_expire('Rate expire');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
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

        $form->number('store_id', 'Store id');
        $form->decimal('pay_account', 'Pay account')->default(0.00);
        $form->decimal('account', 'Account')->default(0.00);
        $form->text('name', 'Name');
        $form->number('expire', 'Expire');
        $form->decimal('rate', 'Rate')->default(0.00);
        $form->number('rate_expire', 'Rate expire');

        return $form;
    }
}
