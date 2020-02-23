<?php

namespace App\Tenancy\Controllers;

use App\Models\Config;
use App\Models\Goods;
use App\Models\Store;
use App\Models\Tyfon;
use App\Http\Controllers\Controller;
use App\Models\TyfonCategory;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\MessageBag;


class TyfonController extends Controller
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
            ->description($str)
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
        $grid = new Grid(new Tyfon);

        $grid->id('Id');
//        $grid->commentable_type(trans('tyfon.commentable_type'));
//        $grid->commentable_id(trans('tyfon.commentable_id'));
        $grid->title(trans('tyfon.title'));
//        $grid->content(trans('tyfon.content'));
//        $grid->images(trans('tyfon.images'));
//        $grid->category_id(trans('tyfon.category_id'));
        $grid->column('分类')->display(function(){
            return $this->fullCat;
        });
        $grid->mobile(trans('tyfon.mobile'));
//        $grid->province_id(trans('tyfon.province_id'));
//        $grid->city_id(trans('tyfon.city_id'));
//        $grid->district_id(trans('tyfon.district_id'));
        $grid->column('地区')->display(function(){
            return $this->fullArea;
        });
        $grid->detail_address(trans('tyfon.detail_address'));
        $grid->click_num(trans('tyfon.click_num'));
        $grid->created_at(trans('tyfon.created_at'));
        $grid->updated_at(trans('tyfon.updated_at'));
        $grid->goods_id(trans('tyfon.goods_id'))->display(function($goodsId){
            if($goods = Goods::find($goodsId)) return $goods->name;
        });

        $grid->model()->where(function($query){
            $query->where('commentable_type','store')->where('commentable_id',Admin::user()->store_id);
        })->orderBy('id','desc');

        $grid->filter(function($filter){
            $filter->where(function($query){
                $query->where('cat1',"{$this->input}")->orWhere('cat2',"{$this->input}")->orWhere('cat3',"{$this->input}");
            },'分类')->select(TyfonCategory::selectOptions());
            $filter->like('title',trans('tyfon.title'));
            $filter->between('created_at',trans('admin.created_at'))->datetime();
        });

        $grid->actions(function($actions){
            $id = $actions->getKey();
            $a = "/tenancy/tyfon_comment?tyfon_id={$id}";
//                dump($a);exit;
            $actions->prepend('<a href='.$a.' > 评论 <i class=""></i>&nbsp;</a>');
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
        $show = new Show(Tyfon::findOrFail($id));

        $show->id('Id');
        $show->commentable_type(trans('tyfon.commentable_type'));
        $show->commentable_id(trans('tyfon.commentable_id'));
        $show->title(trans('tyfon.title'));
        $show->content(trans('tyfon.content'));
        $show->images(trans('tyfon.images'));
        $show->category_id(trans('tyfon.category_id'));
        $show->mobile(trans('tyfon.mobile'));
        $show->province_id(trans('tyfon.province_id'));
        $show->city_id(trans('tyfon.city_id'));
        $show->district_id(trans('tyfon.district_id'));
        $show->detail_address(trans('tyfon.detail_address'));
        $show->click_num(trans('tyfon.click_num'));
        $show->created_at(trans('tyfon.created_at'));
        $show->updated_at(trans('tyfon.updated_at'));
        $show->goods_id(trans('tyfon.goods_id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Tyfon);
        $store = Store::find(Admin::user()->store_id);

        $form->hidden('commentable_type')->default('store');
        $form->hidden('commentable_id')->default(Admin::user()->store_id);
        $form->hidden('province_id')->default($store->province_id);
        $form->hidden('city_id')->default($store->city_id);
        $form->hidden('district_id')->default($store->district_id);
        $form->select('cat1', trans('goods.cat1'))->options(
            TyfonCategory::where('pid' , 0)->pluck('name' , 'id')
        )->load('cat2','/api/backend/getTyfonChildCategory')->rules('required');
        $form->select('cat2', trans('goods.cat2'))->options(function($id){
            return TyfonCategory::where('id' , $id)->pluck('name' , 'id');//回显
        })->load('cat3','/api/backend/getTyfonChildCategory')->rules('required');
        $form->select('cat3', trans('goods.cat3'))->options(function($id){
            return TyfonCategory::where('id' , $id)->pluck('name' , 'id');//回显
        })->rules('required');
        $form->text('title', trans('tyfon.title'))->rules('required');
        $form->textarea('content', trans('tyfon.content'))->rules('required');
        $form->multipleImage('images', trans('tyfon.images'))->removable()->help(trans('admin.ctrl'))->uniqueName();
        $form->mobile('mobile', trans('tyfon.mobile'))->rules('required');
//        $form->number('province_id', trans('tyfon.province_id'));
//        $form->number('city_id', trans('tyfon.city_id'));
//        $form->number('district_id', trans('tyfon.district_id'));
        $form->text('detail_address', trans('tyfon.detail_address'))->rules('required');
//        $form->number('click_num', trans('tyfon.click_num'));
        $form->select('goods_id', trans('tyfon.goods_id'))->options('/tenancy/api/goods')->default(0);

        $form->saving(function(Form $form){
            $res = $this->checkTodayFreeCount();
            if($res['code'] == 0){
                $error = new MessageBag([
                    'title'   => '错误提示',
                    'message' => $res['msg']
                ]);

                return back()->with(compact('error'));
//                admin_toastr('余额不足', 'error', ['timeOut' => 3000]);

            }
        });

        $form->saved(function (Form $form) {
            $res = $this->checkTodayFreeCount();
            if($res['code'] == 1){
                $this->storeTyfonAccount();
            }
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

    protected function checkTodayFreeCount(){
        //免费次数是否用完
        $baseConfig = Config::getConfigValue();
        $store = Store::find(Admin::user()->store_id);

        $count = Tyfon::where(function ($query) use($store) {
            $query->where('commentable_type', 'store')
                ->where('commentable_id', $store->id)
                ->whereDate('created_at', date('Y-m-d'));
        })->count();

        if ($count >= $baseConfig['base.store_free_tyfon']) {
            if ($store['account'] < $baseConfig['base.store_tyfon_account']){
//                admin_toastr('余额不足', 'error', ['timeOut' => 3000]);
                return ['code' => 0, 'msg' => '余额不足'];
            }else{
                //扣余额
                return  ['code' => 1, 'msg' => '扣余额'];
            }

        }else{
            //有免费次数，不扣余额
            return ['code' => 2, 'msg' => '有免费次数，不扣余额'];
        }
    }

    protected function storeTyfonAccount(){
        //扣除商户余额
        $baseConfig = Config::getConfigValue();
        $store = Store::find(Admin::user()->store_id);
        Store::storeAccountLog(1,1,$store->id, '-' . $baseConfig['base.store_tyfon_account'],'发布大喇叭');

    }
}
