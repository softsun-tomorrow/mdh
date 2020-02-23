<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Goods;
use App\Models\Lottery;
use App\Models\PromotionCategory;
use App\Models\StoreClass;
use App\Models\TeamActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class ApiController extends Controller
{
    /**
     * 顶级分类
     */
    public function topCategory(){
        return  Category::where('pid',0)->get(['id',DB::raw('name as text')]);
    }

    public function goods(){
        $collection = Goods::get(['id',DB::raw('name as text')]);

        $filtered = $collection->only(['id', 'text']);
        $goods = $filtered->all();
        dd($goods);
        return $goods->all();
    }

    public function user(){
        return User::get(['id',DB::raw('mobile as text')]);
    }

    public function promotion_category()
    {
        return PromotionCategory::get(['id',DB::raw('name as text')]);

    }

    public function store_class(){
        return StoreClass::get(['id',DB::raw('name as text')]);
    }

    public function team()
    {
        return TeamActivity::get(['id', DB::raw('title as text')]);
    }

    public function lottery()
    {
        return Lottery::get(['id', DB::raw('title as text')]);
    }
}
