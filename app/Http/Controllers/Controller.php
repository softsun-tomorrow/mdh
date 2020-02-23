<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function success($data = null, $msg = '', $code = 1)
    {
        return response()->json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    public function error($msg = '', $data = null, $code = 0)
    {
        return response()->json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }
}
