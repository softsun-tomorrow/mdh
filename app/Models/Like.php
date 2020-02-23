<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    //
    //
    protected $table = 'like';
    public $timestamps = false;
    protected $fillable = ['user_id','commentable_type','commentable_id','created_at'];

    /**
     * 获得拥有此信息的模型。
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
    }
}
