<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\Invite;
use App\Models\Order;
use App\Models\Sms;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{

    protected $guard = 'api';//设置使用guard为api选项验证，请查看config/auth.php的guards设置项，重要！

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['login', 'register', 'forget', 'test1', 'bindMobile', 'checkOpenidBind']]);
    }

    public function test()
    {

//        dd(auth('api')->user()->toArray());

//        \QrCode::generate('Make me into a QrCodeQrCode!');

        $data = file_get_contents('php://input');
        Log::info('curl数据：',$data);
    }

    public function test1()
    {
        echo 'test1';
    }


    /**
     * 微信openid是否绑定手机
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOpenidBind(Request $request)
    {
        $openid = $request->input('openid');
        $type = $request->input('type', 0); //0=微信，1=qq

        $user = User::where(function ($query) use ($type, $openid) {
            if ($type == 1) {
                $query->where('qq_openid', $openid);
            } else {
                $query->where('wx_openid', $openid);
            }
        })->first();
        if (!$user) {
            return $this->error('未绑定手机');
        }
        if (!$user['mobile']) {
            return $this->error('未绑定手机');
        }
        if ($token = auth('api')->login($user)) {
            $data = $this->respondWithToken($token);
            return $this->success(['token' => $data->original['access_token'], 'user' => auth('api')->user(), 'is_first_login' => 0]);
        } else {
            return $this->error('登录失败');
        }
    }

    public function bindMobile(Request $request)
    {
        $type = $request->input('type', 0); //0=微信
        $opendid = $request->input('openid');
        $nickname = $request->input('nickname');
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        $pcode = $request->input('pcode', '');
        $avatar = $request->input('avatar', '');
        $fileName = 'images/wechat'. uniqid() .'.png';
        $savePath = './uploads/'.$fileName;

//        $img = file_get_contents($avatar);
//        file_put_contents($savePath,$img);
        getImage($avatar,$savePath);

        //验证短信验证码
        $sms = new Sms();
        $check = $sms->check($mobile, $code);
        //短信验证暂时屏蔽
        if(!$check) return $this->error($sms->getError());

        $user = User::where('mobile', $mobile)->first();
        $isFirstLogin = 0;

        $pcode = isset($pcode) ? $pcode : '';
        //验证推荐码
        if (!empty($pcode)) {
            $first_leader = User::decodeReferralCode($pcode);
            if (!$first_leader) return $this->error('推荐码错误');
        } else {
            $first_leader = 0;
        }

        if (!$user) {
            //不存在用户
            $isFirstLogin = 1;
            if ($type) {
                $qq_openid = $opendid;
                $wx_openid = '';
            } else {
                $qq_openid = '';
                $wx_openid = $opendid;
            }

            // 创建用户
            $user = User::create([
                'mobile' => $mobile,
                'avatar' => $fileName,
                'name' => $nickname,
                'wx_openid' => $wx_openid,
                'qq_openid' => $qq_openid,
                'first_leader' => $first_leader,
            ]);

        } else {
            //已存在手机号码
            $type ? $user->qq_openid = $opendid : $user->wx_openid = $opendid;
            $user->name = $nickname;
            $user->avatar = $fileName;
            $user->save();
        }

        if ($token = auth('api')->login($user)) {
            $data = $this->respondWithToken($token);
            return $this->success(['token' => $data->original['access_token'], 'user' => auth('api')->user(), 'is_first_login' => $isFirstLogin]);
        }
        return $this->error('用户名或密码错误');
    }

    public function register(Request $request)
    {
        $rules = [
            'mobile' => ['required', 'unique:users', 'size:11'],
//            'password' => ['required', 'min:6', 'max:16'],
            'code' => ['required'],
        ];

//        $payload = $request->only('mobile', 'password', 'code', 'pcode');
        $payload = $request->only('mobile', 'code', 'pcode');
        $pcode = isset($payload['pcode']) ? $payload['pcode'] : '';

        $exists = User::where('mobile', $payload['mobile'])->count();
        if ($exists > 0) return $this->error('该手机号码已注册');
        //验证推荐码
        if (!empty($pcode)) {
            $first_leader = User::decodeReferralCode($pcode);
            if (!$first_leader) return $this->error('推荐码错误');
        } else {
            $first_leader = 0;
        }

        unset($payload['pcode']);
        $validator = \Validator::make($payload, $rules);

        // 验证格式
        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }
        //验证短信验证码
        $sms = new Sms();
        $check = $sms->check($payload['mobile'], $payload['code']);
        //短信验证暂时屏蔽
        if(!$check) return $this->error($sms->getError());

        // 创建用户
        $result = User::create([
            'mobile' => $payload['mobile'],
//            'password' => bcrypt($payload['password']),
            'avatar' => 'default.jpg',
            'name' => yc_phone($payload['mobile']),
            'first_leader' => $first_leader,
        ]);

        if ($result) {
            User::afterInsert($result);
            return $this->success();
        } else {
            return $this->error('创建用户失败');
        }

    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
//        $credentials = $request->only('mobile', 'password');
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        $user = User::where('mobile', $mobile)->first();

        if (!$user) return $this->error('用户不存在');
        //验证短信验证码
        $sms = new Sms();
        $check = $sms->check($mobile, $code);
        //短信验证暂时屏蔽
        if(!$check) return $this->error($sms->getError());


        if ($token = auth('api')->login($user)) {

            $data = $this->respondWithToken($token);
            return $this->success(['token' => $data->original['access_token'], 'user' => auth('api')->user()]);
        }

//        return $this->response->errorUnauthorized('登录失败');
        return $this->error('用户名或密码错误');
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
//        echo User::decodeReferralCode(auth('api')->user()->num);exit;
        return $this->success($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return $this->success();
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard($this->guard);
    }


    /**
     * 找回密码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forget(Request $request)
    {

        $rules = [
            'mobile' => ['required'],
            'password' => ['required', 'min:6', 'max:16'],
            'code' => ['required']
        ];

        $param = $request->only('mobile', 'code', 'password');
        $validator = Validator::make($param, $rules);

        // 验证格式
        if ($validator->fails()) {
            return $this->error($validator->errors());
        }

        $user = User::where('mobile', $param['mobile'])->first();
        if (!$user) return $this->error('该手机号码未注册');

        //验证短信验证码
        $sms = new Sms();
        $check = $sms->check($param['mobile'], $param['code']);
        if (!$check) return $this->error($sms->getError());

        $user->password = bcrypt($param['password']);
        $res = $user->save();
        if ($res !== false) {
            return $this->success();

        } else {
            return $this->error('找回密码失败');
        }

    }

    /**
     * 修改密码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changpwd(Request $request)
    {

        $rules = [
            'mobile' => ['required', 'size:11'],
            'password' => ['required', 'min:6', 'max:16'],
            'oldpwd' => ['required', 'min:6', 'max:16']
        ];

        $param = $request->only('mobile', 'oldpwd', 'password');
        $validator = Validator::make($param, $rules);

        // 验证格式
        if ($validator->fails()) {
            return $this->error($validator->errors()->first());

        }

        //验证原密码
        $user = auth('api')->user();

        if (!Hash::check($param['oldpwd'], $user['password'])) return $this->error('原密码不正确');

        $user->password = bcrypt($param['password']);
        $res = $user->save();

        if ($res !== false) {
            return $this->success();
        } else {
            return $this->error('修改密码失败');
        }
    }

    public function profile(Request $request)
    {
        $param = $request->all();
        $user = auth('api')->user();

        $res = DB::table('users')->where('id', auth('api')->user()->id)->update($param);
        if ($res !== false) {
            return $this->success();
        } else {
            return $this->error('修改失败');
        }

    }

    /**
     * 推广海报
     * @return \Illuminate\Http\JsonResponse
     */
    public function poster()
    {
        $user = auth('api')->user();
        $referral_img = 'uploads/' . $user->referral_img;
        $imagename = 'images/poster_' . $user->id . '.png';
        $savepath = './uploads/' . $imagename;
        $img = (string)\Image::make('uploads/poster_background.png')
            ->resize(750, 1200)
            ->insert($referral_img, 'bottom-right', 220, 400)
            ->encode('data-url')
            ->text($user->referral_code, 300, 800, function ($font) {
                $font->file('uploads/arial.ttf'); //gd库字体有坑，不能更改字体大小，需自定义字体
                $font->size(50); // 默认值：12
                $font->color('#000000');
            })->save($savepath);

        $code = $user->referral_code;
        return $this->success(['referral_code' => $code, 'img' => $imagename]);
    }

    /**
     * 实名
     */
    public function identify(Request $request)
    {
        $param = $request->all();

        $accountId = "1780843816875805";
        $customerID = auth('api')->user()->id;
        $identifyNum = $param['idcard_num'];
        $userName = $param['real_name'];
        $verifyKey = "IVRmwkqit9i6Ah";
        $host = "https://safrvcert.market.alicloudapi.com";
        $path = "/safrv_2meta_id_name/";
        $method = "GET";
        $appcode = "fdc6cc4d4ffe4c1c9602bd58b202681c";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "__userId=".$accountId."&customerID=".$customerID."&identifyNum=".$identifyNum."&userName=".$userName."&verifyKey=".$verifyKey;
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $ret = curl_exec($curl);

        $start = strpos($ret,"{");

        $end = strripos($ret,"}");

        $resultt = substr($ret,$start);

        $ret_arr = json_decode($resultt,true);

        if($ret_arr['code'] == 200){
            if($ret_arr['value']['bizCode'] != 0){
                return $this->error($ret_arr['message']);
            }else{

                $param['is_real'] = 1;
                $res = DB::table('users')->where('id',auth('api')->user()->id)->update($param);
                if ($res !== false) {
                    return $this->success();
                } else {
                    return $this->error('认证失败');
                }
            }
        }else{
             return $this->error('姓名与身份证号码不匹配');
        }

    }

    /**
     * 支付密码
     */
    public function paymentPassword(Request $request)
    {
        $param = $request->all();
        $user = auth('api')->user();
//        Hash::check($param['payment_password'],$user['payment_password'])

        //验证短信验证码
        $sms = new Sms();
        $check = $sms->check($user['mobile'], $param['code']);
        if (!$check) return $this->error($sms->getError());

        $user->payment_password = bcrypt($param['payment_password']);

//        echo encrypt(bcrypt($param['payment_password']));exit;
        $res = $user->save();
        if ($res !== false) {
            return $this->success();

        } else {
            return $this->error('找回密码失败');
        }
    }

    /**
     * 个人中心首页
     */
    public function myCenter()
    {
        $user = User::find(auth('api')->user()->id);
        $userInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'account' => $user->account,
            'money' => $user->money,
            'capital' => $user->capital,
            'level_text' => $user->level_text,
            'is_real' => $user->is_real,
        ];

        $key = 'user:goods:' . auth('api')->user()->id;
        $count = [
            'collectGoods' => DB::table('collect')->where(['commentable_type' => 'goods', 'user_id' => $user->id])->count(),
            'collectStore' => DB::table('collect')->where(['commentable_type' => 'store', 'user_id' => $user->id])->count(),
            'collectTyfon' => DB::table('collect')->where(['commentable_type' => 'tyfon', 'user_id' => $user->id])->count(),
            'viewHistory' => Redis::llen($key),
            'follow' => DB::table('follow')->where(['commentable_type' => 'user', 'user_id' => $user->id])->count(),
            //'comment' => DB::table('comment')->where(['user_id' => $user->id])->count(),
        ];

        $order = [
            'pay' => DB::table('order')->where(function($query){
                $query->where('user_id', auth('api')->user()->id);
                $query->where('deleted', 0);
                $query->where('pay_status',0)->where('order_status',0);
            })->count(),
            'share' => Order::where(function($query){
                $query->where('user_id', auth('api')->user()->id);
                $query->where('deleted', 0);
                $query->where('order_prom_type', 2);
                $query->whereIn('order_status', [0, 1]);
                $query->where('shipping_status', 0);
                $query->where('pay_status', 1);

                $query->whereHas('team_found', function ($query) {
                    $query->where(function ($query) {
                        $query->where('status', 1);
                    });
                });
            })->count(),
            'confirm' => DB::table('order')->where(function($query){
                $query->where('user_id', auth('api')->user()->id);
                $query->where('deleted', 0);
                $query->where('order_status',1)->where('shipping_status',1);

            })->count(),
            'comment' => DB::table('order')->where(function($query){
                $query->where('user_id', auth('api')->user()->id);
                $query->where('deleted', 0);
                $query->where('order_status',2)->where('shipping_status',1);
            })->count(),
            'refund' => DB::table('return_goods')->where(function ($query) {
                    $query->where('user_id', auth('api')->user()->id);
                })
                ->count()
        ];

        $data = [
            'userInfo' => $userInfo,
            'count' => $count,
            'order' => $order,
            'telephone' => get_config_by_name('service_telephone'),
            'middule' => get_config_by_name('mycenter_invite_image')
        ];
        return $this->success($data);
    }

    /**
     * 余额界面
     */
    public function money()
    {
        //今日收益
        $today = DB::table('money_log')->where(function ($query) {
            $query->where('user_id', auth('api')->user()->id);
            $query->where('type', 0);
            $query->where('source', 2);
            $query->whereDate('change_time', Carbon::today()->toDateString());
        })->sum('change_money');

        //累计收益
        $total = DB::table('money_log')->where(function ($query) {
            $query->where('user_id', auth('api')->user()->id);
            $query->where('type', 0);
            $query->where('source', 5);
        })->sum('change_money');

        //待结算收益
        $wait = DB::table('user_rebate_log')->where(function ($query) {
            $query->where('user_id', auth('api')->user()->id);
            $query->where('type', 0);
            $query->where('is_settle', 0);
        })->sum('change_money');

        return $this->success([
            'money' => auth('api')->user()->money,
            'today' => $today,
            'total' => $total,
            'wait' => $wait
        ]);
    }

    /**
     * 余额明细
     */
    public function moneyLog(Request $request)
    {
        $type = $request->input('type'); //类型:0=收入,1=支出
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = DB::table('money_log')->where(function ($query) use ($type) {
            $query->where('user_id', auth('api')->user()->id);
            $query->where('type', $type);
        })->offset($offset)->limit($limit)->orderBy('id', 'desc')->get();

        return $this->success($list);
    }

    /**
     * 销售收益明细
     */
    public function rebateLog(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $list = DB::table('user_rebate_log')->where(function ($query) {
            $query->where('user_id', auth('api')->user()->id);
            $query->where('type', 0);
        })->offset($offset)->limit($limit)->orderBy('id', 'desc')->get();

        return $this->success($list);

    }

    /**
     * 麦穗记录
     */
    public function accountLog(Request $request)
    {
        //type 类型:0=收入,1=支出
        //source 来源:0=签到赠送，1=购物返利，2=购物抵扣,3=发布心情,4=取消订单退回,5=申请售后退回,6=转账收入,7=转账支出,8=升级一级代理获得,9=邀请代理

        $scene = $request->input('scene'); //1=已使用，2=已转账，3=获得记录
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $list = DB::table('account_log')->where(function ($query) use ($scene) {
            $query->where('user_id', auth('api')->user()->id);

            switch ($scene) {
                case 1:
                    $query->where('type', 1);
                    $query->where('source', '!=', 7);
                    break;
                case 2:
                    $query->where('type', 1);
                    $query->where('source', 7);
                    break;
                case 3:
                    $query->where('type', 0);
                    break;
                default;

            }

        })->offset($offset)->limit($limit)->orderBy('id', 'desc')->get();

        return $this->success($list);
    }

    /**
     * 麦穗转让
     */
    public function accountTrans(Request $request)
    {
        $mobile = $request->input('mobile');
        $num = $request->input('num', 1);
        //代理用户才能转让麦穗
        if (auth('api')->user()->level < 1) return $this->error('请先升级为代理，才能转让');
        if ($num < 1) return $this->error('数量必须大于1个');
        if (!$target = User::where('mobile', $mobile)->first()) return $this->error('对方账号不存在');

        //扣除自己麦穗
        User::accountLog(auth('api')->user()->id, '-' . $num, '', '转让支出', 1, 7);
        //增加对方麦穗
        User::accountLog($target->id, $num, '', '转让收入', 0, 6);
        return $this->success();
    }

    /**
     * 小区长申请
     */
    public function village(Request $request)
    {
        if(!auth('api')->user()->level) return $this->error('代理用户才能升级小区长');

        $villageLevel = DB::table('village_level')->where([
            'id' => auth('api')->user()->level
        ])->first();

        return $this->success(['price' => $villageLevel->price]);
    }

    /**
     * 小区长支付提交订单
     */
    public function addVillageOrder(Request $request)
    {
        $pay_type = $request->input('pay_type');//支付方式:1=支付宝，2=微信
        $order_sn = build_order_sn('vl');
        if(!auth('api')->user()->level) return $this->error('代理用户才能升级小区长');
        $villageLevel = DB::table('village_level')->where([
            'id' => auth('api')->user()->level
        ])->first();

        DB::table('village_order')->insert([
            'order_sn' => $order_sn,
            'pay_status' => 0,
            'user_id' => auth('api')->user()->id,
            'created_at' => date('Y-m-d H:i:s'),
            'order_amount' => $villageLevel->price,
            'pay_type' => $pay_type,
        ]);

        return $this->success(['order_sn' => $order_sn, 'order_amount' => $villageLevel->price]);

    }

    /**
     * 店铺管理
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteInfo()
    {
        $user = User::find(auth('api')->user()->id);
        return $this->success([
            'user' => [
                'name' => $user->name,
                'avatar' => $user->avatar,
                'community_code' => $user->community_code
            ],
            'count' => DB::table('invite')->where('user_id', $user->id)->count()
        ]);
    }

    /**
     * 店铺管理邀请商家列表
     */
    public function inviteList(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Invite::with(['store' => function ($query) {
            $query->select('id', 'shop_name');
        }])->where('user_id', auth('api')->user()->id)
            ->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($list);
    }

    /**
     * 粉丝管理--我的粉丝
     */
    public function children(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = User::where(function($query){
//            $query->Where('second_leader',auth('api')->user()->id);
//            $query->orWhere('third_leader',auth('api')->user()->id);
            $query->where('first_leader',auth('api')->user()->id);
            $query->where('level', 0);

        })
            ->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->select('id','name','avatar','level','mobile','created_at')
            ->get();

        return $this->success($list);
    }

    /**
     * 业绩管理--直推代理
     */
    public function child(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = User::where(function($query){
            $query->where('first_leader',auth('api')->user()->id);
            $query->where('level', '>', 0);

        })
            ->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->select('id','name','avatar','level','mobile','created_at')
            ->get();

        return $this->success($list);
    }

    /**
     * 业绩管理 (代理)
     */
    public function achieve(Request $request)
    {
        $child = User::where(function($query){
            $query->where('first_leader',auth('api')->user()->id);
            $query->where('level', '>', 0);
        })->count();

        $children = User::where(function($query){
            $query->Where('second_leader',auth('api')->user()->id);
            $query->orWhere('third_leader',auth('api')->user()->id);
            $query->where('level', '>', 0);
        })->count();

        //销售额 直推代理的订单额
        $childIds = User::where(function($query){
            $query->where('first_leader',auth('api')->user()->id);
            $query->where('level', '>', 0);
        })->pluck('id')->toArray();

        $amount = Order::where(function($query) use ($childIds){
            $query->whereIn('user_id',$childIds);
            $query->where('pay_status',1);
            $query->whereHas('order_goods',function($query){
                $query->whereNotIn('goods_type',[1,2]);
                $query->where('prom_type','!=',3);
            });
        })->sum('order_amount');

//        dd(Carbon::parse('-1 months')->toDateString());
//        dd(Carbon::today()->toDateString());
        $lastMouthAmount = Order::where(function($query) use ($childIds){
            $query->whereIn('user_id',$childIds);
            $query->where('pay_status',1);
            $query->where('created_at','>',Carbon::parse('-1 months')->toDateTimeString());
            $query->whereHas('order_goods',function($query){
                $query->whereNotIn('goods_type',[1,2]);
                $query->where('prom_type','!=',3);
            });
        })->sum('order_amount');

        return $this->success([
            'child' => $child,
            'cildren' => $children,
            'amount' => $amount,
            'lastMouthAmount' => $lastMouthAmount
        ]);

    }

    /**
     * 资金管理充值页面
     */
    public function capital()
    {
        $amount = get_config_by_name('capital_amount');
        return $this->success(['amount' => $amount]);
    }
    
    /**
     * 资金管理充值下单
     */
    public function addCaptialOrder(Request $request)
    {
        $pay_type = $request->input('pay_type');//支付方式:1=支付宝，2=微信
        $order_sn = build_order_sn('cp');
        $amount = get_config_by_name('capital_amount');
        DB::table('capital_order')->insert([
            'order_sn' => $order_sn,
            'pay_status' => 0,
            'user_id' => auth('api')->user()->id,
            'created_at' => date('Y-m-d H:i:s'),
            'order_amount' => $amount,
            'pay_type' => $pay_type,
        ]);

        return $this->success(['order_sn' => $order_sn, 'order_amount' => $amount]);
    }

    /**
     * 资金管理充值记录
     */
    public function capitalLog(Request $request)
    {
        $data = DB::table('capital_log')->where(function($query){
            $query->where('user_id',auth('api')->user()->id);
            $query->where('type',0);
            $query->where('source',0);
        })->get();
        return $this->success(['data' => $data]);
    }

    /**
     * 关注取消
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delFollow(Request $request)
    {
        $id = $request->input('id');


        $res = Follow::where('id',$id)->delete();
        if($res){
            return $this->success();
        }else{
            return $this->error('取消失败');
        }
    }



}