<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Collect extends Model
{
    //收藏
    protected $table = 'collect';
    public $timestamps = false;
    protected $fillable = ['user_id','commentable_type','commentable_id','created_at'];

    public function commentable()
    {
        return $this->morphTo();
    }


    public function goods(){
        return $this->belongsTo('App\Models\Goods','commentable_id');
    }

    public function store(){
        return $this->belongsTo('App\Models\Store','commentable_id');
    }

    /**
     * @param int $scene 场景：1=添加收藏，0=取消收藏
     */
    public static function doAfterChange($scene,$commentable_type,$commentable_id){
        if($scene){
            DB::table($commentable_type)->where('id',$commentable_id)->increment('collect_nums');
        }else{
            DB::table($commentable_type)->where('id',$commentable_id)->decrement('collect_nums');

        }
    }

    /**
     * 是否收藏
     */
    public static function isCollect($user_id,$commentable_id,$commentable_type){
        $count = self::where([
            'user_id' => $user_id,
            'commentable_id' => $commentable_id,
            'commentable_type' => $commentable_type
        ])->count();
        $count ? $isCollect = 1 : $isCollect = 0;
        return $isCollect;

    }

}
