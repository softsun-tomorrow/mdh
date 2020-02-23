<?php

namespace App\Tenancy\Controllers;

use App\Models\Area;
use App\Models\Store;
use App\Http\Controllers\Controller;

use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class StoreController extends Controller
{
    use HasResourceActions;
    const STATUS = [0 => '关闭', 1 => '通过', 2 => '未审核'];
    public $statusArr = [0 => '关闭', 1 => '通过', 2 => '未审核'];

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
            ->body( $this->grid());
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
        $grid = new Grid(new Store);

        $grid->id('Id');
        $grid->shop_name(trans('store.shop_name'));
//        $grid->logo(trans('store.logo'));

//        $grid->license_front(trans('store.license_front'));
//        $grid->license_back(trans('store.license_back'));
        $grid->contacts_name(trans('store.contacts_name'));
        $grid->contacts_mobile(trans('store.contacts_mobile'));
//        $grid->customer_service(trans('store.customer_service'));


        $grid->address(trans('store.address'));

        $grid->weigh(trans('store.weigh'));
        $grid->is_rec(trans('store.is_rec'))->display(function ($is_rec) {
            return $is_rec ? '是' : '否';
        });
        $grid->is_frozen(trans('store.is_frozen'))->display(function ($is_frozen) {
            return $is_frozen ? '是' : '否';
        });
        $grid->status(trans('store.status'))->display(function ($status) {
            return self::STATUS[$status];
        });
        $grid->created_at(trans('store.created_at'));

        $grid->disableCreateButton();
        $grid->disablePagination();
        $grid->disableFilter();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableView();

        });
//        dd(Admin::user()->username);
        $grid->model()->where('contacts_mobile', Admin::user()->username);

        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->like('shop_name', '店铺名称');
            $filter->equal('contacts_mobile', '手机号码')->mobile();
            $filter->equal('status', '审核状态')->select(self::STATUS);
            $filter->in('is_rec',trans('store.is_rec'))->checkbox(Store::getIsRecArr());


        });

        $grid->model()->orderBy('id','desc');
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
        $show = new Show(Store::findOrFail($id));

        $show->id('Id');
        $show->shop_name(trans('store.shop_name'));
        $show->license_front(trans('store.license_front'))->image();
        $show->license_back(trans('store.license_back'))->image();
        $show->contacts_name(trans('store.contacts_name'));
        $show->contacts_mobile(trans('store.contacts_mobile'));
        $show->customer_service('customer_service');
        $show->logo(trans('store.logo'))->image();
        $show->province_id(trans('store.province_id'))->as(function ($area) {
            if($area = Area::find($area)) return $area->name;
        });
        $show->city_id(trans('store.city_id'))->as(function ($area) {
            if($area = Area::find($area)) return $area->name;

        });
        $show->district_id(trans('store.district_id'))->as(function ($area) {
            if($area = Area::find($area)) return $area->name;

        });
        $show->address(trans('store.address'));

        $show->weigh(trans('store.weigh'));
        $show->is_rec(trans('store.is_rec'))->as(function ($is_rec) {
            return $is_rec ? '是' : '否';
        });
        $show->status(trans('store.status'))->using(self::STATUS);

        $show->created_at(trans('store.created_at'));


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Store);
        $form->tab('基本信息',function($form){
            $form->text('shop_name', trans('store.shop_name'))->rules('required|min:2|max:20');
            $form->image('logo', trans('store.logo'))->rules('required')->uniqueName();
            $form->textarea('notice',trans('store.notice'));
//            $form->text('qq',trans('store.qq'));
//            $form->text('wechat',trans('store.wechat'));
            $form->text('customer_service',trans('store.customer_service'))->default(get_config_by_name('service_telephone'));

        })->tab('店铺轮播图',function($form){
            $form->hasMany('store_banner','店铺轮播图',function(Form\NestedForm $form){
                $form->image('image',trans('store_banner.image'))->uniqueName();
                $form->number('weigh',trans('store_banner.weigh'));
                $form->select('goods_id',trans('store_banner.goods_id'))->options('/tenancy/api/storeGoods');
            });
        })->tab('入驻信息',function($form){
            $form->select('store_class_id', trans('store.store_class_id'))->options('/api/backend/storeClass')->readOnly();
            $form->radio('type',trans('store.type'))->options(Store::getTypeArr())->readOnly();
            $form->radio('send_type',trans('store.send_type'))->readOnly()->options(Store::getSendTypeArr());
            $form->text('contacts_mobile', trans('store.contacts_mobile'))->readOnly();
            $form->text('contacts_name', trans('store.contacts_name'))->readOnly();
            $form->text('idcard_num', trans('store.idcard_num'))->readOnly();
            $form->text('bank_num', trans('store.bank_num'))->readOnly();
            $form->text('address', trans('store.address'))->readOnly();
            $form->image('license_front', trans('store.license_front'))->readOnly();
            $form->image('idcard_front', trans('store.idcard_front'))->readOnly();
            $form->image('idcard_back', trans('store.idcard_back'))->readOnly();
            $form->image('bank_front', trans('store.bank_front'))->readOnly();
            $form->image('bank_back', trans('store.bank_back'))->readOnly();
            $form->image('brand_image', trans('store.brand_image'))->readOnly();
            $form->image('trademark_image', trans('store.trademark_image'))->readOnly();
            $form->image('food_image', trans('store.food_image'))->readOnly();
            $form->image('door_image', trans('store.door_image'))->readOnly();
            $form->image('other_image', trans('store.other_image'))->readOnly();

        });
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->ignore(['type', 'send_type']);

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
