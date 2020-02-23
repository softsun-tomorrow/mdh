<?php

namespace App\Http\Controllers\V1;

use App\Models\Banner;
use App\Models\Card;
use App\Models\Config;
use App\Models\Leesign;
use App\Models\UserCoupon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeesignController extends Controller
{
    //
    protected $guard = 'api';

    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['index']]);
    }

    //签到所得积分,连签奖励,连签周期
    //处理逻辑：如果上次签到的日期和这次签到的日期相差不是1天，那么本次签到就不是连续签到。
    //连续签到奖励规则 - 周期奖励
    //当周连续签到所获得的所有额外奖励
    //当周是否触发连续签到的额外奖励
    public function add(Request $request)
    {
        $type = $request->input('type',0);
        $store_id = $request->input('store_id', 0);


        $last = DB::table('leesign')->where('user_id', auth('api')->user()->id)
            ->where('type', $type)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            //是否连续签到
            $created = new Carbon($last->created_at);
            $created->startOfDay($created);
            $today = Carbon::today();
//            echo $created . ' -- ' . $today;exit;
            $difference = $created->diffInDays($today);
//            echo $difference;exit;
            if (!$difference) return $this->error('今日已经签到了，明天再来吧！');
            if ($difference > 1) {
                //非连续签到
                Leesign::firstSign(auth('api')->user()->id, $type, $store_id);

            } else {
                //连续签到
                Leesign::continueSign(auth('api')->user()->id, $type, $store_id);
            }

        } else {
            //非连续签到
            Leesign::firstSign(auth('api')->user()->id, $type, $store_id);
        }
        return $this->success();
    }


    /**
     * 签到天数列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        //处理逻辑：如果上次签到的日期和这次签到的日期相差不是1天，那么本次签到就不是连续签到。
        $type = $request->input('type');
        $store_id = $request->input('store_id', 0);
        $today = Carbon::today();
        //连签的第一天

        //除今天的最后一次签到
        $last = DB::table('leesign')->where('user_id', auth('api')->user()->id)
            ->where('type', $type)
            ->whereDate('created_at','<',$today)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            //今天之前是否签过到
//            echo $last->created_at;exit;
            $created = new Carbon($last->created_at);
            $created->startOfDay($created);
//            echo $created . ' -- ' . $today;exit;
            $difference = $created->diffInDays($today);
//            echo $difference;exit;
            //是否连续签到
            if ($difference > 0) {
                //连续签到 （显示以连续签到的起始日期为起点的7天）
                $list = Leesign::getWeek(auth('api')->user()->id, $type, 1, $store_id);
            } else {
                //非连续签到（显示从今天开始一周的日期）
                $list = Leesign::getWeek(auth('api')->user()->id, $type, 0, $store_id);
            }
        } else {
            //非连续签到（（显示从今天开始一周的日期））
            $list = Leesign::getWeek(auth('api')->user()->id, $type, 0, $store_id);
        }

        //昨天的签到
        $yd = Carbon::yesterday()->toDateString();
        $yestodayDate = DB::table('leesign')
            ->where('user_id', auth('api')->user()->id)
            ->whereDate('created_at', $yd)
            ->where('type', $type)
            ->first();

        $yestodayMaxSign = $yestodayDate? $yestodayDate->max_sign : 0;

        $last = DB::table('leesign')->where('user_id', auth('api')->user()->id)
            ->where('type', $type)
            ->orderBy('id', 'desc')
            ->first();
        if($last){
            $created = new Carbon($last->created_at);
            $created->startOfDay($created);
            $today = Carbon::today();
//            echo $created . ' -- ' . $today;exit;
            $difference = $created->diffInDays($today);
//            echo $difference;exit;
            if (!$difference) {
                $yestodayMaxSign+=1;
            };
        }


        return $this->success([
            'max_sign' => $yestodayMaxSign,
            'days' => $list,
            'account' => auth('api')->user()->account,
            'image' => Banner::where('page_type',6)->orderBy('id','desc')->first(),
        ]);

    }




}
