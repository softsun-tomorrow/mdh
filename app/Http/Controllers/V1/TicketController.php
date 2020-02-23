<?php

namespace App\Http\Controllers\V1;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{
    //票
    public function __construct()
    {
        $this->middleware('refresh', ['except' => []]);
    }

    /**
     * 我的票列表
     * @param Request $request
     * status状态: 0=未使用, 1=已使用,2=已退货
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $param = $request->all();
        $list = Ticket::with(['store' => function ($query){
            $query->select('id','shop_name','logo');
        }])
            ->where(function ($query) use ($param) {
                $query->where('user_id', auth('api')->user()->id);
                if (isset($param['status'])) $query->where('status', $param['status']);
                if(isset($param['store_id'])) $query->where('store_id',$param['store_id']);
            })->orderBy('id', 'desc')
            ->offset($offset??0)
            ->limit($limit??10)
            ->get();
        return $this->success($list);
    }

}
