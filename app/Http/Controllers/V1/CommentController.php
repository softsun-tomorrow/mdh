<?php

namespace App\Http\Controllers\V1;

use App\Logic\CommentLogic;
use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderGoods;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    //订单评论

    public function __construct()
    {
        $this->middleware('refresh', ['except' => []]);
    }

    /**
     * 发布评论
     */
    public function add(Request $request)
    {
        $param = $request->all();
        $orderGoods = OrderGoods::find($param['order_goods_id']);
        if(empty($param['content'])) return $this->error('评论内容不能为空');

        //检查订单是否已确认收货
        $order = Order::find($orderGoods->order_id);
        if($order->order_status != 2) return $this->error('订单状态不是已收货状态');

        //检查是否已评论过
        if($orderGoods->is_comment) return $this->error('您已评论过该商品');

        $res = DB::table('comment')->insert([
            'order_goods_id' => $orderGoods->id,
            'goods_id' => $orderGoods->goods_id,
            'order_id' => $orderGoods->order_id,
            'store_id' => $orderGoods->store_id,
            'user_id' => auth('api')->user()->id,
            'content' => $param['content'],
            'images' => $param['images']??'',
            'spec_key_name' => $orderGoods->spec_key_name,
            'goods_rank' => $param['goods_rank'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if ($res) {
            $commentLogic = new CommentLogic();
            $commentLogic->setOrderGoods($orderGoods);
            $commentLogic->doAfterComment();
            return $this->success();
        } else {
            return $this->error('发布评论失败');
        }
    }

    public function index(Request $request)
    {
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $store_id = $request->input('store_id',0);

        $list = Comment::with([
            'order_goods' => function ($query) {
                $query->select('id','goods_name', 'goods_num', 'goods_price');
            },
            'goods' => function($query) use ($store_id){
                $query->select('id','cover');
            }
        ])
            ->where(function($query) use ($store_id){
                $query->where('user_id',auth('api')->user()->id);
                $query->where('store_id',$store_id);
                $query->where('is_show',1);
            })
            ->orderBy('created_at', 'desc')
            ->offset($offset??0)
            ->limit($limit??10)
            ->get();
        return $this->success($list);
    }

    public function detail(Request $request)
    {
        $id = $request->input('id');
        $info = Comment::find($id);
        $info->goods->cover;
        $info->order_goods;

        $data = [
            'id' => $info->id,
            'goods_id' => $info->goods_id,
            'content' => $info->content,
            'images' => $info->images,
            'price_rank' => $info->price_rank,
            'service_rank' => $info->service_rank,
            'goods_rank' => $info->goods_rank,
            'goods_name' => $info->goods->name ,
            'goods_cover' => $info->goods->cover,
            'goods_price' => $info->order_goods->goods_price,
            'created_at' => $info->created_at,

        ];
        return $this->success($data);
    }


}
