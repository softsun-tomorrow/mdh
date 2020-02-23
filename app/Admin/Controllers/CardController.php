<?php

namespace App\Admin\Controllers;

use App\Models\Card;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
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
        $grid->user_id('User id');
        $grid->store_id('Store id');
        $grid->type('Type');
        $grid->card_no('Card no');
        $grid->card_type_id('Card type id');
        $grid->created_at('Created at');
        $grid->updated_at('Updated at');
        $grid->end_time('End time');
        $grid->account('Account');
        $grid->score('Score');
        $grid->name('Name');
        $grid->gender('Gender');
        $grid->birthday('Birthday');
        $grid->mobile('Mobile');
        $grid->user_num('User num');
        $grid->email('Email');
        $grid->address('Address');
        $grid->idcard_no('Idcard no');
        $grid->idcard_front('Idcard front');
        $grid->idcard_back('Idcard back');
        $grid->idcard_hold('Idcard hold');
        $grid->store_password('Store password');
        $grid->rate('Rate');
        $grid->rate_end_time('Rate end time');
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
        $show = new Show(Card::findOrFail($id));

        $show->id('Id');
        $show->user_id('User id');
        $show->store_id('Store id');
        $show->type('Type');
        $show->card_no('Card no');
        $show->card_type_id('Card type id');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->end_time('End time');
        $show->account('Account');
        $show->score('Score');
        $show->name('Name');
        $show->gender('Gender');
        $show->birthday('Birthday');
        $show->mobile('Mobile');
        $show->user_num('User num');
        $show->email('Email');
        $show->address('Address');
        $show->idcard_no('Idcard no');
        $show->idcard_front('Idcard front');
        $show->idcard_back('Idcard back');
        $show->idcard_hold('Idcard hold');
        $show->store_password('Store password');
        $show->rate('Rate');
        $show->rate_end_time('Rate end time');
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
        $form = new Form(new Card);

        $form->number('user_id', 'User id');
        $form->number('store_id', 'Store id');
        $form->switch('type', 'Type');
        $form->text('card_no', 'Card no');
        $form->number('card_type_id', 'Card type id');
        $form->datetime('end_time', 'End time')->default(date('Y-m-d H:i:s'));
        $form->decimal('account', 'Account')->default(0.00);
        $form->decimal('score', 'Score');
        $form->text('name', 'Name');
        $form->switch('gender', 'Gender');
        $form->date('birthday', 'Birthday')->default(date('Y-m-d'));
        $form->mobile('mobile', 'Mobile');
        $form->text('user_num', 'User num');
        $form->email('email', 'Email');
        $form->text('address', 'Address');
        $form->text('idcard_no', 'Idcard no');
        $form->text('idcard_front', 'Idcard front');
        $form->text('idcard_back', 'Idcard back');
        $form->text('idcard_hold', 'Idcard hold');
        $form->text('store_password', 'Store password');
        $form->decimal('rate', 'Rate')->default(0.0);
        $form->datetime('rate_end_time', 'Rate end time')->default(date('Y-m-d H:i:s'));
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
