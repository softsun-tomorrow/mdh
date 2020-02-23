<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    //
    protected $table = 'version';

    public static function getSceneArr(){
        return [0 => '安卓', 1 => 'ios'];
    }

}
