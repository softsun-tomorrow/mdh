<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tyfon extends Model
{
    //
    protected $table = 'tyfon';

    /**
     * 获得拥有此信息的模型。
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    public function goods(){
        return $this->belongsTo('App\Models\Goods');
    }

    public function setImagesAttribute($pictures)
    {
        if (is_array($pictures)) {
            $this->attributes['images'] = implode(',',$pictures);
        }
    }

    public function getImagesAttribute($images)
    {
        return explode(',',$images);

    }

    /**
     * 处理图片
     */
//    public function getImagesTextAttribute(){
//        return explode(',',$this->images);
//    }

    /**
     * 分类
     * @return string
     */
    public function getFullCatAttribute(){
        $goods = self::find($this->id);
        return TyfonCategory::find($goods->cat1)->name . ' > ' . TyfonCategory::find($goods->cat2)->name . ' > ' . TyfonCategory::find($goods->cat3)->name;
    }

    /**
     * 地区
     */
    public function getFullAreaAttribute(){
        $goods = self::find($this->id);
        return Area::find($goods->province_id)->name . ' > ' . Area::find($goods->city_id)->name . ' > ' . Area::find($goods->district_id)->name;

    }
    
}
