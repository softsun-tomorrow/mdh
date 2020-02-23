<?php

namespace App\Logic;

use App\Models\Coupon;
use App\Models\Store;
use App\Models\UserCoupon;
use Illuminate\Database\Eloquent\Model;

class CouponLogic extends Model
{
    //优惠券逻辑类
    protected $store_id;
    protected $user_id;

    public function setStoreId($store_id)
    {
        $this->store_id = $store_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * 下单时获取用户可用的优惠券
     * @param int $coupon_type 优惠券类型：0=平台通用优惠券，1=店铺优惠券，2=会员卡优惠券
     */
    public function getCoupon($couponType, $storeTotalPrice)
    {
        $list = UserCoupon::whereHas('coupon',function ($query) use ($storeTotalPrice, $couponType) {
            $query->where(['status' => 1, ['use_start_time', '<=', date('Y-m-d H:i:s')], ['use_end_time', '>=', date('Y-m-d H:i:s')]]);
            $query->where('condition', '<=', $storeTotalPrice);
            $query->where('coupon_type', $couponType);
            if ($couponType) $query->where('store_id', $this->store_id);
        })->with('coupon')->where(function($query) use ($couponType){
            $query->where('status',0);
            $query->where('user_id',$this->user_id);
            if ($couponType) $query->where('store_id',$this->store_id);

        })->get();
        return $list;
    }


    /**
     * 获取店铺优惠券列表
     */
    public function getStoreCouponList($limit = 100)
    {
        $list = Coupon::where(function ($query) {
            $query->where(['store_id' => $this->store_id, 'status' => 1, ['send_start_time', '<=', date('Y-m-d H:i:s')], ['send_end_time', '>=', date('Y-m-d H:i:s')]]);
        })
            ->limit($limit)
            ->orderBy('id','desc')
            ->get();

        return $list;
    }

    /**
     * 获取商品的优惠券
     */
    public function getGoodsCouponList($limit = 100){
        $list = Coupon::where(function ($query) {
            $query->where(['store_id' => $this->store_id, 'status' => 1, ['send_start_time', '<=', date('Y-m-d H:i:s')], ['send_end_time', '>=', date('Y-m-d H:i:s')]]);
        })
            ->limit($limit)
            ->orderBy('id','desc')
            ->select('id','name','money','condition')
            ->get();

        return $list;
    }

}