<?php

namespace App\Http\Controllers\V1;

use App\Logic\FlashSaleLogic;
use App\Logic\FlashSaleOrderLogic;
use App\Models\Address;
use App\Models\FlashSale;
use App\Models\Goods;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FlashSaleController extends Controller
{
    //限时抢购
    public function __construct()
    {
        $this->middleware('refresh', ['except' => ['scene','index','recommend']]);
    }

    public function scene(){
        $changci = get_config_by_name('flash_sale_changci');
        //08,10,12,14,16,18,
        $changciArr = explode(',',$changci);
        $data = [];
        foreach($changciArr as $k => $v){
            if($v != '00' && empty($v)) continue;
            $data[$k]['id'] = $v;
            $data[$k]['value'] = $v.':00';
        }
        sort($data);
        return $this->success($data);
    }

    public function index(Request $request){
        $param = $request->all();

        $list = FlashSale::with(['goods' => function($query){
            $query->select('id', 'store_id', 'name','type', 'cover', 'share_rebate','self_rebate', 'shop_price', 'sale_nums','collect_nums', 'description', 'cat1', 'cat2', 'prom_type', 'prom_id','cover_width','cover_height','shipper_fee','exchange_integral','cover_height','cover_width');
        }])->where(function($query) use ($param){
//            $query->whereIn('status',[1,4]);
            $query->where('status', 1);
            $query->whereHas('goods',function($query){
                $query->where('type',0);
                $query->where('prom_type', 1);
            });
            if(isset($param['scene'])) $query->where('scene',$param['scene']);
        })
            ->orderBy('weigh','desc')
            ->offset($param['offset']??0)
            ->limit($param['limit']??10)
            ->select('id','title','description','price','buy_num','goods_id','scene','status')
            ->get();

        if($list->count()){
            foreach ($list as $k => $v){
                $list[$k]['goods']['goods_images'] = $v->goods->goods_images;
            }

        }

        return $this->success($list);
    }

    /**
     * 限时抢购推荐
     */
    public function recommend(Request $request){
        $param = $request->all();

        $list = FlashSale::with(['goods' => function($query){
            $query->select('id','cover','shop_price','self_rebate','share_rebate','shipper_fee','exchange_integral','prom_type','store_id');
        }])->where(function($query) use ($param){
//            $query->whereIn('status',[1,4]);
            $query->where('status', 1);
            $query->where('is_recommend',1);
            $query->whereHas('goods',function($query){
                $query->where('type',0);
                $query->where('prom_type', 1);
            });
            if(isset($param['scene'])) $query->where('scene',$param['scene']);
        })
            ->orderBy('weigh','desc')
            ->select('id','title','description','price','buy_num','goods_id','scene','status')
            ->get();


        return $this->success($list);
    }

    /**
     * 立即抢购
     */
    public function buy(Request $request)
    {
        $goods_id = $request->input('goods_id');
        $spec_key = $request->input('spec_key');
        $address_id = $request->input('address_id');
        $goods_num = $request->input('goods_num',1);

        $address = Address::find($address_id);
        $goods = Goods::find($goods_id);
        if ($goods->prom_type != 1) return $this->error('该商品抢购活动不存在或者已下架');
        $flashSaleLogic = new FlashSaleLogic($goods);
        $flashSale = $flashSaleLogic->getPromModel();
        $goods = $flashSaleLogic->getGoodsInfo();

        $flashSaleOrderLogic = new FlashSaleOrderLogic();
        $flashSaleOrderLogic->setFlashSale($flashSale);
        $flashSaleOrderLogic->setGoodsBuyNum($goods_num);
        $flashSaleOrderLogic->setUserId(auth('api')->user()->id);
        $flashSaleOrderLogic->setGoods($goods);
        $flashSaleOrderLogic->setSpecKey($spec_key);
        $flashSaleOrderLogic->setAddress($address);
        $res = $flashSaleOrderLogic->add();
        if (!$res) {
            return $this->error($flashSaleOrderLogic->getError());
        } else {
            return $this->success($res);
        }
    }

}
