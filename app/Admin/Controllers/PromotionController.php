<?php

namespace App\Admin\Controllers;

use App\Models\Goods;
use App\Models\Promotion;
use App\Http\Controllers\Controller;
use App\Models\PromotionCategory;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class PromotionController extends Controller
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
        $grid = new Grid(new Promotion);

        $grid->id('Id');
        $grid->promotion_category_id(trans('promotion.promotion_category_id'))->display(function($promotionCategoryId){
            if($promotionCategory = PromotionCategory::find($promotionCategoryId)) return $promotionCategory->name;
        });
        $grid->goods_id(trans('promotion.goods_id'))->display(function($goodsId){
            if($goods = Goods::find($goodsId)) return $goods->name;
        });
        $grid->created_at(trans('promotion.created_at'));
        $grid->updated_at(trans('promotion.updated_at'));

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
        $show = new Show(Promotion::findOrFail($id));

        $show->id('Id');
        $show->promotion_category_id(trans('promotion.promotion_category_id'));
        $show->goods_id(trans('promotion.goods_id'));
        $show->created_at(trans('promotion.created_at'));
        $show->updated_at(trans('promotion.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Promotion);

        $form->select('promotion_category_id',trans('promotion.promotion_category_id'))->options('/admin/api/promotion_category');
        $form->select('goods_id', trans('promotion.goods_id'))->options('/admin/api/goods');
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
