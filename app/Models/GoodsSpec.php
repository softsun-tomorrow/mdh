<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoodsSpec extends Model
{
    //商品规格模型
    protected $table = 'goods_spec';

    public $timestamps = false;

    public function goods(){
        return $this->belongsTo('App\Models\Goods','goods_id','id');
    }

    public static function getStatusArr()
    {
        //状态：0=隐藏，1=显示
        return [0 => '禁用',1 => '使用'];
    }


    /**
     * 保存商品规格
     * @param $goodsId
     * @param $specvalues 规格值id数组
     */
    public static function saveSpec($goodsId,$specvalues){

        $spec_list = [];
        $specIds = [];
        if(!empty($specvalues)){
            foreach($specvalues as $k => $v){
                $spec = SpecValue::find($v);
//                $spec_list[$spec->spec_name][] = $spec->spec_value;
                $spec_list[$spec->spec_name][] = [
                    'id' => $spec->id,
                    'value' => $spec->spec_value
                ];
                $specIds[$spec->spec_key_id][] = $spec->id;
            }

            //写入商品表规格列表
            DB::table('goods')->where('id',$goodsId)->update(['spec_list' => json_encode($spec_list)]);

        }

        $specIds = array_values($specIds);

        //写入sku表
        self::addGoodsSpecs($goodsId,$specIds);
    }

    //修改商品规格列表
    public static function editGoodsSpecList($goodsId,$specvalues){
        $spec_list = [];
        if(!empty($specvalues)){
            foreach($specvalues as $k => $v){
                $spec = SpecValue::find($v);
                $spec_list[$spec->spec_name][] = [
                    'id' => $spec->id,
                    'value' => $spec->spec_value
                ];
                $specIds[$spec->spec_key_id][] = $spec->id;
            }

            //写入商品表规格列表
            DB::table('goods')->where('id',$goodsId)->update(['spec_list' => json_encode($spec_list)]);

        }




    }

    /**
     * 写入sku表
     * @param $goodsId
     * @param $specIds
     */
    public static function addGoodsSpecs($goodsId,$specIds){
        $specsArr = self::CartesianProduct($specIds);
        $specIdArr = self::Cartesian($specIds);
        $goods = Goods::find($goodsId);
//        Log::info('specIds:'.json_encode($specIds));
//        Log::info('specsArr:'.json_encode($specsArr));

        $array = array();
        if(!empty($specIdArr) && !empty($specsArr)){
            foreach ($specIdArr as $k => $v){
                $array[$k]['id'] = $v;
            }

            foreach($specsArr as $k => $v){
                $array[$k]['text'] = $v;
            }
        }

//        Log::info('生成sku',$array);
        if(!empty($array)){
            //先删除此商品的sku
            DB::table('goods_spec')->where(['goods_id' => $goodsId])->delete();
            foreach($array as $k => $v){

                DB::table('goods_spec')->insert([
                    'goods_id' => $goodsId,
                    'goods_specs' => $v['text'],
                    'spec_keys' => json_encode($v['id']),
                    'goods_stock' => $goods->store_count,
                    'goods_price' => $goods->shop_price,
                    'goods_stock' => 100
                ]);
            }
        }

    }


    /**
     * 计算多个集合的笛卡尔积
     * @param Array $sets 集合数组 （spec_value表id）[[91,93],[94,95]]
     * @return Array
     */
    public static function CartesianProduct($sets){
//        Log::info('CartesianProduct result:' , $sets);
        /** 笛卡尔积处理sku **/
        // 保存结果
        $result = array();

        // 循环遍历集合数据
        for($i=0,$count=count($sets); $i<$count-1; $i++){
            // 初始化
            if($i==0){
                $result = $sets[$i];
            }
//            Log::info('CartesianProduct result:' , $result);
            // 保存临时数据
            $tmp = array();
            // 结果与下一个集合计算笛卡尔积
            foreach($result as $res){
                foreach($sets[$i+1] as $set){
//                    $tmp[] = $res. '_'.$set;
//                    $key_tmp[] = $res. '_'.$set;
                    $spec = SpecValue::find($res);
                    $spec2 = SpecValue::find($set);
//                    $goods_specs = [$spec->spec_name => $spec->spec_value,$spec2->spec_name => $spec2->spec_value];
                    $goods_specs = $spec->spec_name . ' : ' . $spec->spec_value . '  '. $spec2->spec_name .' : '. $spec2->spec_value;
                    $tmp[] = $goods_specs;
                }
            }
            // 将笛卡尔积写入结果
            $result = $tmp;
        }
        return $result;
        //返回最终笛卡尔积
//        echo "<pre>";
//        print_r($result);
////        var_dump(json_encode($result));exit;
//
//        echo "</pre>";
        /**
        [{
        "颜色": "白",
        "尺码": "XL"
        }, {
        "颜色": "白",
        "尺码": "XXL"
        }, {
        "颜色": "蓝",
        "尺码": "XL"
        }, {
        "颜色": "蓝",
        "尺码": "XXL"
        }]
         **/

    }


    /**
     * 计算多个集合的笛卡尔积
     * @param Array $sets 集合数组 （spec_value表id）
     * @return Array
     */
    public static function Cartesian($sets){

        /** 笛卡尔积处理sku **/
        // 保存结果
        $result = array();

        // 循环遍历集合数据
        for($i=0,$count=count($sets); $i<$count-1; $i++){

            // 初始化
            if($i==0){
                $result = $sets[$i];
            }

            // 保存临时数据
            $tmp = array();

            // 结果与下一个集合计算笛卡尔积
            foreach($result as $res){
                foreach($sets[$i+1] as $set){
//                    $tmp[] = $res. ','.$set;
                    $tmp[] = [$res,$set];
                }
            }

            // 将笛卡尔积写入结果
            $result = $tmp;

        }

        return $result;


    }


    public static function getSpecKey($key){
        if(!empty(trim($key))){
            //判断是否是json
            if(is_not_json($key)){
                $key = explode(',',$key);
                sort($key);
                foreach ($key as $k => $v){
                    $key[$k] = (int)$v;

                }
                $key = json_encode($key);
            }

        }
        return $key;
    }



        /**
     * @param $goodsId
     * @param string $key json数组
     * @return string
     */
    public static function getSpecValueByKey($goodsId,$key){
        if(is_not_json($key)){
            $key = strKey2json($key);
        }
        $speckey = self::where(['goods_id' => $goodsId, 'spec_keys' => $key])->first();
        return $speckey ? $speckey : '';

    }

    public static function getGoodsSpecsBySpecKeys($spec_key)
    {
        if($spec_key){
            //判断是否是json
            if(is_not_json($spec_key)){
                $specKey = explode(',',$spec_key);
            }else{
                $specKey = json_decode($spec_key, true);
            }

            $spec = SpecValue::find($specKey[0]);
            $spec2 = SpecValue::find($specKey[1]);
//                    $goods_specs = [$spec->spec_name => $spec->spec_value,$spec2->spec_name => $spec2->spec_value];
            $goods_specs = $spec->spec_name . ' : ' . $spec->spec_value . '  '. $spec2->spec_name .' : '. $spec2->spec_value;
            return $goods_specs;
        }else{
            return '';
        }

    }


    public static function getGoodsSpecsBySpecKeyJson($specKeys)
    {
        $specKey = json_decode($specKeys, true);
        $spec = SpecValue::find($specKey[0]);
        $spec2 = SpecValue::find($specKey[1]);
//                    $goods_specs = [$spec->spec_name => $spec->spec_value,$spec2->spec_name => $spec2->spec_value];
        $goods_specs = $spec->spec_name . ' : ' . $spec->spec_value . '  '. $spec2->spec_name .' : '. $spec2->spec_value;
        return $goods_specs;
    }


}
