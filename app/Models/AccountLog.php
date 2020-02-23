<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountLog extends Model
{
    //来源:0=签到赠送，1=购物返利，2=麦穗抵扣,3=发布大喇叭,4=取消订单退回,5=申请售后退回，6=麦穗兑换，7=积分兑换麦穗

    protected $table = 'account_log';
    public $appends = ['type_text', 'source_text'];

    public static function getTypeText()
    {
        //类型:0=收入,2=支出
        return [0 => '收入', 1 => '支出'];
    }

    public static function getSourceText()
    {

        return [0 => '签到赠送', 1 => '购物返利', 2 => '麦穗抵扣', 3 => '发布大喇叭', 4 => '取消订单退回', 5 => '申请售后退回', 6 => '麦穗兑换', 7 => '积分兑换麦穗', 8 => '升级一级代理获得', 9 => '邀请代理'];
    }

    public function getTypeTextAttribute()
    {
        $type = self::getTypeText();
        return $type[$this->type];
    }

    public function getSourceTextAttribute()
    {
        $source = self::getSourceText();
        return isset($this->source) ? $source[$this->source] : '';
    }

    /**
     * 获取即将到期的麦穗
     */
    public static function getExpireAccount($user_id)
    {
        $startYear = Carbon::parse('-2 year')->startOfYear();
        $endYear = Carbon::parse('-2 year')->endOfYear();


        //这一年的总获取
        $in = DB::table('account_log')->where(function ($query) use ($user_id, $startYear, $endYear) {
            $query->whereBetween('change_time', [$startYear, $endYear]);
            $query->where('user_id', $user_id);
            $query->where('type', 0);
        })->sum('change_money');

        //所有的总支出
        $out = DB::table('account_log')->where(function ($query) use ($user_id, $startYear, $endYear) {
            $query->whereBetween('change_time', [$startYear, Carbon::now()->endOfYear()]);

            $query->where('user_id', $user_id);
            $query->where('type', 1);
        })->sum('change_money');

        $expire = $in - abs($out);
        $value = $expire > 0 ? $expire : 0;


        return $value;
    }


}
