<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingType extends Model
{
    //配送方式
    protected $table = 'shipping_type';
    public $timestamps = false;

    public static function getStatusArr(){
        return [0=> '关闭', 1 => '开启'];
    }

    public static function getValueByCode($code,$store_id){
        return self::where(function($query) use ($code,$store_id){
            $query->where('store_id',$store_id);
            $query->where('shipping_code',$code);
        })->value('shipping_name');
    }

}
