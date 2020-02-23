<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Card extends Model
{
    use SoftDeletes;
    //
    protected $table = 'card';

//    protected $appends = ['store_id_text','card_type_id_text'];

    public static function getTypeArr()
    {
        //类型：0=离线，1=在线
        return [0 => '离线', 1 => '在线'];
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $card_no = self::getCardNo($model->store_id);
            $cardType = CardType::find($model->card_type_id);

            $end_time = date('Y-m-d H:i:s',(time()+$cardType->expire ));
            $rate_end_time = date('Y-m-d H:i:s',(time()+$cardType->rate_expire ));
            self::where('id', $model->id)->update([
                'card_no' => $card_no,
                'account' => $cardType->account,
                'end_time' => $end_time,
                'rate' => $cardType->rate,
                'rate_end_time' => $rate_end_time
            ]);
        });
    }

    public function store(){
        return $this->belongsTo('App\Models\Store','store_id');
    }

    public function card_type(){
        return $this->belongsTo('App\Models\CardType','card_type_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public static function getCardNo($store_id)
    {
        $store = Store::find($store_id);
        $district_code = Area::find($store->district_id)->code;
        $rand = rand(100000, 999999);
        $card_no = $district_code . $store_id . $rand;
        return $card_no;
    }

    public function getStoreIdTextAttribute(){
        if($store = Store::find($this->store_id)) return $store->shop_name;

    }

    public function getCardTypeIdTextAttribute(){
        if($cardType = CardType::find($this->card_type_id)) return $cardType->name;
    }

    /**
     * 会员卡金额变动
     * @param $card_id
     * @param $account
     * @param string $order_sn
     * @param string $remark
     * @param int $type 类型:0=收入,1=支出
     * @param int $source 来源:0=签到赠送，1=充值,2=订单抵扣,3=取消订单退回,4=扫码支付
     */
    public static function cardAccountLog($card_id,$account,$type = 0, $source = 0,$order_sn = '',$remark = ''){
        $card = DB::table('card')->where('id',$card_id)->first();
        $afterAccount = ($card->account*100 + $account*100)/100;

        DB::transaction(function() use ($afterAccount,$card_id,$card,$account,$remark,$order_sn,$type,$source){

            DB::table('card')->where('id',$card_id)->update(['account' => $afterAccount]);
            DB::table('card_account_log')->insert([
                'user_id' => $card->user_id,
                'card_id' => $card_id,
                'before_money' => $card->account,
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
     * 会员卡积分变动
     * @param $card_id
     * @param $score
     * @param string $order_sn
     * @param string $remark
     * @param int $type 类型:0=收入,1=支出
     * @param int $source 来源:0=签到赠送，1=购物返利,2=订单抵扣,3=积分兑换
     */
    public static function cardScoreLog($card_id,$score,$type = 0, $source = 0,$order_sn = '',$remark = ''){
        $card = DB::table('card')->where('id',$card_id)->first();
        $afterscore = ($card->score*100 + $score*100)/100;

        DB::transaction(function() use ($afterscore,$card_id,$card,$score,$remark,$order_sn,$type,$source){

            DB::table('card')->where('id',$card_id)->update(['score' => $afterscore]);
            DB::table('card_score_log')->insert([
                'user_id' => $card->user_id,
                'card_id' => $card_id,
                'before_money' => $card->score,
                'change_money' => $score,
                'after_money' => $afterscore,
                'change_time' => date('Y-m-d H:i:s'),
                'desc' => $remark,
                'order_sn' => $order_sn,
                'type' => $type,
                'source' => $source
            ]);
        });

    }


    /***
     * 获取用户在线会员卡id
     */
    public static function getCardId($user_id,$store_id){
        return  DB::table('card')->where(['user_id' => $user_id, 'store_id' => $store_id])->value('id');
    }

    /**
     * 获取用户在线会员卡
     */
    public static function getUserOnlineCard($user_id,$store_id){
        return self::where(['user_id' => $user_id, 'store_id' => $store_id])->first();
    }

    /**
     * 获取即将到期的麦穗
     */
    public static function getExpireScore($user_id,$card_id){
        $startYear = Carbon::parse('-2 year')->startOfYear();
        $endYear = Carbon::parse('-2 year')->endOfYear();
        //这一年的总获取
        $in = DB::table('card_score_log')->where(function($query) use ($user_id,$card_id,$startYear,$endYear){
            $query->whereBetween('change_time',[$startYear,$endYear]);
            $query->where('user_id',$user_id);
            $query->where('card_id',$card_id);
            $query->where('type',0);
        })->sum('change_money');

        //所有的总支出
        $out = DB::table('card_score_log')->where(function($query) use ($user_id,$card_id,$startYear,$endYear){
            $query->whereBetween('change_time',[$startYear,Carbon::now()->endOfYear()]);
            $query->where('user_id',$user_id);
            $query->where('card_id',$card_id);
            $query->where('type',1);
        })->sum('change_money');
        $expire = $in - abs($out);
        $value = $expire > 0 ?  $expire : 0;
        return $value;
    }

    public function getExpireScoreAttribute(){
       return $this->getExpireScore($this->user_id,$this->id);
    }







}
