<?php

namespace App\Models;

use App\Logic\GoodsPromFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * 商品模型
 * type 商品类型：0=普通商品, 1=升级赚钱, 2=麦穗抵扣, 3=活动专区
 * prom_type 活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
 * Class Goods
 * @package App\Models
 */
class Goods extends Model
{
    use SoftDeletes;
    //
    protected $table = 'goods';
    protected $hidden = [];
    protected $appends = [
        'total_rebate',
        'actual_price',
        'is_free_shipping',
        'exchange_integral_rate',
        'prom_info',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function($model) {
            //商品加入了购物车或是产生了订单，禁止删除
            $cartCount = DB::table('cart')->where('goods_id',$model->id)->count();
            $orderGoodsCount = DB::table('order_goods')->where('goods_id',$model->id)->count();

            if($cartCount || $orderGoodsCount) {
                throw new \Exception('此商品已被加入购物车或产生订单, 不能删除!');
                return false;
            }
        });
    }

    public function getTagsTextAttribute()
    {
//        return explode(',', $value);
        $tagIds = explode(',', $this->tags);
        $tag = DB::table('tag')->whereIn('id', $tagIds)->pluck('name');
        return $tag->toArray();
    }

    public function setTagsAttribute($value)
    {
        $value ? $this->attributes['tags'] = implode(',', $value) : $this->attributes['tags'] = '';
    }

    public function goods_images()
    {
        return $this->hasMany('App\\Models\\GoodsImages', 'goods_id');
    }

    public function goods_spec()
    {
        return $this->hasMany('App\Models\GoodsSpec', 'goods_id', 'id');
    }

    public static function getStatusArr()
    {
        //审核状态:0待审核1审核通过2审核失败
        return [0 => '待审核', 1 => '审核通过', 2 => '审核失败'];
    }

    public static function getTypeArr()
    {
        //商品类型：0=普通商品, 1=升级赚钱, 2=麦穗抵扣
        return [0 => '普通商品', 1 => '升级赚钱', 2 => '麦穗抵扣'];
    }

    public static function getPromTypeArr(){
        //prom_type 活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
        return [0 => '无', 1 => '限时抢购', 2 => '拼团', 3 => '抽奖'];
    }

    public static function getCoinGoodsRateArr()
    {
        $rate = Config::getConfigValueByName('coin_goods_rate');
        $rateArr = explode(',', $rate);
        $arr = [];
        foreach ($rateArr as $k => $v) {
            $arr[$v] = $v;
        }
        return $arr;
    }

    public function getUseIntegralTextAttribute()
    {
        return price_format($this->shop_price * $this->use_integral / 100);
    }


    public function getFullCatAttribute()
    {
        $goods = self::find($this->id);
        return Category::find($goods->cat1)->name . ' > ' . Category::find($goods->cat2)->name;
    }

    public function getSpecListAttribute($value)
    {
        $arr = json_decode($value, true);
        $newarr = [];
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $newarr[] = [
                    'spec_name' => $k,
                    'spec_value' => $v
                ];
            }
        }

        return $newarr;
    }

    public function getShopNameAttribute()
    {
        return Store::find($this->store_id)->shop_name;
    }

    public function getShopPhoneAttribute()
    {
        return Store::find($this->store_id)->contacts_mobile;
    }


    public static function getIsOnSaleText()
    {
        return [0 => '下架', 1 => '上架'];//上架状态:0=下架,1=上架
    }

    public static function getIsHotText()
    {
        return [0 => '否', 1 => '是'];//上架状态:0=下架,1=上架
    }

    public static function getStatusText()
    {
        return [0 => '待审核', 1 => '审核通过', 2 => '审核不通过']; //审核状态:0待审核1审核通过2审核失败
    }

    public static function getSpecPrice($goods_id, $key)
    {
        $key = str2json($key);
        $goods_price = DB::table('goods_spec')->where('goods_id', $goods_id)->where('spec_keys', $key)->value('goods_price');
        return $goods_price;
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function goods_spu()
    {
        return $this->hasMany(GoodsSpu::class);
    }

    public function setContentAttribute($Content)
    {
        if (is_array($Content)) {
            $this->attributes['content'] = json_encode($Content);
        }
    }

    public function getContentAttribute($Content)
    {
        return json_decode($Content, true);
    }

    //分享赚+自购赚
    public function getTotalRebateAttribute()
    {
        if (isset($this->self_rebate) && isset($this->share_rebate)) return price_format($this->self_rebate + $this->share_rebate);
    }

    //商品实际价格 = 本店价 - （分享赚+自购赚）
    public function getActualPriceAttribute()
    {
        if (isset($this->self_rebate) && isset($this->share_rebate) && isset($this->shop_price)) return price_format($this->shop_price - ($this->self_rebate + $this->share_rebate));
    }

    //是否包邮
    public function getIsFreeShippingAttribute()
    {
        if (isset($this->shipper_fee)) {
            if (!empty((float)$this->shipper_fee)) return 0;
        }
        return 1;
    }

    //麦穗抵扣比例
    public function getExchangeIntegralRateAttribute()
    {
        $coin2rmb = get_config_by_name('coin2rmb');
        $coinPrice = price_format($this->exchange_integral / $coin2rmb);
        if ($this->exchange_integral??0) return price_format($coinPrice / $this->shop_price * 100);
        return 0;
    }

    //麦穗抵扣比例
    public function getExchangeIntegralPriceAttribute()
    {
        $coin2rmb = get_config_by_name('coin2rmb');
        $coinPrice = price_format($this->exchange_integral / $coin2rmb);
        if ($this->exchange_integral??0) return price_format($coinPrice);
        return 0;
    }

    //商品活动信息
    public function getPromInfoAttribute()
    {
        if($this->prom_type && $this->prom_id){
            $goodsPromFactory = new GoodsPromFactory();
            if ($goodsPromFactory->checkPromType($this->prom_type)) {
                $goodsPromLogic = $goodsPromFactory->makeModule($this);
                $goodsPromModel = $goodsPromLogic->getPromModel();

                //活动类型：0默认，1=限时抢购，2=拼团，3=抽奖
                if ($this->prom_type == 1) {
                    $prom_tag = '特惠';
                } elseif($this->prom_type == 2) {
                    $prom_tag = optional($goodsPromModel)->needer .'人团';
                }elseif($this->prom_type == 3){
                    $prom_tag = '抽奖';
                }
                $goodsPromModel['prom_tag'] = $prom_tag;
                return $goodsPromModel;
            }
        }

    }

    public function cat1()
    {
        return $this->belongsTo(Category::class,'cat1','id');
    }

    public function cat2()
    {
        return $this->belongsTo(Category::class,'cat2','id');
    }

    /**
     * 获取商品一级分类列表-select-option
     * @return User[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getCat1SelectOptions($store_id)
    {
//        $options = DB::table('users')->select('id','name as text')->get();
        //商家拥有的商品品类

        $catIds = Store::where('id', $store_id)->value('cat_ids');
        $options = DB::table('category')->whereIn('id', $catIds)->select('id','name as text')->get();
        $selectOption = [];
        foreach ($options as $option){
            $selectOption[$option->id] = $option->text;
        }

        return $selectOption;
    }

}
