<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TyfonComment extends Model
{
    //
    protected $table = 'tyfon_comment';

    public function user(){
        return $this->belongsTo('App\\Models\\User');
    }



}
