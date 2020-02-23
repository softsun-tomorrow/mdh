<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsImages extends Model
{
    //
    protected $table = 'goods_images';
    public $timestamps = false;
    protected $fillable = ['image_url','weigh'];


    public function goods(){
        return $this->belongsTo('App\Models\Goods','goods_id','id');
    }


}
