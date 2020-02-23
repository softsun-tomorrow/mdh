<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecValue extends Model
{
    //规格值表
    protected $table = 'spec_value';
    public $timestamps = false;
    protected $fillable = ['spec_key_id','spec_value','weigh'];

    public function spec_key(){
        return $this->belongsTo('App\Models\SpecKey','spec_key_id');
    }

    public function getSpecNameAttribute(){
        return SpecKey::find($this->spec_key_id)->spec_name;
    }

}
