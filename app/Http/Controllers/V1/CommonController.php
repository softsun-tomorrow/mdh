<?php

namespace App\Http\Controllers\V1;

use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Core\DefaultAcsClient;

use App\Libraries\KdApiEOrder;
use App\Logic\OrderLogic;
use App\Logic\StoreLogic;
use App\Logic\TeamOrderLogic;
use App\Models\Area;
use App\Models\CardOrder;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\Lottery;
use App\Models\Order;
use App\Models\TeamActivity;
use App\Models\TeamFound;
use App\Models\User;
use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CommonController extends Controller
{
    //
    public function test()
    {
        $userLogic = new \App\Logic\UserLogic();
        $storeLogic = new StoreLogic();
        $orderLogic = new OrderLogic();

//        $specIds = [[1,3],[9,10]];
//        GoodsSpec::CartesianProduct($specIds);
//        $str = '80:11:19:DC:9D:0E:80:12:1C:4D:48:C4:3E:09:35:5F';
//        echo  strtolower(str_replace(':','',$str));

//        $order = Order::find(86);
//        $orderLogic = new OrderLogic();
//        $orderLogic->give_integral($order);
//        Redis::get('foo','bar1');
////        $str = Redis::lindex('foo',0);
//        dd($str);
//        $date = '2019-03-28 18:08:10';
//        $shipping_time = new Carbon($date);
////        dd($shipping_time);
//        $now = Carbon::now();
////        dd($now);
//        $difference = $shipping_time->diffInDays($now) ;
//        dd($difference);

//        $coin2rmb = get_config_by_name('coin2rmb');
////        echo $coin2rmb;exit;
////        return $this->success(Area::getSelectOptions());
//        $teamOrderLogic = new TeamOrderLogic();
//        $teamOrderLogic->refund(TeamFound::find(11));
//        $order = \App\Models\Order::where(['order_sn' => '2019052950975748'])->first();
//        dd($order->order_goods->toArray());
//        $list = get_upgrade_config();
//        dd($list);

        //测试升级
//        $order = order::find(169);
//        $userLogic = new \App\Logic\UserLogic();
//        $userLogic->setUserId($order->user_id);
//        $userLogic->setUser(\App\Models\User::find($order->user_id));
//        $userLogic->setOrder($order);
//        $userLogic->upgrade();
        //

        //测试待结算收入
//        $userLogic->autoSettle();
//        Log::info('test');

        //测试商家结算KdApiEOrder
//        $storeLogic->setStoreId(21);
//        $storeLogic->autoTransfer();

        //测试确认收货
//        $orderLogic->autoConfirmOrder();

        //测试取消订单
//        $orderLogic->autoCancelOrder();

//        $curl = new Curl();
//        $curl->setHeader('Content-Type', 'application/json');
//        $curl->post('http://maidahui.ddky520.cn/api/test', array(
//            'username' => 'myusername',
//            'password' => 'mypassword',
//        ));
//        if ($curl->error) {
//            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
//        } else {
//            echo 'Response:' . "\n";
//            var_dump($curl->response);
//        }


//        Goods::where('id',28)->restore();


//        try{
//            $res = $userLogic->test();
//            echo $res;
//        }catch (\Exception $e){
////            echo $e->getMessage();
//            return $this->error($e->getMessage());
//        }

        /*$kdn = new KdApiEOrder();
        //构造电子面单提交信息
        $eorder = [];
        $eorder["ShipperCode"] = "SF";
        $eorder["OrderCode"] = "012657700387";
        $eorder["PayType"] = 1;
        $eorder["ExpType"] = 1;

        $sender = [];
        $sender["Name"] = "李先生";
        $sender["Mobile"] = "18888888888";
        $sender["ProvinceName"] = "李先生";
        $sender["CityName"] = "深圳市";
        $sender["ExpAreaName"] = "福田区";
        $sender["Address"] = "赛格广场5401AB";

        $receiver = [];
        $receiver["Name"] = "李先生";
        $receiver["Mobile"] = "18888888888";
        $receiver["ProvinceName"] = "李先生";
        $receiver["CityName"] = "深圳市";
        $receiver["ExpAreaName"] = "福田区";
        $receiver["Address"] = "赛格广场5401AB";

        $commodityOne = [];
        $commodityOne["GoodsName"] = "其他";
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;


//调用电子面单
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);

//$jsonParam = JSON($eorder);//兼容php5.2（含）以下

        echo "电子面单接口提交内容：<br/>".$jsonParam;

        $res = $kdn->submitEOrder($jsonParam);
        dd($res);*/
//        $type = 1; $keywords = ''; $offset = 0; $limit = 10; $store_cat2 = 0;
//        $list = Goods::with(['store' => function ($query) {
//            $query->select('id', 'shop_name', 'logo');
//        }])->where(function ($query) use ($type, $keywords,$store_cat2) {
//            $query->where(['is_on_sale' => 1, 'status' => 1]);
//            $query->where('type',0);
//            $query->whereIn('prom_type', [0, 1, 2]);
////            $query->where('store_id', $this->store_id);
//            $query->where('store_id', 21);
//            if ($keywords) $query->where('name', 'like', '%' . $keywords . '%');
//            if($store_cat2) $query->where('store_cat2', $store_cat2);
//
//            switch ($type) {
//                case 1:
//                    $query->where('is_store_rec', 1);
//                    break;
//                case 2:
//                    $query->where('prom_type', 0);
//                    break;
//                case 3:
//                    $query->whereIn('prom_type', [1, 2]);
//                    break;
//                case 4:
//                    $oneMonth = date('Y-m-d H:i:s', strtotime('-1 week'));
//                    $query->whereDate('created_at', '>', $oneMonth);
//                    break;
//                default:
//
//            }
//
//        })
//            ->select('id', 'store_id', 'name', 'type', 'cover', 'share_rebate', 'self_rebate', 'shop_price', 'sale_nums', 'collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id', 'cover_width', 'cover_height', 'shipper_fee', 'exchange_integral', 'cover_height', 'cover_width')
//            ->offset($offset)
//            ->limit($limit)
//            ->get();
//
//        return response()->json(['code' => 0, 'msg' => '', 'data' => json_decode(json_encode($list),true)]);


//        Goods::where('store_id',20)->delete();
//        FlashSale::where('store_id', 20)->delete();
//        Lottery::where('store_id', 20)->delete();
//        TeamActivity::where('store_id', 20)->delete();
//        Order::where('store_id',20)->delete();

//        foreach (User::all() as $user){
//            $key = 'user:goods:' . $user->id;
//            Redis::del($key);
//        }

//        $list = Goods::where('store_id', 21)->get();
//        dd($list);

        //33,34,35,39,40
        //Goods::where('id',69)->restore();
//        $d = Goods::where('id',39)->first()->toArray();
//        dd($d);
//        Goods::where('id', '=', 69)->delete();


        //拼团价
//        $specKey = '100,103';
//        if($specKey){
//            $teamPrice = DB::table('goods_spec')->where(function($query) use ($specKey){
//                $key = strKey2json($specKey);
//                $query->where('goods_id', 79);
//                $query->where('spec_keys', $key);
//            })->value('team_price');
//        }else{
//            $teamPrice = $this->team['price'];
//        }
//        echo  $teamPrice;

//        $url = "http://thirdwx.qlogo.cn/mmopen/vi_32/DYAIOgq83epL09anejpkSUV9yDrydPtCt4GxvaibcOmePW0ymqI7I93AY4yiaG0LGZfd20j7icEAmZ2yZtwpCttxw/132";
//        $path = './uploads/images/test1111111.png';
//        $res = getImage($url,$path);
//        echo '<img src="/uploads/images/test1111111.png"/>';

    }


    //base64 上传图片
    public function base64Upload()
    {

        $imgdata = request()->all();

        if (stripos($imgdata['imgurl'], 'data:image/') !== false) {
            $imgdata = $imgdata['imgurl'];
        } else {
            $imgdata = 'data:image/jpeg;base64,' . $imgdata['imgurl'];
        }

        $base64_str = substr($imgdata, strpos($imgdata, ",") + 1);
        $image = base64_decode($base64_str);
        $imgname = 'images/' . rand(1000, 10000) . uniqid() . '.png';
        \Illuminate\Support\Facades\Storage::disk('admin')->put($imgname, $image);
        //return $imgname;//获取到图片名称后存储到数据库中，供以后使用。
        return $this->success($imgname);
    }


    //验证码接口
    public function sms(Request $request)
    {
        $phone = $request->input('phone');
        $ip = $request->ip();
        $count_ip = DB::table('sms')->where('created_at', '>', strtotime('-1 day'))
            ->where('ip', $ip)->count();
        if ($count_ip > 15) {
            return $this->error('短信发送次数太多');
        }
        DB::table('sms')->where('created_at', '<', strtotime('-1 day'))->delete();
        $code = rand(1000, 9999);
        $ret = $this->getMyCode($phone, $code);
        if (isset($ret->Code) && $ret->Code == 'OK') {
            DB::table('sms')->insert([
                'code' => $code, 'phone' => $phone,
                'ip' => $ip, 'created_at' => time()]);
        } else {
            return $this->error($ret->Message);

        }
        return $this->success('', '验证码已发送,请查收!');
    }


    //获取阿里云短信
    protected function getMyCode($phone, $code)
    {
        \Aliyun\Core\Config::load();
        $profile = \Aliyun\Core\Profile\DefaultProfile::getProfile("cn-hangzhou", config('app.sms_key'), config('app.sms_secret'));
        \Aliyun\Core\Profile\DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", 'Dysmsapi', 'dysmsapi.aliyuncs.com');
        $acsClient = new DefaultAcsClient($profile);
        $request_ = new SendSmsRequest();
        $request_->setPhoneNumbers($phone);
        $request_->setSignName(config('app.sms_name'));
        $request_->setTemplateCode(config('app.sms_tmpCode'));
        $request_->setTemplateParam("{\"code\":\"$code\"}");
        $ret = $acsClient->getAcsResponse($request_);
        return $ret;
    }

    public function allArea()
    {

        if (!$cache = Cache::get('allArea')) {
            $list = Area::where('parent_id', 1)->select('id', 'parent_id', 'code', 'name')->get();

            foreach ($list as $k => $v) {
                $city = Area::where('parent_id', $v->id)->select('id', 'parent_id', 'code', 'name')->get();
                foreach ($city as $ko => $vo) {
                    $city[$ko]['districtlist'] = Area::where('parent_id', $vo->id)->select('id', 'parent_id', 'code', 'name')->get();
                }
                $list[$k]['citylist'] = $city;
            }
            Cache::set('allArea', $list);
            $cache = $list;
        }

        return $this->success($cache);
    }

    public function testpay(Request $request)
    {
        $order_sn = $request->input('order_sn');
//        $order = DB::table('card_order')->where('order_sn',$order_sn)->first();
//        dd($order);
        update_pay_status($order_sn);
        return $this->success();

    }

    /**
     * 获取配置
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfigByName(Request $request)
    {
        $name = $request->input('name');
        $value = get_config_by_name($name);
        return $this->success($value);
    }


}
