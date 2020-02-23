<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRebateLog extends Model
{
    //用户收益记录
    protected $table = 'user_rebate_log';
    public $timestamps = false;
    //类型:0=收入,1=支出
    const TYPE = [0 => '收入', 1 => '支出'];
    //来源:0=销售佣金,1=管理津贴,2=购物收益，3=分享收益，4=收益结算, 5=团队营业额分红
    const SOURCE = [0 => '销售佣金', 1 => '管理津贴', 2 => '购物收益', 3 => '分享收益', 4 => '收益结算', 5=>'团队营业额分红'];
    //是否结算：0=否，1=是，2=已失效
    const IS_SETTLE = [0 => '否', 1 => '是', 2 => '已失效'];




}
