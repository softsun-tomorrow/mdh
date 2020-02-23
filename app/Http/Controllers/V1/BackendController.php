<?php

namespace App\Http\Controllers\V1;

use App\Logic\GoodsLogic;
use App\Models\Area;
use App\Models\Category;
use App\Models\Goods;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreClass;
use App\Models\StoreGoodsCategory;
use App\Models\Tyfon;
use App\Models\TyfonCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackendController extends Controller
{
    //后台api接口


    /**
     * 商品顶级分类
     */
    public function topCategory(){
        return  Category::where('pid',0)->get(['id',DB::raw('name as text')]);

    }

    /**
     * 商品获取子分类
     */
    public function getChildCategory(Request $request){
        $q = $request->input('q');
        return Category::where('pid',$q)->get(['id',DB::raw('name as text')]);
    }

    /**
     * 商品获取子分类
     */
    public function getChildStoreGoodsCategory(Request $request){
        $q = $request->input('q');
        return StoreGoodsCategory::where('pid',$q)->get(['id',DB::raw('name as text')]);
    }

    public function getTopStoreGoodsCategory(){
        $list =  StoreGoodsCategory::where('pid',0)->get(['id',DB::raw('name as text')]);
        $list->prepend(['id' => 0, 'text' => '顶级分类']);
        $list->all();
        return $list;
    }

    /**
     * 商品顶级分类
     */
    public function topTyfonCategory(){
        return  TyfonCategory::where('pid',0)->get(['id',DB::raw('name as text')]);
    }

    /**
     * 商品获取子分类
     */
    public function getTyfonChildCategory(Request $request){
        $q = $request->input('q');
        return TyfonCategory::where('pid',$q)->get(['id',DB::raw('name as text')]);
    }

    /**
     * 顶级地区
     */
    public function topArea(){
        return  Area::where('parent_id',0)->get(['id',DB::raw('name as text')]);
    }

    /**
     * 子地区
     */
    public function getChildArea(Request $request){
        $q = $request->input('q');
        return Area::where('parent_id',$q)->get(['id',DB::raw('name as text')]);
    }




    public function attribute(Request $request)
    {
        $store_id = $request->route('storeid', 0);
        $categoryId = $request->get('q', 0);
        return DB::table('spec_key')->where(function ($query) use ($store_id, $categoryId) {
            if ($store_id) {
                $query->where('store_id', $store_id);
            }
            if ($categoryId) {
                $query->where('cat2', $categoryId);
            }
        })->get(['id', DB::raw('spec_name as text')]);
    }

    public function attribute_values(Request $request)
    {
        $attribute_id = $request->get('q', 0);
        $list = DB::table('spec_value')->where(function ($query) use ($attribute_id) {
            if ($attribute_id) {
                $query->where('spec_key_id', $attribute_id);

            }
        })->select('id', 'spec_value as text')->get();

        return response()->json($list);
    }

    public function getUser(Request $request){
        $userId = $request->input('q');
//        dd($userId);
        $list = User::where('id',$userId)->get(['id',DB::raw('name as text')]);
        return response()->json($list);
    }

    public function getTyfon(Request $request){
        $q = $request->input('q');

        $list = Tyfon::where('id',$q)->get(['id',DB::raw('title as text')]);
        return response()->json($list);
    }


    public function getGoods(Request $request){
        $q = $request->input('q');
        $list = Goods::get(['id',DB::raw('name as text')]);
        return response()->json($list);
    }

    /**
     * 确认订单
     */
    public function confirmOrder(Request $request){
        $id = $request->input('id');
        $res = DB::table('order')->where('id',$id)->update([
            'order_status' => 1,
        ]);
        if($res !== false){
            return $this->success('确认订单成功');
        }else{
            return $this->error('确认订单失败');
        }
    }

    public function orderProcessHandle($order_id,$act,$store_id = 0){
        $updata = array();
        switch ($act){
            case 'pay': //付款
                $order_sn = M('order')->where("order_id", $order_id)->getField("order_sn");
                update_pay_status($order_sn); // 调用确认收货按钮
                return true;
            case 'pay_cancel': //取消付款
                $updata['pay_status'] = 0;
                break;
            case 'confirm': //确认订单
                $updata['order_status'] = 1;
                break;
            case 'cancel': //取消确认
                $updata['order_status'] = 0;
                break;
            case 'invalid': //作废订单
                $updata['order_status'] = 5;
                break;
            case 'remove': //移除订单
                $this->delOrder($order_id,$store_id);
                break;
            case 'delivery_confirm'://确认收货
                confirm_order($order_id); // 调用确认收货按钮
                return true;
            default:
                return true;
        }
        return M('order')->where(['order_id'=>$order_id,'store_id'=>$store_id])->save($updata);//改变订单状态
    }

    /**
     * 获取店铺快递
     */
    public function getStoreShipper(Request $request){
        $storeId = $request->input('store_id');
        $list = DB::table('store_shipper')->where('store_id',$storeId)->get();
        return $this->success($list);
    }

    /**
     * 确认发货
     */
    public function confirmSend(Request $request){
        $param = $request->all();
        $storeShipper = DB::table('store_shipper')->where('id',$param['shipper_id'])->first();
        $res = DB::table('order')->where('id',$param['id'])->update([
            'shipping_status' => 1,
            'shipping_time' => date('Y-m-d H:i:s'),
            'logistic_code' => $param['logistic_code'],
            'shipper_name' => $storeShipper->shipper_name,
            'shipper_code' => $storeShipper->shipper_code
        ]);
        if($res !== false){
            return $this->success('发货成功');
        }else{
            return $this->error('发货失败');
        }
    }

    /**
     * 确认送货
     */
    public function confirmGiveOrder(Request $request){
        $id = $request->input('id');
        $res = DB::table('order')->where('id',$id)->update([
            'shipping_status' => 1,
            'shipping_time' => date('Y-m-d H:i:s'),
        ]);
        if($res !== false){
            return $this->success('确认送货成功');
        }else{
            return $this->error('确认送货失败');
        }
    }

    /**
     * 生成取货码
     */
    public function confirmCode(Request $request){
        $id = $request->input('id');
        $res = DB::table('order')->where('id',$id)->update([
            'shipping_status' => 1,
            'shipping_time' => date('Y-m-d H:i:s'),
            'pick_code' => nonceStr()
        ]);
        if($res !== false){
            $order = DB::table('order')->where('id',$id)->first();
            DB::table('ticket')->insert([
                'code' => $order->pick_code,
                'user_id' => $order->user_id,
                'store_id' => $order->store_id,
                'order_id' => $order->id,
                'order_sn' => $order->master_order_sn ? $order->master_order_sn : $order->order_sn,
            ]);
            return $this->success('生成取货码成功');
        }else{
            return $this->error('生成取货码失败');
        }
    }

    /**
     * 用户已收货
     */
    public function confirmGet(Request $request){
        $id = $request->input('id');
        $res = DB::table('order')->where('id',$id)->update([
            'order_status' => 2,
            'confirm_time' => date('Y-m-d H:i:s')
        ]);
        if($res !== false){
            DB::table('ticket')->where('order_id',$id)->update([
                'status' => 1
            ]);
            return $this->success('生成取货码成功');
        }else{
            return $this->error('生成取货码失败');
        }
    }

    public function storeClass(){
        return DB::table('store_class')->get(['id',DB::raw('name as text')]);
    }


    public function getOrder(Request $request)
    {
        $store_id = $request->input('store_id',0);

        //两个where限制开始结束时间
        $data = Order::where('created_at', '>', Carbon::parse('-1 month'))
            ->groupBy('date')
            ->where(function($query) use ($store_id){
                if($store_id){
                    $query->where('store_id',$store_id);
                }
            })
            ->get([DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as value')]);

        $dateArr = [];
        $valueArr = [];
        foreach ($data as $k => $v) {
            $dateArr[$k] = $v['date'];
            $valueArr[$k] = $v['value'];
        }
        $assign = ['data' => $dateArr, 'value' => $valueArr];
        return $this->success($assign);

    }

    public function spu(Request $request)
    {
        $cat2 = $request->input('cat2');
        return DB::table('spu')->where('cat2',$cat2)->get();

    }

    public function getGoodsSpecList(Request $request)
    {
        $specvalues = $request->input('specvalues');

        if($specvalues){
            $goodsLogic = new GoodsLogic();
            $specIds = $goodsLogic->getCartesianKey($specvalues);

            $ret = $goodsLogic->getSkuList($specIds);
            return $this->success($ret);
        }else{
            return $this->error('请选择规格');
        }

    }


}
