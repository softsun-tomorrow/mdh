<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReturnGoods extends Model
{
    //售后
    protected $table = 'return_goods';
    public $timestamps = false;

    protected $appends = [
        'status_text',
        'reason_text',
        'type_text',
    ];

    public static function TypeArr(){
        //退款类型：0仅退款 1退货退款 2换货
        return [0 => '仅退款', 1 => '退货退款'];
    }

    //申请售后时是否已收货 0未收货1已收货
    public static function isReceiveArr(){
        return [0 => '未收货', 1 => '已收货'];
    }

    //退款原因
    public static function reasonArr(){
        return [0 => '拍错了/定单信息错误', 1 => '未按约定时间发货', 2 => '缺货', 3 => '不想要了', 4 => '七天无理由退货'];
    }

    //-2用户取消-1不同意0待审核1通过2已发货3已收货4换货完成5退款完成6申诉仲裁
    public static function statusArr(){
        //-2用户取消-1不同意0待审核1通过2已发货3已收货4换货完成5退款完成6申诉仲裁
        return [-2 => '用户取消', -1 => '不同意', 0 => '待审核', 1 => '审核通过', 3 => '已收货', 5 => '退款完成'];
    }

    public function getImgsAttribute($imgs){
        return $imgs ? explode(',',$imgs) : [];
    }

    /***
     * 审核通过后退款
     */
    public static function refund($model,$status){
        $model->checktime = date('Y-m-d H:i:s');
        $model->save();

        $account = intval($model->refund_integral) + $model->refund_money*100;
        User::moneyLog($model->user_id,$account,'','申请售后退回',0,1);

        //修改order_goods表is_send状态
        DB::table('order_goods')->where('id',$model->order_goods_id)->update(['is_send' => 3]);
    }


    public function order_goods(){
        return $this->belongsTo('App\Models\OrderGoods');
    }

    public function getStatusTextAttribute(){
        return isset($this->status) ? self::statusArr()[$this->status] : '';
    }

    public function getTypeTextAttribute(){
        return isset($this->type) ? self::TypeArr()[$this->type] : '';
    }

    public function getReasonTextAttribute(){
        return isset($this->reason) ? self::reasonArr()[$this->reason] : '';
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }



}
