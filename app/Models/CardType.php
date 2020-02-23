<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CardType extends Model
{
    //会员卡类型
    use SoftDeletes;
    protected $table = 'card_type';
    protected $appends = [
        'give_account'
    ];

    protected $hidden = ['deleted_at'];

    public static function getExpireArr(){
        return ['2592000' => '1个月','31536000' => '1年'];
    }

    public function getGiveAccountAttribute(){
        return $this->account - $this->pay_account;
    }



}
