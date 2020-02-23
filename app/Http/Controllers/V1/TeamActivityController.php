<?php

namespace App\Http\Controllers\V1;

use App\Logic\TeamActivityLogic;
use App\Logic\TeamFoundLogic;
use App\Logic\TeamOrderLogic;
use App\Models\Address;
use App\Models\Goods;
use App\Models\Order;
use App\Models\TeamActivity;
use App\Models\TeamFound;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamActivityController extends Controller
{
    //拼团
    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['index','autoRefund']]);
    }

    /**
     * 定时任务 -- 拼团有效期结束，未拼成的单退款
     */
//    public function autoRefund()
//    {
//        $teamOrderLogic = new TeamOrderLogic();
//        $teamOrderLogic->autoCheck();
//    }

    /**
     * 列表
     */
    public function index(Request $request){
        $param = $request->all();

        $list = TeamActivity::with(['goods' => function($query){
            $query->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width');

        }])->where(function($query) use ($param){
//            $query->whereIn('status',[1,4]);
            $query->where('status', 1);
            $query->where('is_recommend',1);

            $query->whereHas('goods',function($query){
                $query->where('type',0);
                $query->where('prom_type', 2);
            });
        })
            ->orderBy('weigh','desc')
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id','title','description','price','needer','sales_sum','goods_id','sales_sum','status')
            ->get();
//            ->simplePaginate(10);

        return $this->success($list);
    }

    /**
     * 发起拼团
     */
    public function addTeamFound(Request $request)
    {
        $goods_id = $request->input('goods_id');
        $goods_num = $request->input('goods_num',1);
        $found_id = $request->input('found_id',0);
        $spec_key = $request->input('spec_key', '');
        $address_id = $request->input('address_id');
        $address = Address::find($address_id);
        $goods = Goods::find($goods_id);

        Log::info('addTeamFoundParam:'. $spec_key);
        if($goods->prom_type != 2) return $this->error('该商品拼团活动不存在或者已下架');

        $teamActivityLogic = new TeamActivityLogic($goods);
        $team = $teamActivityLogic->getPromModel();
        $goods = $teamActivityLogic->getGoodsInfo();

        $teamOrderLogic = new TeamOrderLogic();
        if($found_id) {
            //参团
            $teamFound = TeamFound::find($found_id);
            $teamOrderLogic->setTeamFound($teamFound);

            $teamFoundLogic = new TeamFoundLogic();
            $teamFoundLogic->setTeam($team);
            $teamFoundLogic->setTeamFound($teamFound);
            $teamFoundLogic->setUserId(auth('api')->user()->id);
            $teamFoundIsCanFollow = $teamFoundLogic->TeamFoundIsCanFollow();
            if(!$teamFoundIsCanFollow) return $this->error($teamFoundLogic->getError());
        }
        $teamOrderLogic->setTeam($team);
        $teamOrderLogic->setUserId(auth('api')->user()->id);
        $teamOrderLogic->setGoods($goods);
        $teamOrderLogic->setGoodsBuyNum($goods_num);
        $teamOrderLogic->setSpecKey($spec_key);
        $teamOrderLogic->setAddress($address);
        $res = $teamOrderLogic->add();
        if(!$res){
            return $this->error($teamOrderLogic->getError());
        }else{
            return $this->success($res);
        }
    }

    /**
     * 发起拼团支付后
     */
    public function teamFound(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = Order::find($orderId);
        $teamFound = TeamFound::where(['order_id' => $order->id])->first();

        $teamFound->team;
        return $this->success($teamFound);
    }

    /**
     * 参与拼团
     */
    public function teamFollow(Request $request)
    {
        $foundId = $request->input('found_id');
        $teamFound = TeamFound::find($foundId);
//        $team = TeamActivity::find($teamFound->team_id);
//        $team->goods;
        $teamFound->team;
        $teamFound->team->goods;
        $teamFound->team['is_free_shipping'] = $teamFound->team->goods['is_free_shipping'];
        unset( $teamFound->team->goods);
        //参团人员
        $teamFound->team_follow;

        //拼主所选规格
        $specName = DB::table('order_goods')->where('order_id',$teamFound->order_id)->value('spec_key_name');
        $teamFound['team_found_spec'] = $specName;
        return $this->success($teamFound);
    }
    
    /**
     * 可插队拼单的团
     */
    public function canFollowTeams(Request $request)
    {
        $teamId = $request->input('team_id');
        $list = TeamFound::where(function($query) use ($teamId){
            $query->where('team_id',$teamId);
            $query->where('status',1);
        })->limit(5)->get();
        return $this->success($list);
    }








}
