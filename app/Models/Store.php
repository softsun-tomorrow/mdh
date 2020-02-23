<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class Store extends Model
{
    //
    protected $table = 'store';
    public $timestamps = false;

    protected  $fillable = [
        'shop_name' => '',
        'license_front' => '',
        'contacts_name' => '',
        'contacts_mobile' => '',
        'created_at' => '',
        'updated_at' => '',
        'logo' => '',
        'address' => '',
        'province_id' => '',
        'city_id' => '',
        'district_id' => '',
        'weigh' => '',
        'is_rec' => '',
        'status' => '',
        'handle_time' => '',
        'reason' => '',
        'license_number' => '',
        'customer_service' => '',
        'account' => '',
        'frozen_account' => '',
        'is_frozen' => '',
        'map_city_id' => '',
        'lat' => '',
        'lng' => '',
        'idcard_front' => '',
        'idcard_back' => '',
        'idcard_num' => '',
        'city' => '',
        'city_name' => '',
        'notice' => '',
        'collect_nums' => '',
        'pedding_account' => '',
        'store_start_time' => '',
        'store_end_time' => '',
        'deliver_region' => '',
        'deleted_at' => '',
        'email' => '',
        'type' => '',
        'send_type' => '',
        'bank_num' => '',
        'bank_front' => '',
        'bank_back' => '',
        'brand_image' => '',
        'trademark_image' => '',
        'food_image' => '',
        'door_image' => '',
        'other_image' => '',
        'user_id' => '',
        'store_class_id' => '',
        'pay_status' => '',
        'cat_ids' => '',
    ];
    protected $appends = [
        'send_type_text',
        'store_sale_nums',
        'service_rank'
    ];

    public static function getIsRecArr()
    {
        return [0 => '否', 1 => '是'];
    }

    public static function getStatusArr(){
        return [0 => '关闭',1=>'通过',2=>'未审核',3 => '审核不通过'];
    }

    public static function getTypeArr(){
        return [0 => '个人店铺', 1 => '企业店铺'];
    }

    public static function getSendTypeArr(){
        //发货类型：0=国内商家自己发货，1=国内商品麦达汇发货，2=进口商品国内发货，3=进口商品国外发货，4=到店消费店铺
        return [0 => '国内商家自己发货', 1 => '国内商品麦达汇发货', 2 => '进口商品国内发货', 3 => '进口商品国外发货', 4 => '到店消费/自提'];
    }

    public function getCatIdsAttribute($value)
    {
        return explode(',', $value);
    }

    public function setCatIdsAttribute($value)
    {
        $this->attributes['cat_ids'] = implode(',', $value);
    }

    public function getSendTypeTextAttribute()
    {
        if(isset($this->send_type)) return self::getSendTypeArr()[$this->send_type];
    }


    public function getNameAttribute()
    {
        return $this->shop_name;
    }

    public function store_banner(){
        return $this->hasMany('App\\Models\\StoreBanner','store_id');
    }

    /**
     * @param int $type 类型:0=收入，1=支出
     * @param int $source 来源：0=订单结算,1=发布大喇叭,2=提现,3=扫码支付收款，4=会员卡开卡，5=会员卡充值
     */
    public static function storeAccountLog($type,$source,$store_id, $change_money, $desc, $order_sn = '', $order_id = 0)
    {
        $oldAccount = DB::table('store')->where('id', $store_id)->value('account');
        DB::transaction(function () use ($type,$source, $store_id, $change_money, $desc, $order_sn, $order_id, $oldAccount) {
            $store = DB::table('store')->where('id', $store_id)->first();
            if ($type == 1) {
                //提现
                //将提现金额，存到冻结金额,为负数
                DB::table('store')->where('id', $store_id)->update([
                    'frozen_account' => price_format($store->frozen_account - $change_money),
                    'account' => price_format($store->account+$change_money)
                ]);
            } else {
                //订单结算
                DB::table('store')->where('id', $store_id)->update([
                    'account' => price_format($store->account+$change_money)
                ]);
            }
            $after_money = floatval($oldAccount) + floatval($change_money);
            $after_money = price_format($after_money);
            DB::table('store_account_log')->insert([
                'store_id' => $store_id,
                'before_money' => $oldAccount,
                'change_money' => $change_money,
                'after_money' => $after_money,
                'created_at' => date('Y-m-d H:i:s'),
                'desc' => $desc,
                'order_sn' => $order_sn,
                'order_id' => $order_id,
                'type' => $type,
                'source' => $source
            ]);
        });

    }

    public function store_class(){
        return $this->belongsTo('App\Models\StoreClass');
    }

    public function getStatusTextAttribute(){
        $statusArr = self::getStatusArr();
        return $statusArr[$this->status];
    }

    //获取店铺销量
    public function getStoreSaleNumsAttribute(){
        //['store_id',$this->id, 'pay_status' => 1]
        return Order::where('store_id',$this->id)->where('pay_status',1)->count();
    }

    public function getServiceRankAttribute(){
        $goodsRank = Comment::where(function($query){
            $query->where('store_id',$this->id);
        })->avg('service_rank');

        return price_format($goodsRank,1);
    }

    public function goods()
    {
        return $this->hasMany(Goods::class);
    }




}
