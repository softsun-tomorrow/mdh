<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    //文章
    protected $table = 'article';

    public  static  function getTypeArr(){
        return [0 => '特别声明', 1 => '隐私政策',2 => '用户协议'];
    }

}
