<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreShipper extends Model
{
    //店铺快递
    protected $table = 'store_shipper';
    public $timestamps = false;

    public static function getStatusArr(){
        return [0 => '关闭', 1 => '开启'];
    }


}
