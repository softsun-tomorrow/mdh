<?php

namespace App\Logic;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TeamFoundLogic extends Model
{
    protected $team; //拼团模型
    protected $teamFound; //团长模型
    protected $error;
    protected $user_id;

    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    protected function setError($error){
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setTeam($team){
        $this->team = $team;
    }

    public function setTeamFound($teamFound){
        $this->teamFound = $teamFound;
    }

    /**
     * 检查该单是否可以拼
     * @return array
     */
    public function TeamFoundIsCanFollow()
    {
        if($this->teamFound['team_id'] != $this->team['id']){
            $this->setError('该拼单数据不存在或已失效');
            return false;
        }
        if($this->teamFound['join'] >= $this->teamFound['need']){
            $this->setError('该拼单已成功结束');
            return false;
        }
//        if(time() - $this->teamFound['found_time'] > $this->team['time_limit']){
        if($this->teamFound['found_end_time'] < date('Y-m-d H:i:s')){
            $this->setError('该拼单已过期');
            return false;
        }

        if($this->user_id == $this->teamFound['user_id']){
            $this->setError('自己开的团不能自己参团');
            return false;
        }

        if($this->teamFound['status'] != 1){
            $this->setError('不是待拼团状态');
            return false;
        }

        //一个拼单只能参与一次
        $hasTeamFollow = DB::table('team_follow')->where([
            'found_id' => $this->teamFound['id'],
            'follow_user_id' => $this->user_id,
        ])->count();
        if($hasTeamFollow){
            $this->setError('同一个拼单只能参与一次');
            return false;
        }

        return true;
    }


}