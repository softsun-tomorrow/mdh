<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreWithdrawals extends Model
{
    //
    protected $table = 'store_withdrawals';

    public $timestamps = false;

    public static function getStatusArr(){
        return [-1=>'审核失败',0=>'申请中',1=>'审核通过',2=>'已转款完成'];
    }

    public function store(){
        return $this->belongsTo('App\Models\Store');
    }

}
