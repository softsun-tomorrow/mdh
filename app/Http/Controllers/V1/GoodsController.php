<?php

namespace App\Http\Controllers\V1;

use App\Logic\CommentLogic;
use App\Logic\CouponLogic;
use App\Logic\FlashSaleOrderLogic;
use App\Logic\GoodsLogic;
use App\Logic\OrderLogic;
use App\Logic\StoreLogic;
use App\Logic\UserLogic;
use App\Models\Address;
use App\Models\Category;
use App\Models\Collect;
use App\Models\Comment;
use App\Models\FlashSale;
use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\HotSearch;
use App\Models\Lottery;
use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class GoodsController extends Controller
{
    //
    protected $guard = 'api';

    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['delTestGoods','friend','storeRecGoods','agentGoods','coinGoods','promotionCategory','promotion','search','getGoodsComment', 'categoryTree', 'topCategory', 'index', 'detail', 'getSpecPrice','recGoods']]);
    }

    public function categoryTree()
    {
        $category = new Category();
        $tree = $category->toTree();
        return $this->success($tree);
    }

    /**
     * 顶级分类
     */
    public function topCategory()
    {
        $list = Category::where('pid', 0)->get()->prepend(['id' => 0, 'pid' => 0, 'name' => '全部', 'weigh' => 9999])->all();

        return $this->success($list);
    }

    /**
     * 商品列表
     */
    public function index(Request $request)
    {
        DB::connection()->enableQueryLog();
        $param = $request->all();
        //sort： 0=综合排序，1=销量优先，2=价格升序，3=价格降序
        switch ($param['sort']??0) {
            case 1:
                $sort = 'sale_nums';
                $order = 'desc';
                break;
            case 2:
                $sort = 'shop_price';
                $order = 'asc';
                break;
            case 3:
                $sort = 'shop_price';
                $order = 'desc';
                break;
            default:
                $sort = 'weigh';
                $order = 'desc';
        }
        $list = Goods::with(['store' => function ($query) use ($param) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($param) {
            $query->where(['is_on_sale' => 1, 'status' => 1]);
            $query->where('type',0);
            $query->whereIn('prom_type', [0, 1, 2]);

            if ($param['cat1']??0) $query->where('cat1', $param['cat1']);
            if ($param['cat2']??0) $query->where('cat2', $param['cat2']);
            if ($param['keywords']??'') {
                $query->where('name', 'like', '%' . $param['keywords'] . '%');

            }
        })->when($param['sort']??0, function ($query) use ($param, $sort, $order) {
            if($order == 'desc'){
                return $query->orderBy($sort, $order);
            }else{
                return $query->orderBy((string)$sort);
            }

        }, function ($query) {
            //综合排序
            return $query->orderBy('weigh', 'desc');
        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->get();


//        dd( DB::getQueryLog());
        return $this->success($list);
    }

    /**
     * 商品搜索
     */
    public function search(Request $request)
    {

        $param = $request->all();
        $list = Goods::with(['store' => function ($query) use ($param) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($param) {
            $query->where(['is_on_sale' => 1, 'status' => 1]);
            if ($param['keywords']??'') $query->where('name', 'like', '%' . $param['keywords'] . '%');
            //商家id
            if($param['store_id']??0) $query->where('store_id',$param['store_id']);
        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->get();
        return $this->success($list);
    }

    /**
     * 商品详情
     */
    public function detail(Request $request)
    {
        $id = $request->input('id');
        try{
            $info = Goods::findOrFail($id);
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return $this->error($e->getMessage());
        }

        if(!$info) return $this->error('该商品不存在');
        //点击量
        $info->click_nums += 1;
        $info->save();

        $isCollect = 0;
        $userLogic = new UserLogic();

        if (auth('api')->check()) {
            $userLogic->setUserId(auth('api')->user()->id);
            //记录浏览历史
            $key = 'user:goods:' . auth('api')->user()->id;
            Redis::lpush($key, $id);
//            $list = Redis::lrange($key,0,-1);
            if (Redis::llen($key) > 50) {
                Redis::rpop($key);
            }

            //是否收藏
            $isCollect = $userLogic->isCollect($id, 'goods');
        }
        $info['isCollect'] = $isCollect;
        $info['tags_text'] = $info->tags_text;
        $filtered = $info->store->only(['id', 'shop_name', 'logo', 'notice', 'qq', 'wechat', 'customer_service']);
        unset($info->store);
        $info['store'] = $filtered;
        //相册
        $info->goods_images;

        //评价
        $commentLogic = new CommentLogic();
        $commentList = $commentLogic->getCommentByGoodsId($id);
        $info['commentList'] = $commentList;

        //活动
        $info['prom_info'] = $info->prom_info;
        return $this->success($info);
    }

    /**
     * 获取属性价格
     *
     */
    public function getSpecPrice(Request $request)
    {
        $goods_id = $request->input('goods_id');
        $key = $request->input('key');
        $key = strKey2json($key);
        $goods = Goods::find($goods_id);
        if($goods->prom_type == 2){
            //拼团
            $goods_price = DB::table('goods_spec')->where('goods_id', $goods_id)->where('spec_keys', $key)->value('team_price');
        }elseif($goods->prom_type == 3){
            //抽奖
            $goods_price = Lottery::find($goods->prom_id)->price;
        }elseif($goods->prom_type == 1){
            //秒杀
            $flashSale = FlashSale::find($goods->prom_id);
            $flashSaleOrderLogic = new FlashSaleOrderLogic();
            $flashSaleOrderLogic->setFlashSale($flashSale);
            $flashSaleOrderLogic->setUserId(auth('api')->user()->id);
            $flashSaleOrderLogic->setGoods($goods);
            $res = $flashSaleOrderLogic->check();
            if(!$res){
                //普通价格
                $goods_price = DB::table('goods_spec')->where('goods_id', $goods_id)->where('spec_keys', $key)->value('goods_price');

            }else{
                //秒杀价
                $goods_price = $flashSale->price;
            }

        }else{
            $goods_price = DB::table('goods_spec')->where('goods_id', $goods_id)->where('spec_keys', $key)->value('goods_price');
        }

        return $this->success($goods_price);
    }


    /**
     * 获取商品评价
     */
    public function getGoodsComment(Request $request)
    {
        $param = $request->all();
        $list = Comment::with(['user' => function ($query) {
            $query->select('id', 'name', 'avatar');
        }])->where(function ($query) use ($param) {
            $query->where('goods_id', $param['goods_id']);
            $query->where('is_show', 1);
        })
            ->orderBy('created_at', 'desc')
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->get();

        return $this->success($list);
    }

    /**
     * 浏览历史
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $param = $request->all();
        $key = 'user:goods:' . auth('api')->user()->id;
        $redislist = Redis::lrange($key, 0, -1);

        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($redislist) {
            $query->whereIn('id',$redislist);
        })
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->get();
        return $this->success($list);
    }



    /**
     * 本店精选推荐
     */
    public function storeRecGoods(Request $request){
        $store_id = $request->input('store_id');

        $storeLogic = new StoreLogic();
        $storeLogic->setStoreId($store_id);
        $list = $storeLogic->getStoreRecGoods();
        return $this->success($list);
    }



    /**
     * 活动版块分类
     */
    public function promotionCategory(){
        $list = DB::table('promotion_category')->get();
        return $this->success($list);
    }

    /**
     * 活动版块--商品列表
     */
    public function promotion(Request $request){
        $param = $request->all();
        $goodsIds = Promotion::where(function($query) use ($param){
            if($param['promotion_category_id']??0) $query->where('promotion_category_id',$param['promotion_category_id']);
        })->pluck('goods_id')->toArray();

        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($param,$goodsIds) {
            $query->whereIn('id',$goodsIds);
            $query->where(['is_on_sale' => 1, 'status' => 1]);
        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->get();

        return $this->success($list);
    }

    /**
     * 抵扣专区--商品列表
     */
    public function coinGoods(Request $request){
        $param = $request->all();

        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($param) {
            $query->where('type',2);
            $query->where(['is_on_sale' => 1, 'status' => 1]);
        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->get();

        return $this->success($list);
    }

    /**
     * 代理升级--商品列表
     */
    public function agentGoods(Request $request){
        $param = $request->all();

        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($param) {
            $query->where('type',1);
            $query->where(['is_on_sale' => 1, 'status' => 1]);
        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->get();
        $data = [];
        $data['level'] = 0;
        if(auth('api')->check()){
            $data['level'] = auth('api')->user()->level;
        }
        $data['list'] = $list;
        return $this->success($data);
    }

    /**
     * 代理升级--好友福利
     */
    public function friend(Request $request){
        $param = $request->all();

        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($param) {
            $query->where('type',1);
            $query->where(['is_on_sale' => 1, 'status' => 1]);
        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->orderBy('id','desc')
            ->get();
        $data = [];
        $data['level'] = 0;
        if(auth('api')->check()){
            $data['level'] = auth('api')->user()->level;
        }
        $data['list'] = $list;
        return $this->success($data);
    }

    /**
     * 推荐商品
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recGoods(Request $request){
        $param = $request->all();
        $orderLogic = new GoodsLogic();
        $list = $orderLogic->getRecGoods($param['keywords']??'',$param['offset']??0,$param['limit']??10, $param['cat1']??0);
        return $this->success($list);
    }

    /**
     * 热门搜索关键词
     */
    public function hotSearch()
    {
        $list = HotSearch::orderBy('weigh','desc')->limit(20)->get();
        return $this->success($list);
    }

    /**
     * 分享回调
     */
    public function shareCallback(Request $request)
    {
        $goodsId = $request->input('goods_id');

        DB::table('goods_share')->insert([
            'user_id' => auth('api')->user()->id,
            'goods_id' => $goodsId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $this->success();
    }

    public function agentImage()
    {
        //upgrade_first_img
        return $this->success([
            'upgrade_first_img' => get_config_by_name('upgrade_first_img'),
            'upgrade_second_img' => get_config_by_name('upgrade_second_img'),
        ]);
    }

    //测试删除商品
    public function delTestGoods(Request $request)
    {
        $id = $request->input('id');
        Goods::where('id', $id)->delete();
        return $this->success();
    }
    
    //好友福利图片
    public function friendImage()
    {
        return $this->success(['image' => get_config_by_name('upgrade_first_img')]);
    }





}
