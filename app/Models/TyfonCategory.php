<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;

class TyfonCategory extends Model
{
    //
    use ModelTree, AdminBuilder;

    protected $table = 'tyfon_category';
    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setParentColumn('pid');
        $this->setOrderColumn('weigh');
        $this->setTitleColumn('name');
    }

}
