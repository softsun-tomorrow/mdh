<?php

namespace App\Admin\Controllers;

use App\Logic\StoreLogic;
use App\Models\Area;
use App\Models\Category;
use App\Models\Ems;
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
        $storeLogic = new StoreLogic();
        $grid = new Grid(new Store);


        $grid->id('Id');
        $grid->shop_name(trans('store.shop_name'));
        $grid->account(trans('store.account'));
//        $grid->logo(trans('store.logo'));

//        $grid->license_front(trans('store.license_front'));
//        $grid->license_back(trans('store.license_back'));
        $grid->contacts_name(trans('store.contacts_name'));
        $grid->contacts_mobile(trans('store.contacts_mobile'));
        $grid->customer_service(trans('store.customer_service'));


        $grid->address(trans('store.address'));

        $grid->weigh(trans('store.weigh'))->editable();
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

        $grid->column('总销售额')->display(function() use ($storeLogic){
            $storeLogic->setStoreId($this->id);
            $arr = $storeLogic->getStoreOrderAmount();
            return $arr['totalAmount'];
        });

        $grid->column('上月销售额')->display(function() use ($storeLogic){
            $storeLogic->setStoreId($this->id);
            $arr = $storeLogic->getStoreOrderAmount();
            return $arr['lastMouthAmount'];
        });


        if (Admin::user()->isRole('store')) {
            //商户角色
            $grid->disableCreateButton();
            $grid->disablePagination();
            $grid->disableFilter();
            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->actions(function ($actions) {
                $actions->disableDelete();

            });
            $grid->model()->where('contacts_mobile', Admin::user()->username);

        }

        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            $filter->disableIdFilter();
            $filter->like('shop_name', '店铺名称');
            $filter->equal('contacts_mobile', '手机号码')->mobile();
            $filter->equal('status', '审核状态')->select(self::STATUS);
            $filter->in('is_rec', trans('store.is_rec'))->checkbox(Store::getIsRecArr());
            $filter->between('created_at', trans('admin.created_at'))->datetime();


        });

        $grid->model()->orderBy('id', 'desc');
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
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
            if ($area) {
                return Area::getNameByCode($area);
            }
        });
        $show->city_id(trans('store.city_id'))->as(function ($area) {
            if ($area) {
                return Area::getNameByCode($area);
            }
        });
        $show->district_id(trans('store.district_id'))->as(function ($area) {
            if ($area) {
                return Area::getNameByCode($area);
            }
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

        $form->tab('审核操作',function($form){
            //管理员审核
            $form->number('weigh', trans('store.weigh'))->default(Store::max('weigh') + 1);
            $form->switch('is_rec', trans('store.is_rec'))->default(0);
            $form->switch('is_frozen', trans('store.is_frozen'))->default(0);
            $form->decimal('reserve_account', trans('store.reserve_account'))->default(0.00);
            $form->radio('status', trans('store.status'))->options(self::STATUS)->default(2);
        })->tab('基本信息',function($form){
            $form->text('shop_name', trans('store.shop_name'));
            $form->image('logo', trans('store.logo'))->uniqueName();
            $form->textarea('notice',trans('store.notice'));

        })->tab('店铺轮播图',function($form){
            $form->hasMany('store_banner','店铺轮播图',function(Form\NestedForm $form){
                $form->image('image',trans('store_banner.image'))->uniqueName();
                $form->number('weigh',trans('store_banner.weigh'));
                $form->select('goods_id',trans('store_banner.goods_id'))->options('/tenancy/api/goods');
            });
        })->tab('入驻信息',function($form){
            $form->radio('type',trans('store.type'))->options(Store::getTypeArr());
            $form->radio('send_type',trans('store.send_type'))->options(Store::getSendTypeArr());
            $form->checkbox('cat_ids',trans('store.cat_ids'))->options(Category::where('pid' , 0)->pluck('name' , 'id')
);
            $form->text('contacts_mobile', trans('store.contacts_mobile'));
            $form->text('contacts_name', trans('store.contacts_name'));
            $form->text('idcard_num', trans('store.idcard_num'));
            $form->text('bank_num', trans('store.bank_num'));
            $form->text('address', trans('store.address'));
            $form->image('license_front', trans('store.license_front'));
            $form->image('idcard_front', trans('store.idcard_front'));
            $form->image('idcard_back', trans('store.idcard_back'));
            $form->image('bank_front', trans('store.bank_front'));
            $form->image('bank_back', trans('store.bank_back'));
            $form->image('brand_image', trans('store.brand_image'));
            $form->image('trademark_image', trans('store.trademark_image'));
            $form->image('food_image', trans('store.food_image'));
            $form->image('door_image', trans('store.door_image'));
            $form->image('other_image', trans('store.other_image'));

        });


        $form->saved(function (Form $form) {
            //status 状态:0=关闭,1=通过,2=未审核
            if ($form->model()->status == 1) {

                //审核成功
                DB::table('store')->where('id', $form->model()->id)->update(['handle_time' => date('Y-m-d H:i:s')]);
                $contacts_mobile = $form->model()->contacts_mobile;
                $exists = DB::table('store_users')->where(['username' => $contacts_mobile])->count();

                if (!$exists) {
                    $admin_user_id = DB::table('store_users')->insertGetId([
                        'username' => $contacts_mobile,
                        'name' => $contacts_mobile,
                        'password' => bcrypt(123456),
                        'store_id' => $form->model()->id
                    ]);

                    DB::table('store_role_users')->insert([
                        'role_id' => 1,
                        'user_id' => $admin_user_id
                    ]);

                    //商户配送方式写入
//                        $shipping = config('app.shipping_type');
//                        foreach($shipping as $k => $v){
//                            DB::table('shipping_type')->insert([
//                                'store_id' => $form->model()->id,
//                                'shipping_code' => $k,
//                                'shipping_name' => $v
//                            ]);
//                        }

                    //商户快递公司写入
                    $shipper = Ems::get();
                    foreach ($shipper as $k => $v) {
                        DB::table('store_shipper')->insert([
                            'store_id' => $form->model()->id,
                            'shipper_name' => $v->name,
                            'shipper_code' => $v->code,
                            'shipper_desc' => $v->desc,
                        ]);
                    }
                } else {
                    //启用商户登录
                    DB::table('store_users')->where('store_id', $form->model()->id)->update([
                        'enabled' => 1
                    ]);
                }

            } else {
                DB::table('store')->where('id', $form->model()->id)->update(['handle_time' => date('Y-m-d H:i:s')]);

                //禁止商户登录
                DB::table('store_users')->where('store_id', $form->model()->id)->update([
                    'enabled' => 0
                ]);
            }

        });

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
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
