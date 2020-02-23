<?php

namespace App\Logic;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommentLogic extends Model
{
    //评论

    protected $order_goods;

    public function setOrderGoods($orderGoods){
        $this->order_goods = $orderGoods;
    }

    /**
     * 发布评论后调用
     */
    public function doAfterComment(){

        //更新订单商品表状态

        DB::table('order_goods')->where('id',$this->order_goods->id)->update(['is_comment' => 1]);
        DB::table('goods')->where('id',$this->order_goods->goods_id)->increment('comment_nums');

        // 查看这个订单是否全部已经评论,如果全部评论了 修改整个订单评论状态
        $comment_count = DB::table('order_goods')->where(['order_id' => $this->order_goods->order_id,'is_comment' => 0])->count();
        if(!$comment_count){
            DB::table('order')->where('id',$this->order_goods->order_id)->update([
                'order_status' => 4,
                'is_comment' => 1
            ]);
        }
    }

    /**
     * 获取商品的几条评价
     */
    public function getCommentByGoodsId($goods_id,$limit=3){
        $list = Comment::with(['user' => function ($query) {
            $query->select('id', 'name', 'avatar');
        }])->where(function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id);
            $query->where('is_show', 1);
        })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        return $list;
    }
}