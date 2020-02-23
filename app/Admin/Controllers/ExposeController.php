<?php

namespace App\Admin\Controllers;

use App\Models\Expose;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Models\UserCoupon;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ExposeController extends Controller
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
        $grid = new Grid(new Expose);

        $grid->id('Id');
        $grid->user_id(trans('expose.user_id'))->display(function($userId){
            if($user = User::find($userId)) return $user->name;
        });


        $grid->store_id(trans('expose.store_id'))->display(function($storeId){
            if($store = Store::find($storeId)) return $store->shop_name ;
        });
        $grid->content(trans('expose.content'))->limit(20);
        $grid->imgs(trans('expose.imgs'))->image('',50,50);
        $grid->status(trans('expose.status'))->using(Expose::getStatusArr());
        $grid->handle_type(trans('expose.handle_type'))->using(Expose::getHandleTypeArr());
        $grid->created_at(trans('expose.created_at'));
        $grid->updated_at(trans('expose.updated_at'));

        $grid->disableCreateButton();
        $grid->disableExport();
//        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->actions(function($actions){
            $actions->disableView();
        });


        $grid->filter(function($filter){
            $filter->where(function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%")->orWhere('mobile', 'like', "%{$this->input}%");
                });
            }, '用户名或手机号');

            $filter->where(function ($query) {
                $query->whereHas('store', function ($query) {
                    $query->where('shop_name', 'like', "%{$this->input}%")->orWhere('contacts_mobile', 'like', "%{$this->input}%");
                });
            }, '店铺名或手机号');
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
        $show = new Show(Expose::findOrFail($id));

        $show->id('Id');
        $show->user_id(trans('expose.user_id'));
        $show->store_id(trans('expose.store_id'));
        $show->content(trans('expose.content'));
        $show->imgs(trans('expose.imgs'));
        $show->status(trans('expose.status'));
        $show->handle_type(trans('expose.handle_type'));
        $show->created_at(trans('expose.created_at'));
        $show->updated_at(trans('expose.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Expose);

        $form->display('user_id', trans('expose.user_id'))->with(function($userId){
            if($user = User::find($userId)) return $user->name;
        });
        $form->display('store_id', trans('expose.store_id'))->with(function($storeId){
            if($store = Store::find($storeId)) return $store->shop_name;
        });
        $form->display('content', trans('expose.content'));
        $form->display('imgs', trans('expose.imgs'))->with(function($imgs){
            if($imgs){
                $html = '';
                foreach($imgs as $img){
                    $html .= "<img src='/uploads/".$img."'/>";
                }
                return $html;
            }
        });
        $form->radio('status', trans('expose.status'))->options(Expose::getStatusArr());
        $form->select('handle_type', trans('expose.handle_type'))->options(Expose::getHandleTypeArr());
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
