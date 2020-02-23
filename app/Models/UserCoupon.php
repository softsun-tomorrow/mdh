<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCoupon extends Model
{
    //
    protected $table = 'user_coupon';
    public $timestamps = false;

    public function coupon(){
        return $this->belongsTo('App\\Models\\Coupon','coupon_id');
    }

}
