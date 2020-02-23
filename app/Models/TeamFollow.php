<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 参团
 * Class TeamFollow
 * @package App\Models
 */
class TeamFollow extends Model
{

    //参团状态0:待拼单(表示已下单但是未支付)1拼单成功(已支付)2成团成功3成团失败
    protected $table = 'team_follow';
    public $timestamps = false;
    const STATUS = [0 => '待拼单', 1 => '拼单成功', 2 => '成团成功', 3 => '成团失败'];

    public $appends = ['status_text'];

    public function team_found()
    {
        return $this->belongsTo('App\Models\TeamFound','found_id');
    }

    public function getStatusTextAttribute()
    {
        return isset($this->status) ? self::STATUS[$this->status] : '';
    }

    public function getOrderSnAttribute()
    {
        return isset($this->order_id) ? optional(Order::find($this->order_id))->order_sn : '';
    }

}
