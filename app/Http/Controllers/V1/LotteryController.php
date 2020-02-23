<?php

namespace App\Http\Controllers\V1;

use App\Logic\LotteryLogic;
use App\Logic\LotteryOrderLogic;
use App\Models\Address;
use App\Models\Goods;
use App\Models\Lottery;
use App\Models\LotteryChance;
use App\Models\LotteryFollow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LotteryController extends Controller
{
    //抽奖
    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['index']]);
    }

    public function index(Request $request)
    {
        $param = $request->all();

        $list = Lottery::with(['goods' => function ($query) {
            $query->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width');
        }])->where(function ($query) use ($param) {
//            $query->whereIn('status', [1, 4]);
            $query->where('status', 1);
            $query->whereHas('goods',function($query){
                $query->where('type',0);
                $query->where('prom_type', 3);
            });

        })
            ->orderBy('weigh', 'desc')
            ->offset($param['offset'] ?? 0)
            ->limit($param['limit'] ?? 10)
            ->select('id', 'title', 'description', 'price', 'needer', 'goods_id', 'join_num', 'status')
            ->get();

        return $this->success($list);
    }

    /**
     * 立即抽奖
     */
    public function addLotteryFollow(Request $request)
    {
        $goods_id = $request->input('goods_id');
        $spec_key = $request->input('spec_key');
        $address_id = $request->input('address_id');
        $address = Address::find($address_id);
        $goods = Goods::find($goods_id);
        if ($goods->prom_type != 3) return $this->error('该商品抽奖活动不存在或者已下架');
        $lotteryLogic = new LotteryLogic($goods);
        $lottery = $lotteryLogic->getPromModel();
        $goods = $lotteryLogic->getGoodsInfo();

        $lotteryOrderLogic = new LotteryOrderLogic();
        $lotteryOrderLogic->setLottery($lottery);
        $lotteryOrderLogic->setUserId(auth('api')->user()->id);
        $lotteryOrderLogic->setGoods($goods);
        $lotteryOrderLogic->setSpecKey($spec_key);
        $lotteryOrderLogic->setAddress($address);
        $res = $lotteryOrderLogic->add();
        if (!$res) {
            return $this->error($lotteryOrderLogic->getError());
        } else {
            return $this->success($res);
        }
    }

    /**
     * 中奖名单
     */
    public function winer(Request $request)
    {
        $param = $request->all();

        $list = LotteryFollow::with([
            'lottery' => function ($query) {
                $query->select('id', 'title', 'description','goods_id');
            },
            'user' => function($query){
                $query->select('id','name');
            }
        ])->where(function ($query) use ($param) {
            $query->where('status', 2);
        })
            ->offset($param['offset'] ?? 0)
            ->limit($param['limit'] ?? 10)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($list);

    }

    /**
     * 我的抽奖
     */
    public function myLottery(Request $request)
    {
        $param = $request->all();
        $lotteryId = DB::table('lottery_follow')->where(function($query){
            $query->where('user_id',auth('api')->user()->id);
        })->distinct()->pluck('id');

        $list = LotteryFollow::whereIn('id',$lotteryId)->with([
            'lottery' => function ($query) {
                $query->select('id', 'title', 'description','goods_id','price');
            },
            'user' => function($query){
                $query->select('id','name');
            }
        ])->where(function ($query) use ($param) {
        })
            ->offset($param['offset'] ?? 0)
            ->limit($param['limit'] ?? 10)
            ->orderBy('id', 'desc')
            ->get();

        foreach($list as $k => $v){
            $list[$k]['lottery_num'] = DB::table('lottery_chance')->where([
                'user_id' => auth('api')->user()->id,
                'lottery_id' => $v->lottery_id,
            ])->value('lottery_num');

            $list[$k]['lottery_chance'] = LotteryChance::with(['user' => function($query){
                $query->select('id','name','avatar');
            }])->where([
                'lottery_id' => $v->lottery_id,
            ])->get();
        }

        return $this->success($list);
    }

    /**
     * 增加抽奖次数
     */
    public function incLotteryChance(Request $request)
    {
        $lottery_id = $request->input('lottery_id');
        $lottery_change = DB::table('lottery_chance')->where([
            'lottery_id' => $lottery_id,
            'user_id' => auth('api')->user()->id,
        ])->first();
        if (!$lottery_change) return $this->error('请先参与抽奖');
        $lottery_limit_num = get_config_by_name('lottery_limit_num');
        if ($lottery_change->lottery_num < $lottery_limit_num) {
            DB::table('lottery_chance')->where([
                'lottery_id' => $lottery_id,
                'user_id' => auth('api')->user()->id,
            ])->increment('lottery_num');
            return $this->success();
        } else {
            return $this->error('已超过可增加抽奖次数' . $lottery_limit_num . '次');
        }
    }


}
