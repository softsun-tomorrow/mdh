<?php

namespace App\Http\Controllers\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use App\Http\Controllers\Controller;


class AlipayController extends Controller
{
    protected $config = [];
    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['transfer','notify', 'return','web']]);
        $this->config = [
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
        ];
    }



    public function index(Request $request)
    {
        $order_sn = $request->input('order_sn');
        $order = Order::where('order_sn',$order_sn)->first();
        $total_amount = $request->input('total_amount');

        if(empty($order_sn) || empty((float)$total_amount)) return $this->error('参数不正确');
        $total_amount = 0.01; //测试改为1分钱
        $order = [
            'out_trade_no' => $order_sn,
            'total_amount' => $total_amount,
            'subject' => '麦达汇订单：'.$order_sn . '，金额：' . $total_amount,
        ];

        $alipay = Pay::alipay($this->config)->app($order);

        if($alipay->getStatusCode() == 200){
            return $this->success($alipay->getContent());

        }else{

            return $this->error('支付宝接口错误');
        }
    }

    public function web()
    {
        $order = [
            'out_trade_no' => time(),
            'total_amount' => '0.01',
            'subject' => 'test subject - 测试',
        ];
        $alipay = Pay::alipay($this->config)->web($order);
        return $alipay;
    }

    public function notify()
    {
        $alipay = Pay::alipay($this->config);

        try{
            $data = $alipay->verify(); // 是的，验签就这么简单！

            // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
            // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
            // 4、验证app_id是否为该商户本身。
            // 5、其它业务逻辑情况
            update_pay_status($data->out_trade_no);
            Log::debug('Alipay notify', $data->all());
        } catch (\Exception $e) {
            Log::debug('Alipay notify error: '. $e->getMessage());

        }
        return $alipay->success();
    }

    public function find(Request $request){
        $order_sn = $request->input('order_sn');

        $pay_status = DB::table('order')->where('order_sn',$order_sn)->value('pay_status');
        if($pay_status == 1){
            return $this->success();
        }else{
            return $this->error();
        }

    }

    //单笔转账
    public function transfer()
    {
        $order = [
            'out_biz_no' => time(),
            'payee_type' => 'ALIPAY_LOGONID',
            'payee_account' => '807017289@qq.com',
            'amount' => '0.1',
        ];
        $alipay = Pay::alipay($this->config);
        $result = $alipay->transfer($order);
        return $this->success($result);
    }


}
