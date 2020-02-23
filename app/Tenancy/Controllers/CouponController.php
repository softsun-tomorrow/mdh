<?php

namespace App\Tenancy\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\Coupon;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CouponController extends Controller
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
        $grid = new Grid(new Coupon);

        $grid->id('Id');
        $grid->column('分类')->display(function(){
            return $this->fullCat;
        });
        $grid->name(trans('coupon.name'));
        $grid->send_type(trans('coupon.send_type'))->using(Coupon::getSendType());
        $grid->coupon_type(trans('coupon.coupon_type'))->using(Coupon::getCouponType());
        $grid->money(trans('coupon.money'));
        $grid->condition(trans('coupon.condition'));
        $grid->create_num(trans('coupon.create_num'));
        $grid->send_num(trans('coupon.send_num'));
        $grid->use_num(trans('coupon.use_num'));
        $grid->send_start_time(trans('coupon.send_start_time'));
        $grid->send_end_time(trans('coupon.send_end_time'));
        $grid->use_start_time(trans('coupon.use_start_time'));
        $grid->use_end_time(trans('coupon.use_end_time'));
        $grid->created_at(trans('coupon.created_at'));
        $grid->status(trans('coupon.status'))->using(Coupon::getStatus());
        $grid->coupon_info(trans('coupon.coupon_info'));
        $grid->province_id(trans('coupon.province_id'))->display(function ($area) {
            if($area = Area::find($area)) return $area->name;
        });
        $grid->city_id(trans('coupon.city_id'))->display(function ($area) {
            if($area = Area::find($area)) return $area->name;
        });
        $grid->district_id(trans('coupon.district_id'))->display(function ($area) {
            if($area = Area::find($area)) return $area->name;
        });

        $grid->filter(function($filter){
            $filter->like('name',trans('coupon.name'));
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
        $show = new Show(Coupon::findOrFail($id));

        $show->id('Id');
        $show->store_id(trans('coupon.store_id'));
        $show->name(trans('coupon.name'));
        $show->coupon_type(trans('coupon.coupon_type'));
        $show->send_type(trans('coupon.send_type'));
        $show->money(trans('coupon.money'));
        $show->condition(trans('coupon.condition'));
        $show->create_num(trans('coupon.create_num'));
        $show->send_num(trans('coupon.send_num'));
        $show->use_num(trans('coupon.use_num'));
        $show->send_start_time(trans('coupon.send_start_time'));
        $show->send_end_time(trans('coupon.send_end_time'));
        $show->use_start_time(trans('coupon.use_start_time'));
        $show->use_end_time(trans('coupon.use_end_time'));
        $show->created_at(trans('coupon.created_at'));
        $show->status(trans('coupon.status'));
        $show->coupon_info(trans('coupon.coupon_info'));
        $show->province_id(trans('coupon.province_id'));
        $show->city_id(trans('coupon.city_id'));
        $show->district_id(trans('coupon.district_id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Coupon);
        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->text('name', trans('coupon.name'))->rules('required');
        $form->select('coupon_type', trans('coupon.coupon_type'))->options(Coupon::getCouponType())->rules('required');
        $form->select('send_type', trans('coupon.send_type'))->options(Coupon::getSendType())->rules('required');//发放类型 0签到赠送 1 按用户发放 2 免费领取 3 线下发放
        $form->decimal('money', trans('coupon.money'))->rules('required');
        $form->decimal('condition', trans('coupon.condition'))->rules('required');
        $form->number('create_num', trans('coupon.create_num'))->default(1000)->rules('required');
        $form->datetimeRange('send_start_time','send_end_time','可领取时间段')->rules('required');
        $form->datetimeRange('use_start_time','use_end_time','可使用时间段')->rules('required');
        $form->switch('status', trans('coupon.status'))->default(1)->rules('required');
        $form->text('coupon_info', trans('coupon.coupon_info'))->rules('required');

        $form->select('cat1', trans('goods.cat1'))->options(
            Category::where('pid' , 0)->pluck('name' , 'id')
        )->load('cat2','/api/backend/getChildCategory')->rules('required');
        $form->select('cat2', trans('goods.cat2'))->options(function($id){
            return Category::where('id' , $id)->pluck('name' , 'id');//回显
        })->load('cat3','/api/backend/getChildCategory')->rules('required');
        $form->select('cat3', trans('goods.cat3'))->options(function($id){
            return Category::where('id' , $id)->pluck('name' , 'id');//回显
        })->rules('required');

        $form->select('province_id', trans('coupon.province_id'))->options(
            Area::where('parent_id' , 1)->pluck('name' , 'id')
        )->load('city_id','/api/backend/getChildArea')->rules('required');
        $form->select('city_id', trans('coupon.city_id'))->options(function($id){
            return Area::where('id' , $id)->pluck('name' , 'id');//回显
        })->load('district_id','/api/backend/getChildArea')->rules('required');
        $form->select('district_id', trans('coupon.district_id'))->options(function($id){
            return Area::where('id' , $id)->pluck('name' , 'id');//回显
        })->rules('required');

        return $form;
    }
}
