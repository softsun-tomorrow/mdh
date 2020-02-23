<?php

namespace App\Http\Controllers\V1;

use App\Logic\CouponLogic;
use App\Models\AccountLog;
use App\Models\Address;
use App\Models\Banner;
use App\Models\Card;
use App\Models\Exchange;
use App\Models\Feedback;
use App\Models\FlashSale;
use App\Models\Goods;
use App\Models\Lottery;
use App\Models\Nav;
use App\Models\Store;
use App\Models\User;
use App\Models\Version;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class IndexController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['appDownload','banner','invite','version','sideGoods', 'index', 'nav', 'exchangeCoin', 'exchangeScore']]);
    }

    /**
     * 投诉建议
     * @return \Illuminate\Http\JsonResponse
     */
    public function addFeedback(Request $request){
        $param = $request->all();
        $param['user_id'] = auth('api')->user()->id;
        $res = Feedback::create($param);

        if($res){
            return $this->success();
        }else{
            return $this->error('提交失败');
        }

    }

    /**
     * 平台麦穗兑换列表
     */
    public function exchangeCoin(Request $request)
    {
        $param = $request->all();
        $list = Exchange::where(function ($query) use ($param) {
            $query->where('store_id', 0);
            if (isset($param['category_id'])) $query->where('category_id', $param['category_id']);
            if (isset($param['keywords'])) $query->where('name', 'like', '%' . $param['keywords'] . '%');

        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($list);
    }

    /**
     * 店铺积分兑换列表
     */
    public function exchangeScore(Request $request)
    {
        $param = $request->all();
        $list = Exchange::where(function ($query) use ($param) {
            $query->where('store_id', $param['store_id']);
            if ($param['category_id']??0 ) $query->where('category_id', $param['category_id']);
            if ($param['keywords']??'') $query->where('name', 'like', '%' . $param['keywords'] . '%');

        })
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($list);
    }

    /**
     * 提交兑换订单列表
     */
    public function exchangeOrder(Request $request)
    {
        $param = $request->all();
        $exchange = Exchange::withTrashed()->find($param['id']);
        $address = Address::find($param['address_id']);
        if (!$address) return $this->error('收货地址不存在');

        //验证用户余额
        if ($param['type']) {
            //店铺积分
            $store_id = $param['store_id'];
            $card = Card::getUserOnlineCard(auth('api')->user()->id, $store_id);
            if ($card->score < $exchange->money) return $this->error('积分不足');

        } else {
            //麦穗
            $store_id = 0;
            if (auth('api')->user()->account < $exchange->money) return $this->error('麦穗不足');
        }

        $res = DB::table('exchange_order')->insert([
            'exchange_id' => $param['id'],
            'store_id' => $store_id,
            'user_id' => auth('api')->user()->id,
            'money' => $exchange->money,
            'created_at' => date('Y-m-d H:i:s'),
            'consignee' => $address->name,
            'mobile' => $address->mobile,
            'area' => $address->area,
            'address' => $address->detail,
        ]);
        if ($res) {

            //扣除用户麦穗或积分
            if ($param['type']) {
                //积分
                Card::cardScoreLog($card->id, '-' . $exchange->money, 1, 3, '', '积分兑换礼品');
            } else {
                //麦穗
                User::accountLog(auth('api')->user()->id, '-' . $exchange->money, '', '麦穗兑换礼品', 1, 6);
            }

            return $this->success();
        } else {
            return $this->error('兑换失败');
        }
    }

    /**
     * 积分兑换列表
     */
    public function exchangeIndex()
    {
        $expireCoin = AccountLog::getExpireAccount(auth('api')->user()->id);
        $cardsList = Card::with(['store' => function ($query) {
            $query->select('id', 'shop_name');
        }])->where(function ($query) {
            $query->where('user_id', auth('api')->user()->id);
            $query->whereDate('end_time', '>=', Carbon::now());
        })
            ->select('id', 'user_id', 'store_id', 'score', 'end_time')
            ->get();


        foreach ($cardsList as $k => $v) {
            $cardsList[$k]['expireScore'] = Card::getExpireScore(auth('api')->user()->id, $v->id);
        }


        return $this->success([
            'coin' => ['coin' => auth('api')->user()->account, 'expire_coin' => $expireCoin],
            'card_list' => $cardsList
        ]);
    }

    /**
     * 导航
     */
    public function nav()
    {
        $list = Nav::orderBy('weigh', 'desc')->get();

        return $this->success($list);
    }

    /**
     * 首页
     */
    public function index(Request $request)
    {
        $param = $request->all();
        $banner = Banner::where('page_type', 0)
            ->where(function ($query) use ($param) {

            })
            ->orderBy('weigh', 'desc')
            ->get();
        $nowHour = Carbon::now()->hour;
        if($nowHour%2 == 0){
            $scene = $nowHour;
        }else{
            $scene= $nowHour-1;
        }

        $nav = Nav::orderBy('weigh', 'desc')->limit(8)->get();
        $flashSaleList = FlashSale::with(['goods' => function($query){
            $query->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width');
        }])->where(function($query) use ($param,$scene){
//            $query->whereIn('status',[1,4]);
            $query->where('status',1);
            $query->where('is_recommend',1);
            $query->where('scene',$scene);
        })
            ->orderBy('weigh','desc')
            ->select('id','title','description','price','buy_num','goods_id','scene','status')
            ->get();

        $lotteryList = Lottery::with(['goods' => function ($query) {
            $query->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width');
        }])->where(function ($query) use ($param) {
//            $query->whereIn('status', [1, 4]);
            $query->where('status', 1);
            $query->where('is_recommend',1);
        })
            ->orderBy('weigh', 'desc')
            ->select('id', 'title', 'description', 'price', 'needer', 'goods_id', 'join_num', 'status')
            ->get();

        $data = [
            'banner' => $banner,
            'nav' => $nav,
            'flash_sale' => $flashSaleList,
            'lottery' => $lotteryList,
            'middule_img' => Banner::where('page_type',7)->orderBy('id','desc')->first(),
            'buttom_img' => get_config_by_name('index_buttom_img'),
        ];

        return $this->success($data);
    }



    /**
     * app版本
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function version(Request $request){
        $scene = $request->input('scene');
        $info = Version::where('scene',$scene)->orderBy('id','desc')->first();
        return $this->success($info);
    }

    public function appDownload()
    {
        $versinArr = Version::pluck('url','scene');
        return $this->success(['android' => $versinArr[0],'ios' => $versinArr[1]]);

    }

    /**
     * 邀请赚钱页面
     */
    public function invite(Request $request){
        $user_id = $request->input('user_id');
        $user = DB::table('users')->where('id',$user_id)->first();
        $article = DB::table('article')->where('title','邀请攻略')->first();
        $versinArr = Version::pluck('url','scene');
        return $this->success([
            'version' => ['android' => $versinArr[0],'ios' => $versinArr[1]],
            'referral_code' => $user->referral_code,
            'article' => $article,

        ]);
    }

    /**
     * 轮播图
     */
    public function banner(Request $request){
        $page_type = $request->input('page_type',0);
//        $scene = $request->input('scene',0);

        $list = Banner::where(function ($query) use ($page_type){
            $query->where('page_type',$page_type);
        })
            ->orderBy('weigh','desc')
            ->get();

        return $this->success($list);
    }




}
