<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CardOrder extends Model
{
    //
    protected $table = 'card_order';

    public $timestamps = false;

    public static function doAfterPay($order_sn)
    {
        $order = self::where('order_sn', $order_sn)->first();
        $card_type_id = $order->card_type_id;
        $mobile = $order->mobile;
        $cardType = CardType::find($card_type_id);

        $card_no = Card::getCardNo($cardType->store_id);
        $end_time = date('Y-m-d H:i:s', (time() + $cardType->expire));
        $rate_end_time = date('Y-m-d H:i:s', (time() + $cardType->rate_expire));
        $user = User::find($order->user_id);
        DB::table('card')->insert([
            'user_id' => $user->id,
            'store_id' => $cardType->store_id,
            'type' => 1,
            'card_no' => $card_no,
            'card_type_id' => $card_type_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'end_time' => $end_time,
            'account' => $cardType->account,
            'mobile' => $mobile,
            'user_num' => $user->num,
            'rate' => $cardType->rate,
            'rate_end_time' => $rate_end_time,
        ]);

        \App\Models\ExpenseLog::add(0, 1, $cardType->account, 0, '', '充值会员卡余额');
        Store::storeAccountLog(0,4,$order->store_id,$cardType->account,'会员卡开卡',$order_sn); //给店铺增加余额


    }
}
