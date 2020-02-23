<?php

namespace App\Http\Controllers\V1;


use App\Models\Address;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{

    protected $guard = 'api';

    public function __construct()
    {
        $this->middleware('refresh', ['except' => []]);
    }

    public function guard()
    {
        return Auth::guard($this->guard);
    }

    public function add(Request $request){
        $payload = $request->all();
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $payload['user_id'] = auth('api')->user()->id;
        $res = DB::table('address')->insertGetId($payload);
        if($res){
            if($payload['is_default'] == 1){
                Address::setDefault($res,$this->guard()->user()->id);
            }
            return $this->success();
        }else{
            return $this->error('添加失败');
        }
    }

    public function edit(Request $request){
        $payload = $request->all();

        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $payload['user_id'] =auth('api')->user()->id;
        $res = DB::table('address')->where('id',$payload['id'])->update($payload);

        if($res){
            if($payload['is_default'] == 1){
                Address::setDefault($payload['id'],$this->guard()->user()->id);
            }
            return $this->success();
        }else{
            return $this->error('修改失败');
        }
    }

    public function index(Request $request){
//        echo auth('api')->user()->id;exit;
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);
        $list = Address::where('user_id',auth('api')->user()->id)
            ->offset($offset)
            ->limit($limit)
            ->orderBy('is_default','desc')
            ->orderBy('id','desc')
            ->get();


        return $this->success($list);

    }

    public function del(Request $request){
        $id = $request->input('id');
        $res = Address::where('id',$id)->delete();
        if($res){
            return $this->success();
        }else{
            return $this->error('删除失败');
        }
    }

    /**
     * 设置默认地址
     * @param Request $request
     */
    public function setDefault(Request $request){
        $id = $request->input('id');
        $is_default = $request->input('is_default');

        if($is_default){
            Address::setDefault($id,$this->guard()->user()->id);
        }else{
            DB::table('address')->where('id',$id)->update(['is_default' => $is_default]);
        }
        return $this->success();
    }




}
