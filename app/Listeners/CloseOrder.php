<?php

namespace App\Listeners;

use App\Events\AfterInsertOrder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CloseOrder
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
     * @param  AfterInsertOrder  $event
     * @return void
     */
    public function handle(AfterInsertOrder $event)
    {
        //
    }
}
