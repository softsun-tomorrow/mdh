<?php

namespace App\Http\Controllers\V1;

use App\Models\User;
use App\Models\Withdrawals;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class WithdrawalsController extends Controller
{
    //

    public function __construct()
    {
        $this->middleware('refresh', ['except' => []]);
    }

    /**
     * 提现页面
     */
    public function info()
    {
        $last = DB::table('withdrawals')->where('user_id',auth('api')->user()->id)->orderBy('id','desc')->first();
        return $this->success([
            'money' => auth('api')->user()->money,
            'lastTime' => $last ? $last->created_at : '无'
        ]);
    }

    /**
     * 申请提现
     */
    public function add(Request $request){

        $param = $request->all();
        if(auth('api')->user()->is_lock == 1) return $this->error('账号已被锁定，请联系客服');
        $min = get_config_by_name('withdrawals_min');
/*        $rules = [
            'bank_card' => ['required'],
            'money' => ['required','min:'.$min,'max:50000'],
        ];

        $message = [
            'bank_card.required' => '提现账号必填',
            'money.required' => '提现金额必填',
            'money.min' => '提现金额须大于'.$min,
            'money.max' => '提现金额必须小于50000',
        ];

        $param = $request->only('bank_card','money');

        $validator = \Validator::make($param,$rules,$message);

        // 验证格式
        if($validator->fails()) return $this->error($validator->errors()->first());*/
        if(empty($param['bank_card'])) return $this->error('提现账号必填');
        if($param['money']*100 < $min*100) return $this->error('提现金额须大于'. $min);
        if($param['money']*100 > 50000*100) return $this->error('提现金额须小于50000');
        $param['bank_name'] = '支付宝';
        $param['user_id'] = auth('api')->user()->id;

        //提现金额大于余额
        if($param['money'] > auth('api')->user()->money) return $this->error('提现金额必须小于余额');

        //每隔10天可提现一次
        //上次提现时间
        $last = DB::table('withdrawals')->where('user_id',auth('api')->user()->id)->orderBy('id','desc')->first();

        //未实名，不允许提现
        if(!auth('api')->user()->is_real){
            return $this->error('未实名，不允许提现');
        }

        if($last){

            $created = new Carbon($last->created_at);
            $created->startOfDay($created);
            $today = Carbon::today();
            $difference = $created->diffInDays($today);
            if($difference < 10){
                return $this->error('距离上次提现间隔不足10天，请稍候');
            }
        }
        $order_sn = build_order_sn('tx');
        $param['order_sn'] = $order_sn;
        $taxfee_rate = get_config_by_name('withdrawals_taxfee_rate');

        $taxfee = round($param['money']*$taxfee_rate/100,2);

        $param['taxfee'] = $taxfee; //手续费
        $param['transfer_money'] = ($param['money']*100 - $taxfee*100)/100;

        $res =  Withdrawals::create($param);
        if($res){
            $user = DB::table('users')->where('id',auth('api')->user()->id)->first();

            DB::table('users')->where('id',auth('api')->user()->id)->update([
                'frozen_money' => price_format(($user->frozen_money*100 + $param['money']*100)/100)
            ]);

            User::moneyLog($user->id, '-'.$param['money'],$order_sn,'用户申请提现',1,3);

            return $this->success();
        }else{
            return $this->error('申请失败');
        }
    }
    
    /**
     * 提现记录
     */
    public function withdrawalsLog(Request $request)
    {
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);
        $list = Withdrawals::where(function($query){
            $query->where('user_id',auth('api')->user()->id);
        })->offset($offset)->limit($limit)->orderBy('id','desc')->get();

        return $this->success($list);
    }
}
