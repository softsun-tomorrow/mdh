<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecKey extends Model
{
    //规格名称表
    protected $table = 'spec_key';
    public $timestamps = false;

    public function spec_value(){
        return $this->hasMany('App\Models\SpecValue','spec_key_id','id');
    }

}
