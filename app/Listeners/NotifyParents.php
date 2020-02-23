<?php

namespace App\Listeners;

use App\Events\AfterUpgrade;
use App\Models\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class NotifyParents
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AfterUpgrade  $event
     * @return void
     */
    public function handle(AfterUpgrade $event)
    {
        //
        $user = $event->user;
        //升级消息推送上两代
        if($user->first_leader){
            DB::table('message')->insert([
                'type' => 0,
                'user_id' => $user->first_leader,
                'category' => 1,
                'message' => '下属团队用户'. $user->name .'成为'. User::LEVEL[$user->level] .'代理',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        if($user->second_leader){
            DB::table('message')->insert([
                'type' => 0,
                'user_id' => $user->second_leader,
                'category' => 1,
                'message' => '下属团队用户'. $user->name .'成为'. User::LEVEL[$user->level] .'代理',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

    }
}
