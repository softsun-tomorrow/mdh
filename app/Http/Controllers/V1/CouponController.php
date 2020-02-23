<?php

namespace App\Http\Controllers\V1;

use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    //优惠券

    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['index']]);
    }

    /**
     * 优惠券列表
     * @param Request $request
     */
    public function index(Request $request){
        $cat1 = $request->input('cat1', 0);
        $cat2 = $request->input('cat2', 0);
        $cat3 = $request->input('cat3', 0);
        $province_id = $request->input('province_id', 0);
        $city_id = $request->input('city_id', 0);
        $district_id = $request->input('district_id', 0);
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);

        $list = Coupon::where(function($query) use ($cat1,$cat2,$cat3,$province_id,$city_id,$district_id,$offset,$limit){
            $query->where([ 'status' => 1, ['send_start_time','<=',date('Y-m-d H:i:s')], ['send_end_time','>=',date('Y-m-d H:i:s')]]);
            if($cat1) $query->where('cat1', $cat1);
            if($cat2) $query->where('cat1', $cat2);
            if($cat3) $query->where('cat1', $cat3);
            if($province_id) $query->where('province_id', $province_id);
            if($city_id) $query->where('city_id', $city_id);
            if($district_id) $query->where('district_id', $district_id);
        })
            ->orderBy('coupon_type','asc')
            ->orderBy('id','desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        return $this->success($list);

    }

    /**
     * 用户领取优惠券
     */
    public function getCoupon(Request $request){
        $id = $request->input('id');
        $coupon = Coupon::find($id);

        $userCouponCount = UserCoupon::where([
            'user_id' => auth('api')->user()->id,
            'coupon_id' => $id,
            'status' => 0
        ])->count();
        if($userCouponCount) return $this->error('您已经领取过啦！');

        $param = [
            'coupon_id' => $id,
            'user_id' => auth('api')->user()->id,
            'store_id' => $coupon->store_id,
            'send_time' => date('Y-m-d H:i:s'),
        ];
        $res = DB::table('user_coupon')->insert($param);
        if($res){
            return $this->success();
        }else{
            return $this->error('领取失败');
        }
    }

    /**
     * 我的优惠券
     */
    public function myCoupon(Request $request){
        //0未使用1已使用2已过期
        $store_id = $request->input('store_id',0);

        $status = $request->input('status');
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);
        $list = UserCoupon::with('coupon')->where(function($query) use ($store_id,$status,$offset,$limit){
            $query->where(['user_id' => auth('api')->user()->id]);
            $query->where('status',$status);
            if($store_id) $query->where('store_id',$store_id);
        })
            ->orderBy('id','desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        return $this->success($list);

    }

    /**
     * 店铺优惠券列表
     */
    public function storeCoupon(Request $request){

        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);
        $store_id = $request->input('store_id',0);

        $list = Coupon::where(function($query) use ($store_id,$offset,$limit){
            $query->where([ 'status' => 1, ['send_start_time','<=',date('Y-m-d H:i:s')], ['send_end_time','>=',date('Y-m-d H:i:s')]]);
            $query->where('store_id',$store_id);
        })
            ->orderBy('id','desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        return $this->success($list);
    }


}
