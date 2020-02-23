<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * App\Area
 *
 * @property int $id
 * @property int $parent_id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Area whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Area extends Model
{
    use ModelTree, AdminBuilder;

    //
    protected $table = 'china_area';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setParentColumn('parent_id');
        $this->setOrderColumn('code');
        $this->setTitleColumn('name');
    }

    public static function getNameByCode($code){
         return self::where('code',$code)->value('name');
    }

    public static function getSelectOptions(){
        $cache = Cache::get('areaSeclectOptions');
        if(empty($cache)){
            $area = self::selectOptions();
            Cache::forever('areaSeclectOptions',$area);
            $cache = $area;
        }

        return $cache;
    }

}
