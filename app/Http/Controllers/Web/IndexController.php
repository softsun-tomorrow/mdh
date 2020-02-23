<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    //
    /**
     * 商品详情
     */
    public function goodsContent(Request $request){
        $id = $request->input('id');

        $content = DB::table('goods')->where('id',$id)->value('content');

        return view('index',['content' => $content]);
    }


    /**
     * 文章详情
     */
    public function article(Request $request){
        $scene = $request->input('scene',0);
        $id = $request->input('id',0);
        $title = $request->input('title','');
        $info = DB::table('article')->where(function($query) use ($scene,$title,$id){
            if($scene){
                $query->where('title',$title);
            }else{
                $query->where('id',$id);
            }
        })->orderBy('id','desc')->first();

        if(!$info){
            echo '文章不存在';exit;
        }
        return view('article',['title' => $info->title, 'content' => $info->content]);
    }

    public function download(){

        return view('download');
    }

    public function share(Request $request){
        $user_id = $request->user_id;
        $user = User::find($user_id);
        return view('share',['referral_code' => $user->referral_code]);
    }

    public function register(){
        return view('register');
    }


}
