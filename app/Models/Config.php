<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    //
    protected $table = 'admin_config';
    public static function getConfigValue(){
        $list = self::where('name','like','base%')->get();

        $arr = [];
        foreach($list as $k => $v){
            $arr[$v['name']] = $v['value'];
        }
        return $arr;
    }

    public static function getConfigValueByName($name){
        $value = self::where('name','base.'.$name)->value('value');
        return $value;
    }
}
