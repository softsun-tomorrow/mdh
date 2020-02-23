<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Sms
 *
 * @property int $id
 * @property string|null $code
 * @property string|null $ip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property int|null $status
 * @property string|null $phone
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Sms whereStatus($value)
 * @mixin \Eloquent
 */
class Sms extends Model
{
    protected $error;
    protected function setError($error){
        $this->error = $error;
    }

    public function getError(){
        return $this->error;
    }

    /**
     * @param $phone
     * @param $code
     * @return bool
     */
    public function check($phone ,$code){
        $sms = DB::table('sms')->where(['phone' => $phone, 'code' => $code])->first();

        if(isset($sms) && !empty($sms)){
            $now = time();

            if($now - $sms->created_at > 5*60){
                $this->setError('验证码已失效');
                return false;
            }else{
                return true;
            }
        }else{

            $this->setError('短信验证码错误');
            return false;
        }
    }

}
