<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    //
    protected $table = 'invite';
    public $timestamps = false;

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

}
