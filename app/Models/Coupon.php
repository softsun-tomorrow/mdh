<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    //
    protected $table = 'coupon';


    public static function getSendType(){
        //发放类型 0签到赠送 1 按用户发放 2 免费领取 3 线下发放
//        return [0 => '签到赠送',2 => '免费领取', 3 => '线下发放'];
        return [2 => '免费领取', 3 => '线下发放'];
    }

    public static function getStatus(){
        //状态：0=无效，1=有效
        return [0 => '无效', 1 => '有效'];
    }

    public static function getCouponType(){
        //优惠券类型：0=平台通用优惠券，1=店铺优惠券，2=会员卡优惠券
        return [1 => '店铺优惠券', 2 => '会员卡优惠券'];
    }

    public function getFullCatAttribute(){
        $obj = Coupon::find($this->id);

        if($obj) return Category::find($obj->cat1)->name . ' > ' . Category::find($obj->cat2)->name . ' > ' . Category::find($obj->cat3)->name;
    }

    /**
     * 签到第七条赠送优惠券
     */
    public static function getSignCoupon($userId,$coupon_type,$send_type,$store_id=0){
        //第7天, 优惠券大礼包
        $coupon = DB::table('coupon')->where([
            'coupon_type' => $coupon_type,
            'send_type' => $send_type,
            'status' => 1
        ])->orderBy('id','desc')->first();

//        $userCouponCount = UserCoupon::where([
//            'user_id' => auth('api')->user()->id,
//            'coupon_id' => $coupon->id
//        ])->count();
//        if($userCouponCount) return $this->error('您已经领取过啦！');

        $param = [
            'coupon_id' => $coupon->id,
            'user_id' => $userId,
            'store_id' => $coupon->store_id,
            'send_time' => date('Y-m-d H:i:s'),
        ];
        $res = DB::table('user_coupon')->insert($param);
    }

}
