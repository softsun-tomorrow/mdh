<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExpenseLog extends Model
{
    //
    protected $table = 'expense_log';

    /**
     * @param int $type //类型：0=收入,1=支出
     * @param int $source //来源:0=商品订单,1=会员卡购买，2=会员卡余额充值,3=用户提现,4=商户提现
     * @param $money
     * @param string $expenseable_id
     * @param string $order_sn
     * @param string $remark
     */
    public static function add($type,$source,$money,$expenseable_id = 0,$order_sn = '',$remark = ''){
        $param = [
            'type' => $type,
            'source' => $source,
            'money' => $money,
            'created_at' => date('Y-m-d H:i:s'),
            'expenseable_id' => $expenseable_id,
            'order_sn' => $order_sn,
            'remark' => $remark
        ];
        DB::table('expense_log')->insert($param);
    }

    public static function getTypeArr(){
        return [0=>'收入',1=>'支出'];
    }

    public static function getSourceArr(){
        //来源:0=商品订单,1=会员卡购买，2=会员卡余额充值,3=用户提现,4=商户提现
        return [0=>'商品订单',1=>'会员卡购买',2=>'用户余额充值',3 => '用户提现', 4 => '商户提现'];

    }


}
