<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    //消息
    const CATEGORY = [0 => '麦达汇官方助手', 1 => '粉丝升级通知'];//消息分类：0麦达汇官方助手，1粉丝升级通知
    protected $table = 'message';
    //消息类型：0=个体消息，1=全体消息
    //消息分类：0系统消息，1物流通知，2优惠促销，3商品提醒，4我的资产，5商城好店

    protected $appends = [
        'type_text'
    ];

    public static function getTypeArr(){
        return [0 => '个体消息', 1 => '全体消息'];
    }

    public function getTypeTextAttribute(){
        return self::getTypeArr()[$this->type];
    }

}
