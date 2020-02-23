<?php

namespace App\Tenancy\Controllers;

use App\Exports\OrderExporter;
use App\Http\Controllers\Controller;
use App\Models\CardType;
use App\Models\Category;
use App\Models\Goods;
use App\Models\Store;
use App\Models\StoreGoodsCategory;
use App\Models\TeamActivity;

use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class ApiController extends Controller
{

    //商家的商品
    public function storeGoods(Request $request)
    {
        return DB::table('goods')->where(function ($query) {
            $query->where('store_id', Admin::user()->store_id)
                ->where('status', 1)
                ->where('is_on_sale', 1);
        })->get(['id', DB::raw('name as text')]);
    }

    //活动添加商品
    public function goods(Request $request)
    {
        return DB::table('goods')->where(function ($query) {
            $query->where('store_id', Admin::user()->store_id)
                ->where('status', 1)
                ->where('prom_type', 0)
                ->where('prom_id', 0)
                ->where('type',0)
                ->where('is_on_sale', 1);
        })->get(['id', DB::raw('name as text')]);
    }

    public function editGoods(Request $request)
    {
        $param = $request->all();

        $list = DB::table('goods')->where(function ($query) {
            $query->where('store_id', Admin::user()->store_id);
            $query->where('status', 1);
            $query->where('is_on_sale', 1);
        })->get(['id', DB::raw('name as text')]);

        return $list;

    }

    public function cardType()
    {
        return CardType::where('store_id', Admin::user()->store_id)
            ->get(['id', DB::raw('name as text')]);
    }

    public function getTopStoreGoodsCategory(Request $request)
    {

        $list = StoreGoodsCategory::where('pid', 0)->where('store_id', Admin::user()->store_id)->get(['id', DB::raw('name as text')]);
        $list->prepend(['id' => 0, 'text' => '顶级分类']);
        $list->all();
        return $list;
    }

    public function team()
    {
        return TeamActivity::where('store_id', Admin::user()->store_id)->get(['id', DB::raw('title as text')]);
    }


    public function cat1()
    {
        $catIds = Store::where('id', Admin::user()->store_id)->value('cat_ids');
        return Category::whereIn('id', $catIds)->get(['id', DB::raw('name as text')]);
    }


    public function cat2(Request $request)
    {
        $cat2 = $request->get('q');

        return Category::where('pid', $cat2)->get(['id', DB::raw('name as text')]);
    }


    public function exportOrder(Request $request)
    {

        return Excel::download(new OrderExporter(), 'order_list.xlsx');
    }


}
