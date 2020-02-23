<?php

namespace App\Http\Controllers\V1;

use App\Logic\GoodsLogic;
use App\Logic\StoreLogic;
use App\Logic\UserLogic;
use App\Models\Collect;
use App\Models\Goods;
use App\Models\Promotion;
use App\Models\Tyfon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CollectController extends Controller
{
    //收藏

    public function __construct()
    {
        $this->middleware('refresh', ['except' => []]);
    }

    public function add(Request $request)
    {
        $commentable_type = $request->input('commentable_type');
        $commentable_id = $request->input('commentable_id');

        $first = Collect::where('user_id', auth('api')->user()->id)->where('commentable_type', $commentable_type)->where('commentable_id', $commentable_id)->first();
        if ($first) {
            //取消关注
            $res = $first->delete();
            Collect::doAfterChange(0, $commentable_type, $commentable_id);

        } else {
            //添加关注
            $res = Collect::create([
                'user_id' => auth('api')->user()->id,
                'commentable_id' => $commentable_id,
                'commentable_type' => $commentable_type,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            Collect::doAfterChange(1, $commentable_type, $commentable_id);

        }

        if ($res) {
            return $this->success();
        } else {
            return $this->error('关注失败');
        }
    }


    /**
     * 商品收藏列表
     * @param Request $request
     */
    public function goods(Request $request)
    {
        $param = $request->all();

        $goodsIds = Collect::where(function ($query) {
            $query->where('user_id', auth('api')->user()->id);
            $query->where('commentable_type', 'goods');
        })->orderBy('id','desc')->pluck('commentable_id');

        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($goodsIds) {
            $query->whereIn('id',$goodsIds);
        })
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->get();

        return $this->success($list);
    }

    public function store(Request $request)
    {
        $param = $request->all();
        $list = Collect::with(['store' => function($query){
            $query->select('id','shop_name','logo','collect_nums','send_type');
        }])
            ->where(function ($query) {
                $query->where('user_id', auth('api')->user()->id);
                $query->where('commentable_type', 'store');
            })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->get();

        $storeLogic = new StoreLogic();

        foreach ($list as $k => $v) {
            $storeLogic->setStoreId($v->store->id);
            $list[$k]['store']['goods'] = $storeLogic->getStoreRecGoods(1);
        }
        return $this->success($list);
    }


    /**
     * 心情收藏列表
     */
    public function tyfon(Request $request)
    {
        $param = $request->all();
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $tyfonIds = DB::table('collect')->where('collect.commentable_type', 'tyfon')
            ->where('collect.user_id', auth('api')->user()->id)->pluck('commentable_id');

        $list = Tyfon::where(function ($query) use ($tyfonIds) {
            $query->whereIn('id', $tyfonIds);
        })->offset($offset??0)
            ->limit($limit??10)->get();

        foreach ($list as $k => &$v) {
            $v->commentable;
//        dd($v->commentable->toArray());
            $v['created_at_text'] = $v->created_at->diffForHumans();
            $author['name'] = $v->commentable->name;
            $author['avatar'] = $v->commentable->avatar;
            $author['id'] = $v->commentable->id;
            $v['author'] = $author;
            $v['goods_info'] = [];
            if ($v->goods) {
                $v['goods_info'] = [
                    'id' => $v->goods->id,
                    'name' => $v->goods->name,
                    'shop_price' => $v->goods->shop_price
                ];
            }
            unset($v['commentable']);
            unset($v['goods']);


            //是否关注
            $isCollect = 0;
            if (auth('api')->check()) {
                $userLogic = new UserLogic();
                $userLogic->setUserId(auth('api')->user()->id);
//                $isCollect = DB::table('collect')->where(function ($query) use ($v) {
//                    $query->where('user_id', auth('api')->user()->id);
//                    $query->where('commentable_id', $v->commentable_id);
//                    $query->where('commentable_type', $v->commentable_type);
//                })->count();

                $isLike = $userLogic->isLike($v->id,'tyfon');
                $isCollect = $userLogic->isCollect($v->id,'tyfon');
            }
            $v['isCollect'] = $isCollect;
            $v['isLike'] = $isLike;
        }

        return $this->success($list);
    }

    /**
     * 收藏删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function del(Request $request)
    {
        $id = $request->input('id');
        $res = Collect::where('id',$id)->delete();
        if($res){
            return $this->success();
        }else{
            return $this->error('删除失败');
        }
    }
}
