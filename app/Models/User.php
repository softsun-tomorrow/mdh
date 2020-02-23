<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Hashids\Hashids;

/**
 * level 等级：0=普通（粉丝用户），1=一级代理（合伙人），2=二级代理（联创合伙人），3=三级代理（分红股东），4=四级代理（执行董事）
 * village_level 小区长等级: ，1=一级小区长，2=二级小区长，3=三级小区长，4=四级小区长
 * Class User
 * @package App\Models
 */
class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    const LEVEL = [0 => '普通用户', 1 => '合伙人', 2 => '联创合伙人', 3 => '分红股东', 4 => '执行董事'];
    const VILLAGE_LEVEL = [0 => '无', 1 => '一级小区长', 2 => '二级小区长', 3 => '三级小区长', 4 => '四级小区长'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','mobile','num','avatar','first_leader'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','payment_password',
    ];

    protected $appends = [
        'level_text',
        'village_level_text'
    ];



    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        // TODO: Implement getJWTIdentifier() method.
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        // TODO: Implement getJWTCustomClaims() method.
        return [];
    }

    public function getLevelTextAttribute()
    {
        if(isset($this->level)) return self::LEVEL[$this->level];
    }

    public function getVillageLevelTextAttribute()
    {
        if(isset($this->village_level)) return self::VILLAGE_LEVEL[$this->village_level];
    }

    /**
     * 用户麦穗变动
     * @param $user_id
     * @param $account
     * @param string $order_sn
     * @param string $remark
     * @param int $type 类型:0=收入,1=支出
     * @param int $source 来源:0=签到赠送，1=购物返利，2=购物抵扣,3=发布心情,4=取消订单退回,5=申请售后退回,6=转让收入,7=转让支出,8=升级一级代理获得,9=邀请代理
     */
    public static function accountLog($user_id,$account,$order_sn = '',$remark = '',$type = 0, $source = 0){
        $user = DB::table('users')->where('id',$user_id)->first();
        $afterAccount = ($user->account*100 + $account*100)/100;

        DB::transaction(function() use ($afterAccount,$user_id,$user,$account,$remark,$order_sn,$type,$source){
            $afterAccount*100 < 0 ? $afterAccount = 0 : $afterAccount;
            DB::table('users')->where('id',$user_id)->update(['account' => $afterAccount]);
            DB::table('account_log')->insert([
                'user_id' => $user_id,
                'before_money' => $user->account,
                'change_money' => $account,
                'after_money' => $afterAccount,
                'change_time' => date('Y-m-d H:i:s'),
                'desc' => $remark,
                'order_sn' => $order_sn,
                'type' => $type,
                'source' => $source

            ]);
        });

    }


    /**
     * 用户余额变动
     * @param $user_id
     * @param $money
     * @param string $order_sn
     * @param string $remark
     * @param int $type 类型:0=收入,1=支出
     * @param int $source 来源:0=购买商品，1=申请售后退回,2=拼团失败退回,3=用户申请提现，4=提现审核失败,5=待结算流入,6=小区长邀请商家佣金
     */
    public static function moneyLog($user_id,$money,$order_sn = '',$remark = '',$type = 0, $source = 0){
        $user = DB::table('users')->where('id',$user_id)->first();
        $aftermoney = ($user->money*100 + $money*100)/100;

        DB::transaction(function() use ($aftermoney,$user_id,$user,$money,$remark,$order_sn,$type,$source){

            DB::table('users')->where('id',$user_id)->update(['money' => $aftermoney]);
            DB::table('money_log')->insert([
                'user_id' => $user_id,
                'before_money' => $user->money,
                'change_money' => $money,
                'after_money' => $aftermoney,
                'change_time' => date('Y-m-d H:i:s'),
                'desc' => $remark,
                'order_sn' => $order_sn,
                'type' => $type,
                'source' => $source

            ]);
        });

    }


    /**
     * 用户收益变动
     * @param $user_id
     * @param $money
     * @param string $order_sn
     * @param string $remark
     * @param int $type 类型:0=收入,1=支出
     * @param int $source 来源:0=销售佣金,1=管理津贴,2=购物收益，3=分享收益,4=收益结算，5=团队营业额分红
     */
    public static function rebateLog($user_id,$money,$order_sn = '',$remark = '',$type = 0, $source = 0){
        $user = DB::table('users')->where('id',$user_id)->first();
        $aftermoney = ($user->money*100 + $money*100)/100;

        DB::transaction(function() use ($aftermoney,$user_id,$user,$money,$remark,$order_sn,$type,$source){

            DB::table('users')->where('id',$user_id)->update(['rebate' => $aftermoney]);
            DB::table('user_rebate_log')->insert([
                'user_id' => $user_id,
                'before_money' => $user->money,
                'change_money' => $money,
                'after_money' => $aftermoney,
                'change_time' => date('Y-m-d H:i:s'),
                'desc' => $remark,
                'order_sn' => $order_sn,
                'type' => $type,
                'source' => $source

            ]);
        });

    }


    /**
     * 用户资金变动
     * @param $user_id
     * @param $money
     * @param string $order_sn
     * @param string $remark
     * @param int $type 类型:0=收入,1=支出
     * @param int $source 来源:0=资金充值
     */
    public static function capitalLog($user_id,$money,$order_sn = '',$remark = '',$type = 0, $source = 0){
        $user = DB::table('users')->where('id',$user_id)->first();
        $aftermoney = ($user->money*100 + $money*100)/100;

        DB::transaction(function() use ($aftermoney,$user_id,$user,$money,$remark,$order_sn,$type,$source){

            DB::table('users')->where('id',$user_id)->update(['capital' => $aftermoney]);
            DB::table('capital_log')->insert([
                'user_id' => $user_id,
                'before_money' => $user->money,
                'change_money' => $money,
                'after_money' => $aftermoney,
                'change_time' => date('Y-m-d H:i:s'),
                'desc' => $remark,
                'order_sn' => $order_sn,
                'type' => $type,
                'source' => $source

            ]);
        });

    }

    public static function buildReferralCode($id){
        $hashids = new Hashids('', 6, 'abcdefghijklmnopqrstuvwxyz');
        $code = $hashids->encode($id); // o2fXhV
        return $code;
    }

    public static function decodeReferralCode($code){
        $hashids = new Hashids('', 6, 'abcdefghijklmnopqrstuvwxyz');
        $res = $hashids->decode($code);
        return isset($res[0]) ? $res[0] : 0;
    }

    public static function buildCommunityCode($id){
        $hashids = new Hashids('community', 6, 'abcdefghijklmnopqrstuvwxyz');
        $code = $hashids->encode($id); // o2fXhV
        return $code;
    }

    public static function decodeCommunityCode($code){
        $hashids = new Hashids('community', 6, 'abcdefghijklmnopqrstuvwxyz');
        $res = $hashids->decode($code);
        return isset($res[0]) ? $res[0] : 0;
    }

    /**
     * 生成二维码
     * @param $id
     * @return string
     */
    public static function buildReferralImg($id){
        $app_url  = env('APP_URL');
        $code = self::buildReferralCode($id);
        $url = $app_url . '/web/register' . '?referral_code='.$code;
        $path = 'images/'.uniqid().rand(1000,9999).'.png';
        $savepath = './uploads/' . $path;
        \QrCode::format('png')->size(310)->generate($url,$savepath);
        return $path;
    }


    public static function afterInsert($user){
        $id = $user->id;
        $referral_code = self::buildReferralCode($id);
        $referral_img = self::buildReferralImg($id);

        if($user->first_leader){
            $parent = DB::table('users')->where('id',$user->first_leader)->first();
            $parent = get_object_vars($parent);
            $second_leader = $parent['first_leader'];
            $third_leader = $parent['second_leader'];
        }else{
            $second_leader = 0;
            $third_leader = 0;
        }

        DB::table('users')->where('id',$id)->update([
            'referral_code' => $referral_code,
            'referral_img' => $referral_img,
            'second_leader' => $second_leader,
            'third_leader' => $third_leader
        ]);

    }

    public static function getGenderArr(){
        return [0 => '保密', 1=> '男', 2 => '女'];
    }

    public function tyfon(){
        return $this->morphMany('App\Models\Tyfon', 'commentable');
    }

    public function fans(){
        return $this->hasMany('App\Models\Follow','commentable_id');
    }

}