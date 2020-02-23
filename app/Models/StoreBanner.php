<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBanner extends Model
{
    //
    protected $table = 'store_banner';
    protected $fillable = ['name','image','goods_id','weigh'];
    public $timestamps = false;

    public function store(){
        return $this->belongsTo('App\\Models\\Store','store_id');
    }

}
