<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VillageOrder extends Model
{
    //
    protected $table = 'village_order';
    public $timestamps = false;

    public static function doAfterPay($order_sn)
    {
        //升级小区长
        $order = DB::table('village_order')->where('order_sn',$order_sn)->first();
        $user = User::find($order->user_id);
        $user->village_level = $user->level;
        $user->community_code = User::buildCommunityCode($user->id);
        $user->save();

    }
}
