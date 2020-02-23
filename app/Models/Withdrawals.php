<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yansongda\Pay\Pay;

class Withdrawals extends Model
{
    //
    protected $table = 'withdrawals';

    protected $fillable = ['transfer_money','user_id','money','bank_name','bank_card','realname','remark','order_sn','taxfee'];


    public static function getStatusArr(){
        //状态：-2删除作废-1审核失败0申请中1审核通过2付款成功3付款失败
        return [-1 => '审核失败',0 => '申请中',1=>'审核通过',2=>'付款成功',3=>'付款失败'];
    }

    public function getActualMoneyAttribute()
    {
        return ($this->money*100 - $this->taxfee*100)/100;
    }

    /**
     * 提现审核通过后转款, 提现接口请求成功后调用
     */
    public static function trans($withdrawals,$status){
        //-1审核失败0申请中1审核通过2付款成功3付款失败
        $withdrawals->status = $status;
        $withdrawals->pay_time = date('Y-m-d H:i:s');
        $withdrawals->save();
        $user = DB::table('users')->where('id',$withdrawals->user_id)->first();

        if($status == 1){
            //审核通过
            //支付宝转账
            $result = self::alipayTransfer($user->alipay, $withdrawals->actual_money);
            /**
             * {
            "code": "10000",
            "msg": "Success",
            "order_id": "20190705110070001506960004648857",
            "out_biz_no": "1562292472",
            "pay_date": "2019-07-05 10:07:52"
            }
             */

            if(isset($result['code']) && $result['code'] == '10000'){
                //转账成功
                DB::table('users')->where('id',$withdrawals->user_id)->update([
                    'frozen_money' => price_format(($user->frozen_money*100 - $withdrawals->money*100)/100)
                ]);
                //记录
                $user = DB::table('users')->where('id',$withdrawals->user_id)->first();

                //写入平台资金表
                \App\Models\ExpenseLog::add(1,3,$withdrawals->actual_money,$withdrawals->id,$withdrawals->order_sn,'用户申请提现');
            }else{
                //转账失败
                Log::info('转账失败，转账单号：' .$withdrawals->order_sn );
            }

        }elseif($status == -1){
            //审核失败,退回用户余额
            DB::table('users')->where('id',$withdrawals->user_id)->update([
                'frozen_money' => price_format(($user->frozen_money*100 - $withdrawals->money*100)/100)
            ]);
            User::moneyLog($user->id, $withdrawals['money'],$withdrawals->order_sn,'提现审核失败退回',0,4);
        }
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    /**
     * 支付宝转账
     * @param  $payee_account 支付宝账户
     * @param $amount 转账金额
     */
    private static function alipayTransfer($payee_account, $amount){
        $order = [
            'out_biz_no' => time(),
            'payee_type' => 'ALIPAY_LOGONID',
            'payee_account' => $payee_account,
            'amount' => $amount,
        ];
        $alipay = Pay::alipay([
            'app_id' => env('ALI_APPID'),
            'notify_url' => env('APP_URL').'/api/alipay/notify',
            'return_url' => env('APP_URL').'/api/alipay/return',
            'ali_public_key' => env('ALI_PUBLIC_KEY'),
            // 加密方式： **RSA2**
            'private_key' => env('ALI_PRIVATE_KEY'),
            'log' => [ // optional
                'file' => './logs/alipay.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
            'mode' => 'normal', // optional,设置此参数，将进入沙箱模式,normal生产环境
        ]);
        $result = $alipay->transfer($order);

        return $result;
    }

}
