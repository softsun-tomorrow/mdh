<?php

namespace App\Admin\Controllers;

use App\Models\CardScoreLog;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CardScoreLogController extends Controller
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
        $grid = new Grid(new CardScoreLog);

        $grid->id('Id');
        $grid->user_id('User id');
        $grid->card_id('Card id');
        $grid->before_money('Before money');
        $grid->frozen_money('Frozen money');
        $grid->change_money('Change money');
        $grid->after_money('After money');
        $grid->change_time('Change time');
        $grid->desc('Desc');
        $grid->order_sn('Order sn');
        $grid->type('Type');
        $grid->source('Source');

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
        $show = new Show(CardScoreLog::findOrFail($id));

        $show->id('Id');
        $show->user_id('User id');
        $show->card_id('Card id');
        $show->before_money('Before money');
        $show->frozen_money('Frozen money');
        $show->change_money('Change money');
        $show->after_money('After money');
        $show->change_time('Change time');
        $show->desc('Desc');
        $show->order_sn('Order sn');
        $show->type('Type');
        $show->source('Source');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CardScoreLog);

        $form->number('user_id', 'User id');
        $form->number('card_id', 'Card id');
        $form->decimal('before_money', 'Before money')->default(0.00);
        $form->decimal('frozen_money', 'Frozen money')->default(0.00);
        $form->decimal('change_money', 'Change money')->default(0.00);
        $form->decimal('after_money', 'After money')->default(0.00);
        $form->datetime('change_time', 'Change time')->default(date('Y-m-d H:i:s'));
        $form->text('desc', 'Desc');
        $form->text('order_sn', 'Order sn');
        $form->switch('type', 'Type');
        $form->switch('source', 'Source');

        return $form;
    }
}
