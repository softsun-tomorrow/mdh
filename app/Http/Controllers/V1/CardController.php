<?php

namespace App\Http\Controllers\V1;

use App\Models\Card;
use App\Models\CardAccountLog;
use App\Models\CardScoreLog;
use App\Models\CardType;
use App\Models\Sms;
use App\Models\Store;
use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CardController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['cardType']]);
    }

    public function cardType(Request $request)
    {
        $store_id = $request->input('store_id');
        $store = Store::find($store_id);

        $list = CardType::where('store_id', $store_id)->get();
        return $this->success([
            'shop_name' => $store->shop_name,
            'card_notice' => $store->card_notice,
            'card_type_list' => $list
        ]);
    }

    /**
     * 申请开卡
     */
    public function addCardOrder(Request $request)
    {
        $card_type_id = $request->input('card_type_id');
        $mobile = $request->input('mobile');
        $pay_type = $request->input('pay_type');

        $order_sn = build_order_sn('card');
        $cardType = CardType::find($card_type_id);

        //用户只能开一张在线会员卡
        $cardCount = Card::where(function ($query) use ($cardType) {
            $query->where('type', 1)
                ->where('user_id', auth('api')->user()->id)
                ->where('store_id', $cardType->store_id);
        })->count();

        if ($cardCount) return $this->error('您已经办理过本店的会员卡，请勿重复办卡');

        $res = DB::table('card_order')->insert([
            'order_sn' => $order_sn,
            'user_id' => auth('api')->user()->id,
            'card_type_id' => $card_type_id,
            'mobile' => $mobile,
            'created_at' => date('Y-m-d H:i:s'),
            'pay_account' => $cardType->pay_account,
            'pay_type' => $pay_type,
            'store_id' => $cardType->store_id
        ]);

        if ($res) {
            return $this->success(['order_sn' => $order_sn]);
        } else {
            return $this->error('下单失败');
        }
    }

    /**
     * 我的会员卡
     * @param int $scene类型 : 1 = 正常卡， 0 = 失效卡
     */
    public function myCard(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $scene = $request->input('scene', 1);

        $list = Card::with([
            'store' => function ($query) {
                $query->select('id', 'shop_name', 'logo');
            },
            'card_type' => function ($query) {
                $query->select('id', 'name', 'color');
            }
        ])->where(function ($query) use ($scene) {
            $query->where('type', 1)->where('user_id', auth('api')->user()->id);

            if ($scene) {
                //正常卡
                $query->where('end_time', '>', date('Y-m-d H:i:s'));
            } else {
                $query->where('end_time', '<', date('Y-m-d H:i:s'));
            }
        })
            ->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->select('id', 'card_type_id', 'store_id')
            ->get();
        return $this->success($list);
    }

    /**
     * 我的会员卡详情
     * @param Request $request
     */
    public function detail(Request $request)
    {
        $id = $request->input('id');
        $info = Card::find($id);
        $data = [];
        $info->store;
        $info->card_type;
        $data['store']['shop_name'] = $info['store']['shop_name'];
        $data['card_type']['name'] = $info['card_type']['name'];
        $data['card_no'] = $info['card_no'];
        $data['account'] = $info['account'];
        $data['score'] = $info['score'];
        $data['end_time'] = Carbon::parse($info['end_time'])->toDateString();
        $data['expireScore'] = $info->expire_score;
        return $this->success($data);
    }

    /**
     * 会员卡绑定
     */
    public function bangding(Request $request)
    {
        $card_no = $request->input('card_no');
        $code = $request->input('code');
        $mobile = auth('api')->user()->mobile;

        //校验手机验证码
        $card = Card::where('card_no', $card_no)->first();
        if (!$card) return $this->error('此会员卡不存在');

        //验证短信验证码
        $sms = new Sms();
        $check = $sms->check($mobile, $code);
        //短信验证暂时屏蔽
        //if(!$check) return $this->error($sms->getError());


        $card = Card::find($card->id);
        //用户已有的在线的会员卡
        $has = Card::where(['type' => 1, 'store_id' => $card->store_id, 'user_id' => auth('api')->user()->id])->first();

        if ($has) {
            //合并
            //将离线信用卡合并至在线信用卡, 合并后卡级别为两张卡的最高级别
            $card_type_id = $card->card_type->account > $has->card_type->account ? $card->card_type_id : $has->card_type_id;
            $account = $card->account + $has->account;
            $card->end_time > $has->end_time ? $end_time = $card->end_time : $end_time = $has->end_time;
            $card->rate_end_time > $has->rate_end_time ? $rate_end_time = $card->rate_end_time : $rate_end_time = $has->rate_end_time;
            $card->rate > $has->rate ? $rate = $card->rate : $rate = $has->rate;


            $has->card_type_id = $card_type_id;
            $has->account = $account;
            $has->end_time = $end_time;
            $has->rate_end_time = $rate_end_time;
            $has->rate = $rate;
            $result = $has->save();

            $card->delete();
        } else {
            //修改为在线会员卡
            $has->type = 1;
            $has->user_id = auth('api')->user()->id;
            $has->user_num = auth('api')->user()->num;
            $result = $has->save();
        }

        if ($result !== false) {
            return $this->success();
        } else {
            return $this->error('绑定失败');
        }
    }

    /**
     * 会员卡充值
     */
    public function addCardAccount(Request $request)
    {
        $card_id = $request->input('card_id');
        $account = $request->input('account');
        $pay_type = $request->input('pay_type');

        $card = Card::find($card_id);
        if (!$card) return $this->error('会员卡不存在');
        $order_sn = build_order_sn('recharge');
        $res = DB::table('card_recharge')->insert([
            'card_id' => $card_id,
            'account' => $account,
            'user_id' => auth('api')->user()->id,
            'order_sn' => $order_sn,
            'created_at' => date('Y-m-d H:i:s'),
            'pay_type' => $pay_type,
            'store_id' => $card->store_id
        ]);
        if ($res) {
            return $this->success(['order_sn' => $order_sn]);
        } else {
            return $this->error('下单失败');
        }
    }

    /**
     * 储值明细
     */
    public function cardAccountLog(Request $request)
    {
        $card_id = $request->input('card_id');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $list = CardAccountLog::where(function ($query) use ($card_id) {
            $query->where('user_id', auth('api')->user()->id)
                ->where('card_id', $card_id);
        })
            ->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($list);
    }

    /**
     * 积分明细
     */
    public function cardScoreLog(Request $request)
    {
        //类型:0=收入,1=支出
        $card_id = $request->input('card_id');
        $type = $request->input('type');
        $start = $request->input('start');
        $end = $request->input('end');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $list = CardScoreLog::where(function ($query) use ($card_id, $type, $start, $end) {
            $query->where('user_id', auth('api')->user()->id)
                ->where('card_id', $card_id);
            if (isset($type)) $query->where('type', $type);
            if (isset($start)) $query->whereDate('change_time', '>=', $start);
            if (isset($end)) $query->whereDate('change_time', '<=', $end);
        })
            ->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($list);
    }
}
