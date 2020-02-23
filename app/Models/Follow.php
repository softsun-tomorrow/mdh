<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    //
    protected $table = 'follow';

    public $timestamps = false;

    protected $fillable = ['user_id','commentable_type','commentable_id','created_at'];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

}
