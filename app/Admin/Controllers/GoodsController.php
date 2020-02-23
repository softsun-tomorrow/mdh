<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\Config;
use App\Models\Goods;
use App\Http\Controllers\Controller;
use App\Models\GoodsImages;
use App\Models\GoodsSpec;
use App\Models\Spu;
use App\Models\Store;
use App\Models\Tag;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use App\Models\StoreGoodsCategory;

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
        $grid->store_id(trans('goods.store_id'))->display(function($storeId){
            return optional(Store::find($storeId))->shop_name;
        });
        $grid->name(trans('goods.name'))->limit(20)->expand(function ($model) {
            //规格
            $goodsSpec = $model->goods_spec()->take(20)->get()->map(function($goodsSpec){
                return $goodsSpec->only(['goods_specs','goods_stock','goods_price']);
            });
            return new \Encore\Admin\Widgets\Table(['单品规格值', '库存', '价格'], $goodsSpec->toArray());
        });
        $grid->type(trans('goods.type'))->using(Goods::getTypeArr());
        $grid->prom_type(trans('goods.prom_type'))->using(Goods::getPromTypeArr());
        $grid->cover(trans('goods.cover'))->image('',30,30);


        $grid->column('分类')->display(function(){
            return $this->fullCat;
        });
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
        $grid->is_rec(trans('goods.is_rec'))->switch();
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

//        $grid->actions(function($actions){
////            $goods_id = $actions->getKey();
////            $a = "/tenancy/goods_spec?goods_id={$goods_id}";
//////                dump($a);exit;
////            $actions->prepend('<a href='.$a.' >规格 <i class=""></i></a>');
////        });

        $grid->filter(function($filter){
            $filter->where(function($query){
                $query->where('cat1',"{$this->input}")->orWhere('cat2',"{$this->input}")->orWhere('cat3',"{$this->input}");
            },'分类')->select(Category::selectOptions());

            $filter->where(function($query){
                $query->whereHas('store',function($query){
                    $query->where('shop_name','like', "%{$this->input}%")->orWhere('contacts_mobile','like',"%{$this->input}%");
                });
            },'店铺名或手机号');
            $filter->like('name',trans('goods.name'));
            $filter->in('is_on_sale',trans('goods.is_on_sale'))->checkbox(Goods::getIsOnSaleText());
//            $filter->in('is_hot',trans('goods.is_hot'))->checkbox(Goods::getIsHotText());
            $filter->in('status',trans('goods.status'))->checkbox(Goods::getStatusText());
            $filter->between('created_at',trans('admin.created_at'))->datetime();
        });
        $grid->model()->orderBy('id','desc');

        $grid->disableCreateButton();
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

        $show->column('分类')->as(function(){
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
    protected function form_()
    {
        $form = new Form(new Goods);
        $form->display('name', trans('goods.name'));

        $form->number('click_nums', trans('goods.click_nums'));
        $form->number('comment_nums', trans('goods.comment_nums'));
        $form->number('collect_nums', trans('goods.collect_nums'));
        $form->number('sale_nums', trans('goods.sale_nums'));
//        $form->number('weigh', trans('goods.weigh'));
        $form->switch('is_rec', trans('goods.is_rec'));
        $form->switch('is_on_sale', trans('goods.is_on_sale'));

//        $form->switch('is_hot', trans('goods.is_hot'));
        $form->radio('status', trans('goods.status'))->options(Goods::getStatusArr());
//        $form->editor('content');
        $form->footer(function ($footer) {
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });
        return $form;
    }


    protected function form()
    {
        $form = new Form(new Goods);
        Admin::disablePjax();
        $form->row(function ($row) use ($form) {
            $row->hidden('status')->default(1);
            $row->width(12)->radio('type', trans('goods.type'))->options(Goods::getTypeArr())->readOnly();
            $row->width(6)->select('cat1', trans('goods.cat1'))->options(function () {

                return Category::pluck('name', 'id');
            })->load('cat2', '/api/backend/getChildCategory')->rules('required')->readOnly();
            $row->width(6)->select('cat2', trans('goods.cat2'))->options(function ($id) {
                return Category::where('id', $id)->pluck('name', 'id');//回显
            })->rules('required')->readOnly();
            $row->width(12)->text('name', trans('goods.name'))->rules('required')->help('请输入宝贝名称+类目')->required()->readOnly();
            $row->width(12)->textarea('description', trans('goods.description'))->readOnly();

            $row->width(6)->select('store_cat1', trans('goods.store_cat1'))->options(
                StoreGoodsCategory::where('pid', 0)->pluck('name', 'id')
            )->load('store_cat2', '/api/backend/getChildStoreGoodsCategory')->rules('required')->readOnly();
            $row->width(6)->select('store_cat2', trans('goods.store_cat2'))->options(function ($id) {
                return StoreGoodsCategory::where('id', $id)->pluck('name', 'id');//回显
            })->required()->readOnly();
            $row->width(12)->image('cover', trans('goods.cover'))->rules('required')->uniqueName()->required()->setWidth(4, 2)->readOnly();
            $row->hasMany('goods_images', '详情轮播图', function (Form\NestedForm $form) {
                $form->image('image_url', trans('goods_images.image_url'))->uniqueName()->required()->help('建议上传800*800')->readOnly();
                $form->number('weigh', trans('goods_images.weigh'))->default(GoodsImages::max('weigh') + 1)->readOnly();
            });
            $row->width(12)->multipleImage('content', trans('goods.content'))->removable()->help('按ctrl键可选择多张,长图横竖比例为2:3，最小长度为480，建议使用800*1200')->uniqueName()->readOnly();
            $row->width(12)->textarea('notice', trans('goods.notice'))->readOnly();

//            $row->divider('规格');
//            $goodsId = (int)request()->route()->parameter('good');
//            //商品是否设置了规格
//            if ($goodsId) { //编辑时
//                $isSetSpec = DB::table('goods_spec')->where('goods_id', $goodsId)->count();
//                if ($isSetSpec) {
//                    //显示规格列表
//                    $goodsSpec = GoodsSpec::where('goods_id', $goodsId)->select('id', 'goods_specs', 'goods_stock', 'goods_price', 'spec_keys','team_price')->get();
//                    $goodsSpecArr = json_decode(json_encode($goodsSpec), true);
//
//                    $tableArr = [];
//                    foreach ($goodsSpecArr as $k => $v) {
//                        $tableArr[$k]['goods_specs'] = $v['goods_specs'];
//                        $tableArr[$k]['goods_stock'] = "<input type='text' name='item" . $v['spec_keys'] . "[goods_stock]' value='" . $v['goods_stock'] . "'/>";
//                        $tableArr[$k]['goods_price'] = "<input type='text' name='item" . $v['spec_keys'] . "[goods_price]' value='" . $v['goods_price'] . "'/>";
//                        $tableArr[$k]['team_price'] = "<input type='text' name='item" . $v['spec_keys'] . "[team_price]' value='" . $v['team_price'] . "'/>";
//                        //$tableArr[$k]['handle'] = "<a class='btn' href='/tenancy/goods_spec/".$v['id']."/edit'>修改</a>";
//                    }
//                    $headers = ['单品规格', '库存', '价格', '拼团价格'];
//                    $table = new Table($headers, $tableArr);
//
//                    $row->html($table->render());
//                } else {
//                    $html = $this->getSpecHtml();
//                    $row->html($html);
//                    $row->html("<div style='text-align: center'><a class='btn-success ' id='buildsku' >点击生成商品规格</a></div>");
//                    $script = $this->getSpecScript();
//                    Admin::script($script);
//                }
//            } else {
//                $html = $this->getSpecHtml();
//                $row->html($html);
//                $row->html("<div style='text-align: center'><a class='add btn btn-success btn-sm' id='buildsku' >生成商品规格</a></div>");
//                $script = $this->getSpecScript();
//                Admin::script($script);
//            }

//            $row->divider('属性参数');
//            $row->html('<div id="spu_form"></div>');
//            $spuScript = $this->getSpuScript();
//            Admin::script($spuScript);

            $row->divider();
            $row->width(12)->decimal('self_rebate', trans('goods.self_rebate'))->default(0.10)->help('元')->readOnly();
            $row->width(12)->decimal('share_rebate', trans('goods.share_rebate'))->default(0.10)->help('元')->readOnly();
            $row->width(12)->decimal('shipper_fee', trans('goods.shipper_fee'))->default(0.00)->readOnly();


            $row->width(6)->multipleSelect('tags', trans('goods.tags'))->options(Tag::all()->pluck('name', 'id'))->readOnly();
            $row->width(12)->number('store_count', trans('goods.store_count'))->rules('required')->default(100)->readOnly();
            $row->width(12)->decimal('shop_price', trans('goods.shop_price'))->default(0.00)->required()->readOnly();
            $row->width(12)->number('exchange_integral', trans('goods.exchange_integral'))->default(0)->help('可使用麦穗数量')->readOnly();
            $row->width(12)->switch('is_on_sale', trans('goods.is_on_sale'))->readOnly();
            $row->width(12)->switch('is_store_rec', trans('goods.is_store_rec'))->readOnly();
            $row->width(12)->switch('is_return', trans('goods.is_return'))->readOnly();

            //审核操作
            $row->divider('审核操作');
            $row->number('click_nums', trans('goods.click_nums'));
            $row->number('comment_nums', trans('goods.comment_nums'));
            $row->number('collect_nums', trans('goods.collect_nums'));
            $row->number('sale_nums', trans('goods.sale_nums'));
//        $form->number('weigh', trans('goods.weigh'));
            $row->switch('is_rec', trans('goods.is_rec'));
            $row->switch('is_on_sale', trans('goods.is_on_sale'));

//        $form->switch('is_hot', trans('goods.is_hot'));
            $row->radio('status', trans('goods.status'))->options(Goods::getStatusArr());
//            $row->ignore(['column1', 'column2', 'column3']);
        });




        return $form;
    }


//    public function getSpuScript()
//    {
//        $goodsId = request()->route()->parameter('good');
//        $goodsSpu = [];
//        if ($goodsId) {
//            $goodsSpu = DB::table('goods_spu')->where('goods_id', $goodsId)->pluck('spu_value', 'spu_id');
//            //$goodsSpu = DB::table('goods_spu')->where('goods_id', $goodsId)->get();
//        }
//
////        $plucked = $goodsSpu->pluck('spu_value', 'spu_id');
////        $data = $plucked->all();
////        $spuArr = [];
////        foreach ($goodsSpu as $k => $v){
////            $spuArr[$v->spu_id] = $v->spu_value;
////        }
//        $goods_spu = json_encode($goodsSpu);
////dd($goodsSpu);
//        return <<<EOT
//$('.pull-right').attr('class','pull-left');
//var goods_spu = {$goods_spu};
//
//console.log(goods_spu);
//
//var cat2 = $('select[name="cat2"]').val();
//if(cat2) getSpu(cat2,goods_spu);
//$(document).on('change','.cat2',function(){
//    var cat2 = $(this).val();
//    getSpu(cat2);
//});
//
//function getSpu(cat2,goods_spu = null){
//var url = '/api/backend/spu?cat2=' + cat2;
//    $.ajax({
//        method:'get',
//        url:url,
//        success:function(ret){
//            //console.log(ret);
//            $('#spu_form').html('');
//            //属性值
//            var  html=""
//            for(var index in ret){
//<!--                console.log(ret[index]['values']);-->
//<!--                //console.log(ret[index]);-->
//<!--                -->
//<!--                -->
//<!--                values = [];-->
//<!--                var values = ret[index]['values'].split(',');-->
//                html = '<table class="table table-bordered" id="goods_spu_table">';
//                    html+= '<tbody>';
//                    html += '<tr class="spu_'+ ret[index]['id'] +'">';
//                        html += '<td> '+ ret[index]['name'] +'</td>';
//
//                         if(goods_spu){
//                            //console.log('goods_spu'+goods_spu);
//                            $.each(goods_spu, function(k,v){
//                                if(k == ret[index]['id']){
//                                    html += '<td><input type="text" name="spu_'+ ret[index]['id'] +'[]" value="'+v+'"/> </td>';
//                                }
//                            });
//
//                         }else{
//                             html += '<td><input type="text" name="spu_'+ ret[index]['id'] +'[]" value=""/> </td>';
//                         }
//
//                         html += '</td>';
//                    html += '</tr>';
//
//                    html += '</tbody>';
//                html += '</table>';
//                $('#spu_form').append(html);
//            }
//        }
//
//    });
//}
//
//EOT;
//
//    }
//
//    //规格
//    protected function getSpecHtml()
//    {
//        $html = '<div id="ac" data-storeid="' . Admin::user()->store_id . '">';
//        $html .= '<table class="table table-bordered" id="goods_spec_table1">';
//        $html .= '</table>';
//        $html .= '</div>';
//        return $html;
//    }
//
//    //规格
//    protected function getSpecScript()
//    {
//        $goodsSpec = [];
//        $goods_spec = json_encode($goodsSpec);
//        $store_id = Admin::user()->store_id;
//
//        return <<<EOT
//    var goods_spec = {$goods_spec};
//    var store_id = {$store_id};
//    var cat2 = $('select[name="cat2"]').val();
//    //console.log('store_id:'+store_id);
//    if(cat2) getSpec(cat2, store_id,goods_spec);
//    $(document).on('change','.cat2',function(){
//        var cat2 = $(this).val();
//        getSpec(cat2,store_id);
//    });
//
//    //获取规格列表
//    $('#buildsku').click(function(){
//        getGoodsSpecList();
//    });
//
//    function getSpec(cat2, store_id, goods_spec = null){
//        //获取规格名
//        var url = '/api/backend/attribute/' + store_id + '?q='+cat2;
//        $.ajax({
//            method:'get',
//            url:url,
//            success:function(ret){
//                $('#goods_spec_table1').html('');
//                //规格值
//                  var  html=""
//                for(var index in ret){
//                    //console.log(ret[index]['text']);
//                    html = '<tr>';
//                    html +=    '<td class="aaa">'+ret[index]['text']+'</td>';
//                    html +=     '<td id=attr_key_'+ret[index]['id']+'></td>';
//                    html += '</tr>';
//                    get_attribute_value(ret[index]['id']);
//                    $('#goods_spec_table1').append(html);
//                }
//            }
//
//        });
//    }
//
//
//
//
//    function get_attribute_value(key_id){
//        //console.log(key_id);
//
//        var url = '/api/backend/attribute_values?q='+key_id;
//
//        $.ajax({
//            method:'get',
//            url : url,
//            success: function(ret){
//                 var  html=""
//                for(var index in ret){
//                        html += '<div class="col-sm-8" id="tt"><label></label><input type="checkbox" name="specvalues[]" value="'+ret[index]['id']+'" class=specvalues_'+ret[index]['id']+' >'+ret[index]['text']+'</div>';
//                }
//                //console.log(html);
//                //console.log($('#attr_key_'+key_id))
//                $('#attr_key_'+key_id).html(html);
//            }
//        });
//    }
//
//    function getGoodsSpecList(){
//        var url = '/api/backend/getGoodsSpecList';
//
//        var specvalues = [];
//        $("input[name='specvalues[]']:checked").each(function(i){
//            specvalues.push($(this).val())
//        })
//
//        //console.log(specvalues);
//        if(specvalues.length == 0){
//            layer.msg('请先选择规格', {
//              icon: 5,
//              time: 2000 //2秒关闭（如果不配置，默认是3秒）
//            }, function(){
//              //do something
//            });
//        }
//        $.ajax({
//            method:'post',
//            data:{specvalues:specvalues},
//            url:url,
//            success:function(ret){
//
//                 html = '<div class="col-md-12"><div class="form-group"><label class=" control-label"></label><div class=""><table class="table "><thead><tr><th>单品规格</th><th>库存</th><th>价格</th><th>拼团价格</th></tr></thead><tbody>';
//                        if(ret.data){
//                            $.each(ret.data, function(k, v){
//                                html += '<tr>';
//                                html += '<td>'+ v.text +'</td>';
//                                html += '<td><input type="text" name="item['+ v.id +'][goods_stock]" value=""></td>';
//                                html += '<td><input type="text" name="item['+ v.id +'][goods_price]" value=""></td>';
//                                html += '<td><input type="text" name="item['+ v.id +'][team_price]" value=""></td>';
//                                html += '</tr>';
//                            });
//                        }
//
//
//                 html += '</tbody></table></div></div></div>';
//
//                 //console.log(html);
//
//                 $("#ac").replaceWith(html);
//                 $('#buildsku').hide();
//            }
//
//        });
//    }
//
//
//
//
//
//EOT;
//    }


}
