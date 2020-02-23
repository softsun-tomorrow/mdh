<?php
/**
 * 心情
 */
namespace App\Http\Controllers\V1;

use App\Logic\UserLogic;
use App\Models\Collect;
use App\Models\Config;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Tyfon;
use App\Models\TyfonCategory;
use App\Models\TyfonComment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TyfonController extends Controller
{
    //
    protected $guard = 'api';

    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['recommend','homePage','userLike','userCollect','userTyfon','index','detail','categoryTree','topCategory']]);
    }

    public function categoryTree(){
        $category = new TyfonCategory();
        $tree = $category->toTree();
        return $this->success($tree);
    }

    /**
     * 顶级分类
     */
    public function topCategory(){
        $list = TyfonCategory::where('pid',0)->get();
        return $this->success($list);
    }


    /*
     * 列表
     */
    public function index(Request $request)
    {
        $scene = $request->input('scene', 0);//场景：1=关注，0=发现
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Tyfon::where(function ($query) use ( $scene) {

            if ($scene) {
                //关注
                if(!auth('api')->check()) return $this->error('请先登录');
                $follows = DB::table('follow')->where('user_id', auth('api')->user()->id)->get();
                if ($follows) {
                    $tyfon_id_arr = [];
                    foreach ($follows as $follow) {
                        $tyfon_ids = DB::table('tyfon')
                            ->where('commentable_type', $follow->commentable_type)
                            ->where('commentable_id', $follow->commentable_id)
                            ->pluck('id');
                        $tyfon_id_arr = array_merge($tyfon_id_arr, $tyfon_ids->toArray());
                    }
                    $query->whereIn('id', $tyfon_id_arr);
                }
            }
        })
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = $this->getTyfonResult($list);

        return $this->success($data);
    }

    public function add(Request $request)
    {
        $param = $request->all();
        $param['commentable_id'] = auth('api')->user()->id;
        $param['created_at'] = date('Y-m-d H:i:s');
        $param['updated_at'] = date('Y-m-d H:i:s');

        $user = auth('api')->user();
        $baseConfig = get_config();
        if ($user['account']*100 - $baseConfig['base.tyfon_coin']*100 < 0) return $this->error('麦穗不足');
        $res = DB::table('tyfon')->insertGetId($param);

        if ($res) {
            //扣除用户麦穗
            User::accountLog(auth('api')->user()->id, '-' . $baseConfig['base.tyfon_coin'], '', '发布心情', 1, 3);
            $tyfon = Tyfon::find($res);

            list($width, $height) = getimagesize('./uploads/'.$tyfon->images[0]);
            DB::table('tyfon')->where('id',$tyfon->id)->update([
                'img_width' => $width,
                'img_height' => $height
            ]);
            return $this->success();
        } else {
            return $this->error();
        }
    }

    public function detail(Request $request)
    {
        $id = $request->input('id');
        $info = Tyfon::find($id);
        $info->commentable;
//        dd($info->commentable->toArray());
        $info['created_at_text'] = $info->created_at->diffForHumans();
        $author['name'] = $info->commentable->name;
        $author['avatar'] = $info->commentable->avatar;
        $author['id'] = $info->commentable->id;
        $info['author'] = $author;
        $info['goods_info'] = [];
        if($info->goods){
            $info['goods_info'] = [
                'id' => $info->goods->id,
                'name' =>  $info->goods->name,
                'shop_price' => $info->goods->shop_price
            ];
        }
        unset($info['commentable']);
        unset($info['goods']);

        //增加点击量
        DB::table('tyfon')->where('id',$id)->increment('click_num');

        //是否关注
        $isCollect = 0;
        $isLike = 0;
        $isFollow = 0;
        if(auth('api')->check()){
            $userLogic = new UserLogic();
            $userLogic->setUserId(auth('api')->user()->id);

            $isFollow = $userLogic->isFollow($info->commentable_id,$info->commentable_type);
            $isLike = $userLogic->isLike($info->id,'tyfon');
            $isCollect = $userLogic->isCollect($info->id,'tyfon');

        }
        $info['isFollow'] = $isFollow;
        $info['isLike'] = $isLike;
        $info['isCollect'] = $isCollect;
        return $this->success($info);

    }

    /**
     * 个人中心我的发布列表
     */
    public function myTyfon(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Tyfon::where(function ($query) {
            $query->where('commentable_type', 'user')->where('commentable_id', auth('api')->user()->id);
        })
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        return $this->success($list);
    }

    public function del(Request $request)
    {
        $id = $request->input('id');
        $res = Tyfon::where('id', $id)->delete();
        if ($res) {
            return $this->success();
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 心情关注用户
     */
    public function follow(Request $request)
    {
        $commentable_type = $request->input('commentable_type');
        $commentable_id = $request->input('commentable_id');

        $first = Follow::where('user_id',auth('api')->user()->id)->where('commentable_type', $commentable_type)->where('commentable_id', $commentable_id)->first();
        if ($first) {
            //取消关注
            $res = $first->delete();
        } else {
            //添加关注
            $res = Follow::create([
                'user_id' => auth('api')->user()->id,
                'commentable_id' => $commentable_id,
                'commentable_type' => $commentable_type,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        if ($res) {
            return $this->success();
        } else {
            return $this->error('关注失败');
        }

    }

    /**
     * 点赞
     */
    public function like(Request $request){
        //$commentable_type = 'tyfon';
        $commentable_id = $request->input('id');
        $commentable_type = $request->input('commentable_type', 'tyfon');

        $user_id = auth('api')->user()->id;
        $first = Like::where('user_id',$user_id)->where('commentable_type', $commentable_type)->where('commentable_id', $commentable_id)->first();

        if ($first) {
            //取消点赞
            $res = $first->delete();
            if($commentable_type == 'tyfon') DB::table('tyfon')->where('id',$commentable_id)->decrement('like_num');

        } else {
            //添加点赞
            $res = Like::create([
                'user_id' => $user_id,
                'commentable_id' => $commentable_id,
                'commentable_type' => $commentable_type,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if($commentable_type == 'tyfon') DB::table('tyfon')->where('id',$commentable_id)->increment('like_num');

        }

        if ($res) {
            return $this->success();
        } else {
            return $this->error('点赞失败');
        }

    }

    /**
     * 心情发布评论
     */
    public function addComment(Request $request){
        $content = $request->input('content');
        $tyfon_id = $request->input('id');
        if(empty($content)) return $this->error('评论内容不能为空');
        $res = DB::table('tyfon_comment')->insert([
            'user_id' => auth('api')->user()->id,
            'tyfon_id' => $tyfon_id,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($res) {
            DB::table('tyfon')->where('id',$tyfon_id)->increment('comment_num');
            return $this->success();
        } else {
            return $this->error('发布评论失败');
        }
    }

    /**
     * 获取某一条喇叭的评论
     */
    public function getCommentByTyfonId(Request $request){
        $tyfon_id = $request->input('id');
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);

        $list = TyfonComment::with(['user' =>function($query){
            $query->select('id','name','avatar');
        }])->where('tyfon_id',$tyfon_id)
            ->orderBy('id','desc')
            ->offset($offset??0)
            ->limit($limit??10)
            ->get();
        $userLogic = new UserLogic();
        foreach ($list as $k => $v){
            //是否点赞
            $isLike = 0;
            $likeCount = 0;
            if(auth('api')->check()){
                //类型:tyfon=心情, tyfon_comment=心情评论
                $userLogic->setUserId(auth('api')->user()->id);
                $isLike = $userLogic->isLike($v->id,'tyfon_comment');
                $likeCount = $userLogic->getLikeCount('tyfon_comment', $v->id);
            }
            $list[$k]['isLike'] = $isLike;
            $list[$k]['likeCount'] = $likeCount;
            $list[$k]['created_at_text'] = $v->created_at->diffForHumans();
        }
        return $this->success($list);
    }

    /**
     * 我的关注
     */
    public function myFollow(Request $request){
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);

        $list = Follow::with(['commentable' => function($query){
            $query->select('id','name','avatar');
            $query->withCount('tyfon');
            $query->withCount(['fans' => function($query){
                $query->where('commentable_type','user');
            }]);
        }])->where(function($query){
            $query->where('user_id',auth('api')->user()->id);
            $query->where('commentable_type','user');
        })
            ->offset($offset??0)
            ->limit($limit??10)
            ->get();

        return $this->success($list);
    }

    /**
     * 个人主页 -- 关注，粉丝，获赞
     */
    public function myFans(Request $request){
        $offset = $request->input('offset',0);
        $limit = $request->input('limit',10);
        $user_id = $request->input('user_id');
        $type = $request->input('type'); //0=关注，1=粉丝，2=获赞
        if($type ==1){
            $list = Follow::with(['user' => function($query){
                $query->select('id','name','avatar');
                $query->withCount('tyfon');
                $query->withCount(['fans' => function($query){
                    $query->where('commentable_type','user');
                }]);
            }])->where(function($query) use ($user_id){
                $query->where('commentable_id',$user_id);
                $query->where('commentable_type','user');
            })
                ->offset($offset??0)
                ->limit($limit??10)
                ->get();

            foreach ($list as $k => $v){
                $list[$k]['commentable'] = $v['user'];
            }
        }elseif ($type == 2){

            $tyfonIds = Tyfon::where('commentable_type', 'user')->where('commentable_id',$user_id)->pluck('id');

            $list = Like::with(['user' => function($query){
                $query->select('id','name','avatar');
                $query->withCount('tyfon');
                $query->withCount(['fans' => function($query){
                    $query->where('commentable_type','user');
                }]);
            }])->where(function($query) use ($tyfonIds){
                $query->where('commentable_type', 'tyfon');
                $query->whereIn('commentable_id', $tyfonIds);
            })
                ->offset($offset??0)
                ->limit($limit??10)
                ->get();

            foreach ($list as $k => $v){
                $list[$k]['commentable'] = $v['user'];
            }
        }else{
            $list = Follow::with(['commentable' => function($query){
                $query->select('id','name','avatar');
                $query->withCount('tyfon');
                $query->withCount(['fans' => function($query){
                    $query->where('commentable_type','user');
                }]);
            }])->where(function($query) use ($user_id){
                $query->where('user_id',$user_id);
                $query->where('commentable_type','user');
            })
                ->offset($offset??0)
                ->limit($limit??10)
                ->get();
        }

        return $this->success($list);
    }

    /**
     * 晒心情--个人主页
     */
    public function homePage(Request $request){
        $user_id = $request->input('user_id');
        $user = User::find($user_id);
        $userLogic = new UserLogic();
        $userLogic->setUser($user);
        $userLogic->setUserId($user_id);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'sign' => $user->sign,
            ],
            'count' => [
                'followCount' => $userLogic->getFollowCount(),
                'fansCount' => $userLogic->getFansCount(),
                'getLikeCount' => $userLogic->getTyfonLikeCount(),
                'tyfonCount' => $userLogic->getTyfonCount(),
                'collectCount' => $userLogic->getCollectCount(),
                //'likeCount' => $userLogic->getLikeCount(), //获赞数量
            ]
        ]);

    }

    /**
     * 个人主页--心情列表
     * @param Request $request
     */
    public function userTyfon(Request $request){
        $user_id = $request->input('user_id');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $list = Tyfon::where(function ($query) use ($user_id) {
            $query->where('commentable_type', 'user')->where('commentable_id', $user_id);
        })
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = $this->getTyfonResult($list);
        return $this->success($data);
    }

    /**
     * 个人主页--收藏列表
     * @param Request $request
     */
    public function userCollect(Request $request){
        $user_id = $request->input('user_id');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $tyfon_ids = Collect::where(function($query) use ($user_id){
            $query->where('user_id',$user_id);
            $query->where('commentable_type','tyfon');
        })->pluck('commentable_id')->toArray();

        $list = Tyfon::where(function ($query) use ($tyfon_ids) {
            $query->whereIn('id',$tyfon_ids);
        })
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = $this->getTyfonResult($list);
        return $this->success($data);
    }

    /**
     * 个人主页--喜欢列表
     * @param Request $request
     */
    public function userLike(Request $request){
        $user_id = $request->input('user_id');
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $tyfon_ids = Like::where(function($query) use ($user_id){
            $query->where('user_id',$user_id);
            $query->where('commentable_type','tyfon');
        })->pluck('commentable_id')->toArray();

        $list = Tyfon::where(function ($query) use ($tyfon_ids) {
            $query->whereIn('id',$tyfon_ids);
        })
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = $this->getTyfonResult($list);
        return $this->success($data);
    }

    private function getTyfonResult($list){
        $userLogic = new UserLogic();
        if(auth('api')->check()) $userLogic->setUserId(auth('api')->user()->id);

        foreach ($list as $k => $v) {
            $list[$k]['created_at_text'] = $v->created_at->diffForHumans();
            $author['name'] = $v->commentable->name;
            $author['avatar'] = $v->commentable->avatar;
            $author['id'] = $v->commentable->id;
            $list[$k]['author'] = $author;

            //是否关注
            $isCollect = 0;
            $isLike = 0;
            $isFollow = 0;
            if(auth('api')->check()){
                $userLogic->setUserId(auth('api')->user()->id);
                $isFollow = $userLogic->isFollow($v->commentable_id,$v->commentable_type);
                $isLike = $userLogic->isLike($v->id,'tyfon');
                $isCollect = $userLogic->isCollect($v->id,'tyfon');
            }
            $list[$k]['isFollow'] = $isFollow;
            $list[$k]['isLike'] = $isLike;
            $list[$k]['isCollect'] = $isCollect;
            unset($list[$k]['commentable']);
        }
        return $list;
    }

    /**
     * 增加分享次数
     */
    public function incrementShareNum(Request $request)
    {
        $id = $request->input('id');
        $tyfon = Tyfon::find($id);
        $tyfon->share_num += 1;
        $tyfon->save();
        return $this->success();
    }


    //心情推荐列表
    public function recommend()
    {
        $list = Tyfon::orderBy('id','desc')->limit(10)->get();
        $data = $this->getTyfonResult($list);
        return $this->success($data);
    }





}
