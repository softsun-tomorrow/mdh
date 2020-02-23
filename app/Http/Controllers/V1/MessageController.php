<?php

namespace App\Http\Controllers\V1;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    //消息
    public function __construct()
    {
        $this->middleware('refresh', ['except' => []]);
    }

    public function index(Request $request)
    {
        $param = $request->all();
        $list = Message::where(function ($query) use ($param) {
            if ($param['category']) {
                $query->where('user_id', auth('api')->user()->id);
                $query->where('category', $param['category']);
            } else {
                $query->where('user_id', 0);
            }
        })->orderBy('created_at', 'desc')
            ->offset($param['offset'] ?? 0)
            ->limit($param['limit'] ?? 10)
            ->get();
        return $this->success($list);
    }

    public function category()
    {
        $category = Message::CATEGORY;
        $data = [];
        foreach ($category as $k => $v) {
            $data[$k]['id'] = $k;
            $data[$k]['name'] = $v;
            $data[$k]['lasted'] = DB::table('message')->where(function($query) use ($k,$v){
                $query->where('category',$k);
                if($k == 1) $query->where('user_id',auth('api')->user()->id);
            })->orderBy('id','desc')->first();
        }


        return $this->success($data);
    }

    public function detail(Request $request)
    {
        $id = $request->input('id');
        $info = Message::find($id);
        return $this->success($info);
    }


}
