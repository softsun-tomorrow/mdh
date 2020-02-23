<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    //取货码（票）
    //状态: 0=未使用, 1=已使用,2=已退货

    protected $table = 'ticket';
    public $timestamps = false;
    protected $appends = [
        'status_text'
    ];

    public function store(){
        return $this->belongsTo('App\Models\Store');
    }

    public static function getStatusArr(){
        return [0 => '待取货', 1 => '已使用', 2 => '已退货'];
    }

    public function getStatusTextAttribute(){
        return $this->getStatusArr()[$this->status];
    }



}
