<?php

namespace App\Admin\Controllers;

use App\Models\Area;
use App\Models\Banner;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class BannerController extends Controller
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
        $grid = new Grid(new Banner);
        $grid->id('Id');
        $grid->name(trans('banner.name'));
        $grid->href(trans('banner.href'));
        $grid->image(trans('banner.image'))->image('',50,50);
        $grid->href_type(trans('banner.href_type'))->using(Banner::getHrefTypeArr());
        $grid->page_type(trans('banner.page_type'))->using(Banner::getPageTypeArr());
        $grid->weigh(trans('banner.weigh'));

        $grid->filter(function($filter){
            $filter->like('name',trans('banner.name'));

        });

        $grid->actions(function($actions){
            $actions->disableView();
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
        $show = new Show(Banner::findOrFail($id));

        $show->id('Id');
        $show->name(trans('banner.name'));
        $show->href(trans('banner.href'));
        $show->image(trans('banner.image'));
        $show->href_type(trans('banner.href_type'));
        $show->page_type(trans('banner.page_type'));
        $show->weigh(trans('banner.weigh'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Banner);

//        $form->switch('scene', trans('banner.scene'));
        $form->select('href_type', trans('banner.href_type'))->options(Banner::getHrefTypeArr());
        $form->select('page_type', trans('banner.page_type'))->options(Banner::getPageTypeArr());
        $form->text('name', trans('banner.name'));
        $form->number('href', trans('banner.href'))->required();
        $form->image('image', trans('banner.image'))->uniqueName();
        $form->number('weigh', trans('banner.weigh'))->default(Banner::max('weigh')+1);

        // 抛出错误信息
        $form->saving(function ($form) {
            $pageType = Banner::getPageTypeArr();
            //所属页面：0=首页，1=抵扣专区，2=活动版块，3=抽奖商品列表，4=升级赚钱专区，5=逛街，6=签到页面，7 = '首页中部图片'
            $count = DB::table('banner')->where('page_type',$form->model()->page_type)->count();

            if(empty(request('banner'))){
                if(in_array($form->model()->page_type, [1,3,4,6,7]) ){
                    //限制1张
                    if($count >= 1){
                        $error = new MessageBag([
                            'title'   => '错误提示',
                            'message' => $pageType[$form->model()->page_type] . '只能添加1个',
                        ]);
                        return back()->with(compact('error'));
                    }


                }elseif($form->model()->page_type == 2){
                    //限制2张
                    if($count >= 2){
                        $error = new MessageBag([
                            'title'   => '错误提示',
                            'message' => $pageType[$form->model()->page_type] . '只能添加2个',
                        ]);
                        return back()->with(compact('error'));
                    }

                }
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


}
