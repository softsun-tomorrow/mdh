<?php

namespace App\Http\Middleware;

use Closure;
use Encore\Admin\Facades\Admin;

class CheckStoreEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Admin::user()->enabled == 0) {
            return redirect('/tenancy');
        }
        return $next($request);
    }
}
