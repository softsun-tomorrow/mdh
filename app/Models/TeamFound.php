<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TeamFound extends Model
{
    //开团模型
    //status拼团状态0:待开团(表示已下单但是未支付)1:已经开团(团长已支付)2:拼团成功,3拼团失败
    protected $table = 'team_found';
    public $timestamps = false;
    const STATUS = ['0' => '待开团', 1 => '已开团', 2 => '拼团成功', 3 => '拼团失败'];
    const BONUS_STATUS = [0 => '未领取', 1 => '已领取']; //团长佣金领取状态：0无1领取
    protected $appends = [
        'status_text'
    ];
    public function __construct()
    {
        parent::__construct();
        $team_found_num = DB::table('team_found')->where('found_end_time', '<', time())->where('status', 1)->count();
        if ($team_found_num > 0) {
            DB::table('team_found')->where('found_end_time', '<', date('Y-m-d H:i:s'))->where('status', 1)->update(['status' => 3]);
        }
    }

    public function team(){
        return $this->belongsTo('App\Models\TeamActivity','team_id');
    }

    public function team_follow()
    {
        return $this->hasMany('App\Models\TeamFollow','found_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getStatusTextAttribute()
    {
        if(isset($this->status)) return self::STATUS[$this->status];
    }
    





}
