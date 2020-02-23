<?php

namespace App\Http\Controllers\V1;

use App\Logic\CouponLogic;
use App\Logic\GoodsPromFactory;
use App\Logic\StoreLogic;
use App\Models\Card;
use App\Models\Collect;
use App\Models\Coupon;
use App\Models\Goods;
use App\Models\StoreClass;
use App\Models\StoreGoodsCategory;
use App\Models\Tyfon;
use App\Models\User;
use Validator;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class StoreController extends Controller
{
    protected $guard = 'api';

    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['getStoreLicense','storeGoodsCategory','promGoods','newGoods','info','storeClass', 'applyOrderPage', 'detail', 'goodslist', 'storeTyfon', 'index']]);
    }

    public function guard()
    {
        return Auth::guard($this->guard);
    }

    public function add(Request $request)
    {
        $count = DB::table('store')->where('user_id', auth('api')->user()->id)->count();

        if ($count) return $this->error('您已经提交过入驻申请，请勿重复提交');
        $rules = [
            'contacts_mobile' => ['required', 'unique:store'],
        ];

        $message = [
            'contacts_mobile.required' => '联系电话必填',
            'contacts_mobile.unique' => '联系电话手机号码已存在',
            'shop_name.unique' => '店铺名称已存在',
        ];

//        $param = $request->only('license_front', 'license_number', 'contacts_name', 'contacts_mobile', 'idcard_num', 'idcard_front', 'idcard_back');
        $param = $request->all();
        $validator = Validator::make($param, $rules, $message);
        // 验证格式
        if ($validator->fails()) return $this->error($validator->errors()->first());
        $param['created_at'] = date('Y-m-d H:i:s');
        $param['user_id'] = auth('api')->user()->id;
        $res = DB::table('store')->insert($param);
        if ($res) {
            return $this->success();
        } else {
            return $this->error('入驻申请失败');
        }
    }

    /**
     * 审核进度
     */
    public function progress()
    {
        $store = Store::where('user_id', auth('api')->user()->id)->first();

        return $this->success([
            'status' => $store->status,
            'status_text' => $store->status_text,
            'created_at' => $store['created_at'],
            'handle_time' => $store->handle_time,
            'pay_status' => $store->pay_status
        ]);
    }


    /**
     * 入驻付款页面
     */
    public function applyOrderPage()
    {
        $config = get_config();
        return $this->success([
            'store_apply_account' => $config['base.store_apply_account'],
            'apply_discount' => $config['base.apply_discount'],
        ]);
    }

    /**
     * 入驻付款
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyOrder(Request $request)
    {
        //验证审核状态
        $store = DB::table('store')->where('user_id', auth('api')->user()->id)->first();
        if(!$store) return $this->error('请先提交入驻审核');
        $store_id = $store->id;
        if ($store->status != 1) return $this->error('您提交的入驻申请正在审核中，请稍候');


        $community_code = $request->input('community_code', '');
        $pay_type = $request->input('pay_type');
        $config = get_config();
        $apply_account = $config['base.store_apply_account'];

        //验证小区长邀请码
        $community_user_id = 0;
        if ($community_code) {
            if (!DB::table('users')->where('community_code', $community_code)->count()) return $this->error('小区长邀请码不正确');
            $community_user_id = User::decodeCommunityCode($community_code); //小区长用户id
            $discount_account = $config['base.apply_discount']; //优惠金额
            $apply_account = price_format($apply_account - $discount_account);
        }

        $order_sn = build_order_sn('store');
        $res = DB::table('other_order')->insert([
            'user_id' => auth('api')->user()->id,
            'pay_type' => $pay_type,
            'order_sn' => $order_sn,
            'created_at' => date('Y_m-d H:i:s'),
            'commentable_type' => 'store',
            'commentable_id' => $store_id,
            'extra' => $community_user_id,
            'remark' => '商户入驻',
            'order_amount' => $apply_account,
            'store_id' => $store_id
        ]);
        if ($res) {
            return $this->success(['order_sn' => $order_sn, 'order_amount' => $apply_account, 'pay_type' => $pay_type]);
        } else {
            return $this->error('提交订单失败');
        }
    }





    /**
     * 店铺喇叭动态
     * @param Request $request
     */
    public function storeTyfon(Request $request)
    {
        $param = $request->all();

        $list = Tyfon::where(function ($query) use ($param) {
            $query->where('commentable_type', 'store');
            $query->where('commentable_id', $param['id']);
        })
            ->orderBy('id', 'desc')
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->get();
        return $this->success($list);
    }


    /**
     * 举报商家
     */
    public function expose(Request $request)
    {
        $param = $request->all();
        $param['user_id'] = auth('api')->user()->id;
        $param['created_at'] = date('Y-m-d H:i:s');
        $res = DB::table('expose')->insert($param);

        if ($res) {
            return $this->success();
        } else {
            return $this->error();
        }
    }

    /**
     * 积分兑换麦穗信息
     */
    public function socore2coin(Request $request)
    {
        $store_id = $request->input('store_id');
        $store = Store::find($store_id);

        $userCard = Card::getUserOnlineCard(auth('api')->user()->id, $store_id);
        return $this->success([
            'score2coin' => $store->score2coin,
            'user_score' => $userCard->score
        ]);

    }

    /**
     * 提交积分兑换麦穗
     */
    public function submitExchange(Request $request)
    {
        $store_id = $request->input('store_id');
        $score = $request->input('score');
        $store = Store::find($store_id);
        $userCard = Card::getUserOnlineCard(auth('api')->user()->id, $store_id);
        //验证积分余额
        if ($score - $userCard->score > 0) return $this->error('积分不足');
        $coin = floor($score / $store->score2coin);

        Card::cardScoreLog($userCard->id, '-' . $score, 1, 3, '', '积分兑换麦穗');
        User::accountLog(auth('api')->user()->id, $coin, '', '积分兑换麦穗', 0, 7);
        return $this->success();
    }

    /**
     * 获取我在此店会员卡id
     */
    public function getMyCardId(Request $request)
    {
        $store_id = $request->input('store_id');
        $card = Card::getUserOnlineCard(auth('api')->user()->id, $store_id);
        if (!$card) return $this->error('您在本店暂未开通会员卡');
        return $this->success($card->id);
    }



    /**
     * 扫码支付-直接付款
     */
    public function scan(Request $request)
    {
        $account = $request->input('account');
        $store_id = $request->input('store_id');
        $pay_type = $request->input('pay_type');
        if ($account * 100 <= 0) return $this->error('金额不正确');

        if ($pay_type == 0) {
            //会员卡
            //验证用户会员卡余额
            $card_account = DB::table('card')->where(['user_id' => auth('api')->user()->id, 'store_id' => $store_id])->value('account');
            if ($card_account * 100 - $account * 100 < 0) {
                return $this->error('会员卡余额不足！');
            }
        }

        $order_sn = build_order_sn('scan');
        $res = DB::table('scan_order')->insert([
            'user_id' => auth('api')->user()->id,
            'pay_type' => $pay_type,
            'order_sn' => $order_sn,
            'created_at' => date('Y-m-d H:i:s'),
            'account' => $account,
            'store_id' => $store_id
        ]);
        if ($res) {
            if ($pay_type == 0) {
                //会员卡支付
                update_pay_status($order_sn);
            }
            return $this->success(['order_sn' => $order_sn]);
        } else {
            return $this->error('下单失败');
        }

    }

    /**
     * 店铺类别
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeClass()
    {
        $list = StoreClass::get();
        return $this->success($list);
    }


    /**
     * 店铺列表
     */
    public function index(Request $request)
    {
//        DB::connection()->enableQueryLog();  // 开启QueryLog
        $param = $request->all();
        $list = Store::where(function ($query) use ($param) {
            $query->where('status', 1);
            $query->where('send_type',4); //只显示到店自提店铺
            if($param['store_class_id']??0) $query->where('store_class_id',$param['store_class_id']);
            if($param['keywords']??'') $query->where('shop_name','like','%'. $param['keywords'] .'%');

            $query->whereHas('goods',function($query){
                $query->where(['is_on_sale' => 1, 'status' => 1]);
                $query->where('is_store_rec', 1);
                $query->whereIn('prom_type', [1, 2]);
            });
        })
            ->orderBy('weigh', 'desc')
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id', 'shop_name', 'notice', 'logo', 'send_type','collect_nums')
            ->get();

        foreach ($list as $k => $v) {
            $goodsData = Goods::with('goods_images')->where(function ($query) use ($v) {
                $query->where(['is_on_sale' => 1, 'status' => 1]);
                $query->where('store_id', $v->id);
                $query->where('is_store_rec', 1);
                $query->whereIn('prom_type', [1, 2]);
            })
                ->orderBy('id', 'desc')
                ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
                ->first();

            if ($goodsData) {
                $goodsData['prom_info'] = $goodsData->prom_info;
            }
            $list[$k]['goods'] = $goodsData;
        }
//        dump(DB::getQueryLog());
        return $this->success($list);
    }

    /**
     * 商家信息
     */
    public function info(Request $request)
    {
        $id = $request->input('store_id');
        $store = Store::find($id);

        $info = $store->only(['shop_name','logo','collect_nums', 'license_front', 'address', 'customer_service']);
        //是否收藏
        $isCollect = 0;
        if (auth('api')->check()) {
            $user_id = auth('api')->user()->id;
            $isCollect = Collect::isCollect($user_id, $id, 'store');
        }
        $info['isCollect'] = $isCollect;

        return $this->success($info);
    }

    /**
     * 店铺首页
     * @param Request $request
     */
    public function detail(Request $request){
        $store_id = $request->input('store_id');
        $store = Store::find($store_id);
        $banner = $store->store_banner;
        $storeLogic = new StoreLogic();
        $storeLogic->setStoreId($store_id);
        $list = $storeLogic->getStoreRecGoods();
        return $this->success(['banner' => $banner, 'recGoods' => $list]);
    }


    /**
     * 店铺商品列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodslist(Request $request)
    {
        $store_id = $request->input('store_id');
        $type = $request->input('type',1);
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);
        $keywords = $request->input('keywords','');
        $store_cat2 = $request->input('store_cat2',0);

        $storeLogic = new StoreLogic();
        $storeLogic->setStoreId($store_id);
        $list = $storeLogic->getStoreRecGoods($type,$keywords,$offset,$limit,$store_cat2);
        return $this->success($list);

    }

    /**
     * 店内分类
     */
    public function storeGoodsCategory(Request $request){
        $store_id = $request->input('store_id');

        $category = new StoreGoodsCategory();
        $tree = collect($category->toTree())->filter(function($value,$key) use ($store_id){
            return $value['store_id'] == $store_id;
        });

        return $this->success(array_values($tree->toArray()));
    }


    /**
     * 获取某店铺的工商执照接口
     */
    public function getStoreLicense(Request $request)
    {
        $store_id = $request->input('store_id');
        $store = Store::find($store_id);
        return $this->success(['license' => $store->license_front]);
    }




}
