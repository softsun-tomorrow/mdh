<?php

namespace App\Http\Controllers\Web;

use App\Models\Goods;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class GoodsController extends Controller
{
    //
    public function spu(Request $request)
    {
        $goods_id = $request->get('id');
        $goods = Goods::find($goods_id);
        $goods->spu;
        $data = $goods->goods_spu;
//        dd($data->toArray());
        return view('goods_spu',['data' => $data]);

    }

    public function detail(Request $request)
    {
        $goods_id = $request->get('id');
        $goods = Goods::find($goods_id);
        $buy_notice =  DB::table('article')->where('title','购买需知')->latest()->value('content');
        $team_notice =  DB::table('article')->where('title','拼团需知')->latest()->value('content');

//        dd($goods->goods_spu->toArray());
//        dd( $goods->content);
        return view('goods_detail',['detail' => $goods->content, 'goods_spu' => $goods->goods_spu, 'buy_notice' => $buy_notice, 'notice' => $goods->notice, 'team_notice' => $team_notice]);
    }

    public function share()
    {
        return view('app_share');
    }
}
