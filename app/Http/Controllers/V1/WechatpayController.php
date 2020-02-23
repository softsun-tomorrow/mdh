<?php

namespace App\Http\Controllers\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;

class WechatpayController extends Controller
{
    //
    protected $config = [];

    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['notify']]);

        $this->config = [
        'appid' => env('WECHAT_APPID'), // APP APPID
        'app_id' => '', // 公众号 APPID
        'miniapp_id' => '', // 小程序 APPID
        'mch_id' => env('WECHAT_MCH_ID'),//商户号
        'key' => env('WECHAT_KEY'),
        'notify_url' => env('APP_URL') . '/api/wechatpay/notify',
        'cert_client' => './cert/apiclient_cert.pem', // optional，退款等情况时用到
        'cert_key' => './cert/apiclient_key.pem',// optional，退款等情况时用到
        'log' => [ // optional
            'file' => './logs/wechat.log',
            'level' => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http' => [ // optional
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
            // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        ],
        'mode' => 'normal', // optional, dev/hk;当为 `hk` 时，为香港 gateway。
    ];


    }


    public function index(Request $request)
    {
        $order_sn = $request->input('order_sn');
        $total_amount = $request->input('total_amount');
        $order = Order::where('order_sn',$order_sn)->first();

        $order = [
            'out_trade_no' => $order_sn,
            'body' => '麦达汇订单：'.$order_sn,
//            'total_fee' => $order->order_amount*100,
//            'total_fee' => $total_amount*100,
            'total_fee' => 1, //测试改为0.01
        ];

//        Log::debug('wechat order: ',$order);

        $pay =  Pay::wechat($this->config)->app($order);
        if($pay->getStatusCode() == 200){
            Log::debug('wechat prepay: '.$pay->getContent());
            return $this->success(json_decode($pay->getContent()));
        }else{
            return $this->error('微信支付接口错误');
        }
        // $pay->appId
        // $pay->timeStamp
        // $pay->nonceStr
        // $pay->package
        // $pay->signType
    }

    public function notify()
    {
        $pay = Pay::wechat($this->config);

        try{
            $data = $pay->verify(); // 是的，验签就这么简单！
            update_pay_status($data->out_trade_no);
            Log::debug('Wechat notify', $data->all());

        } catch (\Exception $e) {
            Log::debug('Wechat notify error: '. $e->getMessage());
            // $e->getMessage();
        }

        return $pay->success();// laravel 框架中请直接 `return $pay->success()`
    }


}
