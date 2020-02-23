<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    //
    protected $table = 'feedback';
    protected $fillable = ['user_id','content','imgs'];

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function getImgsAttribute($value){
        return explode(',',$value);
    }



}
