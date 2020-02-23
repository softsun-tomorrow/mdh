<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StoreGoodsCategory extends Model
{
    //
    use ModelTree, AdminBuilder;

    protected $table = 'store_goods_category';
    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setParentColumn('pid');
        $this->setOrderColumn('weigh');
        $this->setTitleColumn('name');
    }



}
