<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    //抢购
    protected $table = 'flash_sale';
    public $timestamps = false;

    public static function getSceneArr()
    {
//        return ['00' => '0点场', '02' => '2点场', '04' => '4点场', '06' => '6点场', '08' => '8点场', '10' => '10点场', '12' => '12点场', '14' => '14点场', '16' => '16点场', '18' => '18点场', '20' => '20点场', '22' => '22点场'];
        $changci = get_config_by_name('flash_sale_changci');
        //08,10,12,14,16,18,
        $changciArr = explode(',',$changci);
        $data = [];
        foreach($changciArr as $k => $v){
            if($v != '00' && empty($v)) continue;
            $data[$v] = $v.'点场';
        }
        return $data;

    }

    public static function getStatusArr(){
        //抢购状态：1正常，0待审核，2审核拒绝，3关闭活动，4商品售馨
        return [0 => '待审核', 1 => '正常', 2 => '审核拒绝', 3 => '关闭活动', 4 => '商品售罄'];
    }

    public static function getIsRecArr()
    {
        return [0 => '否', 1 => '是'];
    }

    public function goods(){
        return $this->belongsTo('App\Models\Goods');
    }

}
