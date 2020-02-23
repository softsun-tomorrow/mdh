<?php

namespace App\Tenancy\Controllers;

use App\Models\Category;
use App\Models\Config;
use App\Models\Goods;
use App\Http\Controllers\Controller;
use App\Models\GoodsImages;
use App\Models\Store;
use App\Models\StoreGoodsCategory;
use App\Models\Tag;

use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class GoodsController extends Controller
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
        $grid = new Grid(new Goods);

        $grid->id('Id');
        $grid->name(trans('goods.name'))->limit(20);
        $grid->type(trans('goods.type'))->using(Goods::getTypeArr());
        $grid->prom_type(trans('goods.prom_type'))->using(Goods::getPromTypeArr());
        $grid->cover(trans('goods.cover'))->image('', 30, 30);


        $grid->column('分类')->display(function () {
            return $this->fullCat;
        });

        $grid->tags(trans('goods.tags'))->display(function ($tags) {

            $tagArr = Tag::whereIn('id', explode(',', $tags))->pluck('name', 'id')->toArray();
            return $tagArr;
        })->implode('-');

//        $grid->store_id(trans('goods.store_id'));
//        $grid->store_count(trans('goods.store_count'));
//        $grid->click_nums(trans('goods.click_nums'));
//        $grid->comment_nums(trans('goods.comment_nums'));
//        $grid->collect_nums(trans('goods.collect_nums'));
//        $grid->sale_nums(trans('goods.sale_nums'));

        $grid->shop_price(trans('goods.shop_price'));
        $grid->is_on_sale(trans('goods.is_on_sale'))->using(Goods::getIsOnSaleText());
//        $grid->on_time(trans('goods.on_time'));
//        $grid->weigh(trans('goods.weigh'));
//        $grid->is_rec(trans('goods.is_rec'));
//        $grid->is_hot(trans('goods.is_hot'));
//        $grid->distribut(trans('goods.distribut'));
//        $grid->spec_list(trans('goods.spec_list'));
        $grid->status(trans('goods.status'))->using(Goods::getStatusText());
        $grid->created_at(trans('goods.created_at'));
//        $grid->updated_at(trans('goods.updated_at'));
//        $grid->content(trans('goods.content'));
//        $grid->spu(trans('goods.spu'));
//        $grid->exchange_integral(trans('goods.exchange_integral'));
//        $grid->give_integral(trans('goods.give_integral'));

        $grid->actions(function ($actions) {
            $actions->disableView();

            $goods_id = $actions->getKey();
            $a = "/tenancy/goods_spec?goods_id={$goods_id}";
//                dump($a);exit;
            $actions->prepend('<a href=' . $a . ' >规格 <i class=""></i></a>');
        });

        $grid->filter(function ($filter) {
            $filter->where(function ($query) {
                $query->where('cat1', "{$this->input}")->orWhere('cat2', "{$this->input}")->orWhere('cat3', "{$this->input}");
            }, '分类')->select(Category::selectOptions());
            $filter->like('name', trans('goods.name'));
            $filter->in('is_on_sale', trans('goods.is_on_sale'))->checkbox(Goods::getIsOnSaleText());
//            $filter->in('is_hot',trans('goods.is_hot'))->checkbox(Goods::getIsHotText());
            $filter->in('status', trans('goods.status'))->checkbox(Goods::getStatusText());
            $filter->between('created_at', trans('admin.created_at'))->datetime();
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
        $show = new Show(Goods::findOrFail($id));

        $show->id('Id');
        $show->name(trans('goods.name'));

        $show->column('分类')->as(function () {
            return $this->fullCat;
        });

//        $show->store_id(trans('goods.store_id'));
        $show->store_count(trans('goods.store_count'));
        $show->click_nums(trans('goods.click_nums'));
        $show->comment_nums(trans('goods.comment_nums'));
        $show->collect_nums(trans('goods.collect_nums'));
        $show->sale_nums(trans('goods.sale_nums'));
        $show->shop_price(trans('goods.shop_price'));
        $show->is_on_sale(trans('goods.is_on_sale'))->using(Goods::getIsOnSaleText());
//        $show->on_time(trans('goods.on_time'));
        $show->weigh(trans('goods.weigh'));
//        $show->is_rec(trans('goods.is_rec'));
//        $show->is_hot(trans('goods.is_hot'));
//        $show->distribut(trans('goods.distribut'));
//        $show->spec_list(trans('goods.spec_list'));
        $show->status(trans('goods.status'))->using(Goods::getStatusText());
        $show->created_at(trans('goods.created_at'));
        $show->updated_at(trans('goods.updated_at'));
        $show->cover(trans('goods.cover'))->image();
        $show->content(trans('goods.content'))->unescape();
//        $show->spu(trans('goods.spu'))->unescape();
        $show->exchange_integral(trans('goods.exchange_integral'));
        $show->give_integral(trans('goods.give_integral'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Goods);

        $form->hidden('store_id')->default(Admin::user()->store_id);
        $form->hidden('status')->default(1);

        $form->text('name', trans('goods.name'))->rules('required');
        $form->textarea('description', trans('goods.description'));
//            $form->tags('keywords', trans('goods.keywords'));
        $form->radio('type', trans('goods.type'))->options(Goods::getTypeArr());
        $form->select('cat1', trans('goods.cat1'))->options(Goods::getCat1SelectOptions(Admin::user()->store_id))->load('cat2', '/api/backend/getChildCategory')->rules('required');
        $form->select('cat2', trans('goods.cat2'))->options(function ($id) {
            return Category::where('id', $id)->pluck('name', 'id');//回显
        })->rules('required');

//        $form->select('cat1', trans('goods.cat1'))->options('/tenancy/api/cat1')->load('cat2', '/api/backend/getChildCategory')->rules('required');
//        $form->select('cat2', trans('goods.cat2'))->options(function ($cat2) {
//            return Category::where('id', $cat2)->pluck('name', 'id');
//        })->rules('required');

        $form->select('store_cat1', trans('goods.store_cat1'))->options(
            StoreGoodsCategory::where('pid', 0)->where('store_id', Admin::user()->store_id)->pluck('name', 'id')
        )->load('store_cat2', '/api/backend/getChildStoreGoodsCategory')->rules('required');
        $form->select('store_cat2', trans('goods.store_cat2'))->options(function ($id) {
            return StoreGoodsCategory::where('id', $id)->pluck('name', 'id');//回显
        });

        $form->multipleSelect('tags', trans('goods.tags'))->options(Tag::all()->pluck('name', 'id'));
        $form->image('cover', trans('goods.cover'))->rules('required')->uniqueName()->required();

        $form->number('store_count', trans('goods.store_count'))->rules('required')->default(100);
        $form->decimal('shop_price', trans('goods.shop_price'))->default(0.00)->required();
        $form->decimal('self_rebate', trans('goods.self_rebate'))->default(0.10)->help('元');
        $form->decimal('share_rebate', trans('goods.share_rebate'))->default(0.10)->help('元');
        $form->decimal('shipper_fee', trans('goods.shipper_fee'))->default(0.00);
        $form->switch('is_on_sale', trans('goods.is_on_sale'));
        $form->switch('is_store_rec', trans('goods.is_store_rec'));
        $form->switch('is_return', trans('goods.is_return'));
        $form->number('exchange_integral', trans('goods.exchange_integral'))->default(0)->help('可使用麦穗数量');
        $form->textarea('notice', trans('goods.notice'));
        $form->multipleImage('content', trans('goods.content'))->removable()->help('按ctrl键可选择多张,请上传正方形图片')->uniqueName();

        $form->divider('商品相册');
        $form->hasMany('goods_images', '商品相册', function (Form\NestedForm $form) {
            $form->image('image_url', trans('goods_images.image_url'))->uniqueName()->required();
            $form->number('weigh', trans('goods_images.weigh'))->default(GoodsImages::max('weigh') + 1);
        });

//            $form->divider('属性参数');
//            $form->html('<div id="spu_form"></div>');
//            $spuScript = $this->getSpuScript();
//            Admin::script($spuScript);

        $form->hasMany('goods_spu', '商品属性', function (Form\NestedForm $form) {
            $form->text('spu_name', '属性名');
            $form->text('spu_value', '属性值');
        });


        // 在表单提交前调用
        $form->submitted(function (Form $form) {
            dd( $form->model()->name);
        });
        $form->saved(function (Form $form) {
            $cover = $form->model()->cover;
            if ($cover) {
                list($width, $height) = getimagesize('./uploads/' . $cover);
                DB::table('goods')->where('id', $form->model()->id)->update([
                    'cover_width' => $width,
                    'cover_height' => $height
                ]);
            }

            //升级，抽奖，活动版块需要审核
            if ($form->model()->type != 0 || $form->model()->prom_type == 3) {
                DB::table('goods')->where('id', $form->model()->id)->update(['status' => 0]);
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


    public function getSpuScript()
    {
        return <<<EOT

$(document).on('change','.cat2',function(){
    var cat2 = $(this).val();
    var url = '/api/backend/spu?cat2=' + cat2;
    $.ajax({
        method:'get',
        url:url,
        success:function(ret){
            $('#spu_form').html('');
            //属性值
            var  html=""
            for(var index in ret){
                console.log(ret[index]['values']);
                
                values = [];
                var values = ret[index]['values'].split(',');
                html = '<table class="table table-bordered" id="goods_spu_table">';
                    html+= '<tbody>';
                    html += '<tr class="spu_'+ ret[index]['id'] +'">';
                        html += '<td> '+ ret[index]['name'] +'</td>';
                        html += '<td><select name="spu_'+ ret[index]['id'] +'[]">';
                                html += '<option value="">不限</option>';
                                $.each(values, function(k,v){
                                    html += '<option value="'+ v +'">'+ v +'</option>';
                                });
                            html += '</select></td>';
                    html += '</tr>';
                    
                    html += '</tbody>';
                html += '</table>';
                $('#spu_form').append(html);
            }
        }

    });
    
    
    

    
    
});

EOT;

    }


}
