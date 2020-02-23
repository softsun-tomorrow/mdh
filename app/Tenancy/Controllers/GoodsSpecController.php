<?php

namespace App\Tenancy\Controllers;

use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsSpecController extends Controller
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
        Admin::disablePjax();
        $goods_id = request()->get('goods_id');
//        dd(request()->path());
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->specform($goods_id));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GoodsSpec);

        $grid->id('Id');
        $grid->goods_id(trans('goods_spec.goods_id'))->display(function($goodsId){
            return Goods::find($goodsId)->name;
        });
//        $grid->spec_keys(trans('goods_spec.spec_keys'));
        $grid->goods_specs(trans('goods_spec.goods_specs'));
        $grid->goods_stock(trans('goods_spec.goods_stock'))->editable();
        $grid->goods_price(trans('goods_spec.goods_price'))->editable();
//        $grid->status(trans('goods_spec.status'))->switch(GoodsSpec::getStatusArr());


        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableFilter();

//        $grid->disableActions();
        $grid->actions(function($actions){
            $actions->disableDelete();
            $actions->disableView();
        });

        $grid->tools(function ($tools) {
            $goods = Goods::find(request()->get('goods_id'));
            $GoodsSpecCount = DB::table('goods_spec')->where('goods_id',request()->get('goods_id'))->count();
            if(!$GoodsSpecCount){
                $href = '/tenancy/goods_spec/create?goods_id=' . request()->get('goods_id');
                $tools->append("<a href='". $href ."' class='btn btn-sm btn-success' style='float: right;'>
<i class='fa fa-save'></i>&nbsp;&nbsp;设置规格
</a>");
            }

        });
        $grid->model()->where('goods_id',request()->get('goods_id'))->orderBy('id','desc');

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
        $show = new Show(GoodsSpec::findOrFail($id));

        $show->id('Id');
        $show->goods_id(trans('goods_spec.goods_id'));
        $show->spec_keys(trans('goods_spec.spec_keys'));
        $show->goods_specs(trans('goods_spec.goods_specs'));
        $show->goods_stock(trans('goods_spec.goods_stock'));
        $show->goods_price(trans('goods_spec.goods_price'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new GoodsSpec);

//        $form->number('goods_id', trans('goods_spec.goods_id'));
//        $form->text('spec_keys', trans('goods_spec.spec_keys'));

        $form->display('goods_specs', trans('goods_spec.goods_specs'));
        $form->number('goods_stock', trans('goods_spec.goods_stock'));
        $form->decimal('goods_price', trans('goods_spec.goods_price'))->default(0.00);
        $form->switch('status', trans('goods_spec.status'));

        return $form;
    }


    /**
     * 添加商品规格
     * @param $goods_id
     * @return Form
     */
    protected function specform($goods_id)
    {

        $html = $this->getAttributeHtml();

        $goods = Goods::find($goods_id);
        $form = new Form(new GoodsSpec);

        $form->hidden('goods_id')->default($goods_id);
        $form->html($html, $label = '商品规格');
        $form->html( "<div style='text-align: center'><button  class='buildsku' data-categoryid='".$goods['cat2']."' >生成商品规格</button></div>");

        $script = $this->getAttrScript();
        Admin::script($script);

        $form->setAction('/tenancy/goods_spec/addspec?goods_id=' . $goods_id);

        $form->tools(function (Form\Tools $tools) {

            // 去掉`列表`按钮
            $tools->disableList();

            // 去掉`删除`按钮
            $tools->disableDelete();

            // 去掉`查看`按钮
            $tools->disableView();

            // 添加一个按钮, 参数可以是字符串, 或者实现了Renderable或Htmlable接口的对象实例
//            $tools->add('<a class="btn btn-sm btn-danger"><i class="fa fa-trash"></i>&nbsp;&nbsp;delete</a>');
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

    public function addspec(Request $request){
        $param = $request->post();
        if(!isset($param['specvalues'])) $param['specvalues'] = [];
        GoodsSpec::saveSpec($param['goods_id'],$param['specvalues']);
        $redirect = app('redirect');
        return $redirect->away($redirect->getUrlGenerator()->to('/tenancy/goods_spec?goods_id=' . $param['goods_id']), 302, [])->with('提示', '规格设置成功');
    }


    protected function getAttributeHtml()
    {
        $html = '<div id="ac" data-storeid="' . Admin::user()->store_id . '">';
        $html .= '<table class="table table-bordered" id="goods_spec_table1">';
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    protected function getAttrScript(){
        return <<<EOT

//$(document).ready(function(){
    var category_id = $('.buildsku').data('categoryid');
    var store_id = $('#ac').data('storeid');
//    console.log(category_id);

    //获取属性名
    var url = '/api/backend/attribute/' + store_id + '?q='+category_id;
    $.ajax({
        method:'get',
        url:url,
        success:function(ret){
            $('#goods_spec_table1').html('');
            //属性值
              var  html=""
            for(var index in ret){
                //console.log(ret[index]['text']);
                html = '<tr>';
                html +=    '<td class="aaa">'+ret[index]['text']+'</td>';
                html +=     '<td id=attr_key_'+ret[index]['id']+'></td>';
                html += '</tr>';
                get_attribute_value(ret[index]['id']);
                $('#goods_spec_table1').append(html);
            }
        }

    });

    function get_attribute_value(key_id){
        //console.log(key_id);

        var url = '/api/backend/attribute_values?q='+key_id;

        $.ajax({
            method:'get',
            url : url,
            success: function(ret){
                 var  html=""
                for(var index in ret){
                        html += '<div class="col-sm-8" id="tt"><label></label><input type="checkbox" name="specvalues[]" value="'+ret[index]['id']+'" class=specvalues_'+ret[index]['id']+' >'+ret[index]['text']+'</div>';
                }
                console.log(html);
                console.log($('#attr_key_'+key_id))
                $('#attr_key_'+key_id).html(html);
            }
        });
    }

//});



EOT;
    }
}
