<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    //pay_status: 支付状态.0待支付，1已支付，2支付失败，3已退款，4拒绝退款
    //order_status : 订单状态.0待确认，1已确认，2已收货，3已取消(用户取消订单)，4已完成(评论成功后改为4)，5已作废
    //shipping_status : 发货状态:0=未发货，1=已发货，2=部分发货
    //shipping_code: 配送方式编号：ems=快递配送，same_city=同城配送，in_shop=到点自取
    //pay_type : 支付方式:0=余额,1=支付宝,2=微信
    //order_prom_type: 活动类型：0默认，1=限时抢购，2=拼团，3=抽奖

    /**
     *  订单用户端显示按钮
     * 去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
     * 取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
     * 确认收货  AND shipping_status=1 AND order_status=1
     * 评价      AND order_status=2
     * 查看物流  if(!empty(物流单号))
     */

    protected $table = 'order';
    public $timestamps = false;
    protected $appends = [
        'pay_status_text',
        'order_status_text',
        'shipping_status_text',
        'order_prom_type_text'
    ];

    public function order_goods(){
        return $this->hasMany('App\Models\OrderGoods');
    }

    public function store(){
        return $this->belongsTo('App\Models\Store');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team_found()
    {
        return $this->hasOne(TeamFound::class);
    }

    public static function getPayStatusArr(){
        return [0 => '待支付', 1 => '已支付', 2 => '支付失败', 3 => '已退款', 4 => '拒绝退款', 5 => '已取消'];
    }

    public static function getOrderStatusArr(){
        return [0 => '待确认',1 => '已确认', 2 => '已收货', 3 => '已取消', 4 => '已完成',5 => '已作废'];
    }

    public static function getShippingStatusArr(){
        return [0 => '未发货', 1 => '已发货'];
    }

    public static function getPayTypeArr(){
        //支付方式:0=余额,1=支付宝,2=微信
        return [0 => '余额', 1 => '支付宝', 2 => '微信'];
    }

    public static function getShippingCodeArr(){
        //配送方式编号：ems=快递配送，same_city=同城配送，in_shop=到点自取
        return ['ems' => '快递配送', 'same_city' => '同城配送', 'is_shop' => '到店自取'];
    }

    public static function getIsCommentArr(){
        return [0 => '否', 1 => '是'];
    }

    public function getPayStatusTextAttribute(){
        return isset($this->pay_status) ? self::getPayStatusArr()[$this->pay_status] : '';
    }

    public function getOrderStatusTextAttribute(){
        return isset($this->order_status) ? self::getOrderStatusArr()[$this->order_status] : '';
    }

    public function getShippingStatusTextAttribute(){
        return isset($this->shipping_status) ? self::getShippingStatusArr()[$this->shipping_status] : '';
    }

    public function getOrderPromTypeTextAttribute(){
        return isset($this->order_prom_type) ?  self::getOrderPromTypeArr()[$this->order_prom_type] : '';
    }

    public function getTotalGoodsNumAttribute(){
        $count = DB::table('order_goods')->where('order_id',$this->id)->sum('goods_num');
        return $count;
    }

    public static function getOrderPromTypeArr()
    {
        //活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
        return [0 => '默认', 1 => '限时抢购', 2 => '拼团', 3 => '抽奖'];
    }




}
