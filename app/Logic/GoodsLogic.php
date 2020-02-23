<?php

namespace App\Logic;


use App\Models\Goods;
use App\Models\GoodsSpec;
use App\Models\SpecValue;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GoodsLogic extends Model
{
    protected $goods;
    protected $goods_id;
    protected $user_id;

    public function setUserId($userId){
        $this->user_id = $userId;
    }

    public function setGoodsId($goodsId){
        $this->goods_id = $goodsId;
    }

    public function setGoods($goods){
        $this->goods = $goods;
    }

    public function getGoodsByGoodsIds(array $goodsIds)
    {
        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($goodsIds) {
            $query->whereIn('id',$goodsIds);
            $query->where(['is_on_sale' => 1, 'status' => 1]);
        })
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width')
            ->get();

        foreach($list as $k => $goods){
            $list[$k]['prom_info'] = $goods->prom_info;
        }

        return $list;
    }

    /**
     * 获取推荐商品
     *
     */
    public function getRecGoods($keywords = '',$offset=0,$limit = 10,$category = 0)
    {
//        DB::connection()->enableQueryLog();  // 开启QueryLog
        $list = Goods::with(['store' => function ($query) {
            $query->select('id', 'shop_name', 'logo');
        }])->where(function ($query) use ($keywords,$category) {
            $query->where(['is_on_sale' => 1, 'status' => 1]);
            $query->where('is_rec',1);
            $query->where('type',0);
            $query->whereIn('prom_type', [0, 1, 2]);
            if($keywords) $query->where('name','like', '%'. $keywords .'%');
            if($category) $query->where('cat1',$category);
        })
            ->select('id', 'store_id', 'name','type', 'cover', 'share_rebate', 'self_rebate', 'shop_price', 'sale_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','exchange_integral','cover_width','cover_height','collect_nums')
            ->offset($offset)
            ->limit($limit)
            ->get();
//                dump(DB::getQueryLog());

        foreach ($list as $k => $goods) {
            $list[$k]['prom_info'] = $goods->prom_info;
        }
        return $list;
    }

    /**
     * 是否分享过
     */
    public function userIsShare()
    {
        //用户是否分享
        $isShare = DB::table('goods')->where(['user_id' => $this->user_id, 'goods_id' => $this->goods_id])->count();
        return $isShare ? 1 : 0;
    }


    public function getCartesianKey($specvalues){
        $specIds = [];
        if(!empty($specvalues)){
            foreach($specvalues as $k => $v){
                $spec = SpecValue::find($v);
                $specIds[$spec->spec_key_id][] = $spec->id;
            }
        }

        $specIds = array_values($specIds);
        return $specIds;
    }

    public function getSkuList($specIds){
        $specsArr = GoodsSpec::CartesianProduct($specIds);
        $specIdArr = GoodsSpec::Cartesian($specIds);

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
        return $array;
    }



}