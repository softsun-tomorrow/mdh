<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'cart';
    protected $fillable = ['user_id','store_id','goods_id','goods_name','goods_price','goods_num','spec_key','spec_key_name','selected'];

    protected $appends = ['goods_cover','price'];

//    public function getSpecKeyNameAttribute($value){
//        $arr = json_decode($value,true);
//        $str = '';
//        if($arr){
//            foreach($arr as $k => $v){
//                $str .= $k . ':' . $v . ' ';
//            }
//        }
//
//        return $str;
//    }

    public function getPriceAttribute(){
        return price_format($this->goods_price * $this->goods_num);
    }

    public function getGoodsCoverAttribute(){
        $goods = Goods::find($this->goods_id);
        if($goods){
            return Goods::find($this->goods_id)->cover;
        }else{
            return '';
        }

    }




}
