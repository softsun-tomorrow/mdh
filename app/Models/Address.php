<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * App\Address
 *
 * @property int $id
 * @property string $name 姓名
 * @property string $mobile 手机号码
 * @property int $province_id 省
 * @property int $city_id 城市
 * @property int $district_id 区/县
 * @property string $detail 详细地址
 * @property int $is_default 是否设为默认
 * @property int $user_id 用户
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereDistrictId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereProvinceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereUserId($value)
 * @mixin \Eloquent
 */
class Address extends Model
{
    //
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'address';
    protected $fillable = [

    ];




    protected $hidden = [

    ];

    public static function setDefault($id,$user_id){
        //先都设置为否，再单独设置的为是
        DB::table('address')->where('user_id',$user_id)->update(['is_default' => 0]);

        DB::table('address')->where('id',$id)->update(['is_default' => 1]);
    }

    public function getProvinceIdTextAttribute(){
        return Area::find($this->province_id)->name;
    }

    public function getCityIdTextAttribute(){
        return Area::find($this->city_id)->name;
    }

    public function getDistrictIdTextAttribute(){
        return Area::find($this->district_id)->name;
    }
}
