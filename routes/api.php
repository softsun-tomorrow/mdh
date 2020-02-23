<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api = app('Dingo\Api\Routing\Router');
$api->version('v1', ['namespace' => 'App\Http\Controllers\V1'], function ($api) {
    $api->post('register', 'AuthController@register');
    $api->any('login', 'AuthController@login');
    $api->post('logout', 'AuthController@logout');
    $api->post('refresh', 'AuthController@refresh');
    $api->post('me', 'AuthController@me');
    $api->any('test', 'AuthController@test');
    $api->any('test1', 'AuthController@test1');
    $api->post('identify', 'AuthController@identify');
    $api->post('changpwd', 'AuthController@changpwd');
    $api->post('forget', 'AuthController@forget');
    $api->post('paymentPassword', 'AuthController@paymentPassword');
    $api->post('profile', 'AuthController@profile');
    $api->post('myCenter', 'AuthController@myCenter');
    $api->post('poster', 'AuthController@poster');
    $api->post('checkOpenidBind', 'AuthController@checkOpenidBind');
    $api->post('bindMobile', 'AuthController@bindMobile');
    $api->post('money', 'AuthController@money');
    $api->any('moneyLog', 'AuthController@moneyLog');
    $api->any('rebateLog', 'AuthController@rebateLog');
    $api->any('accountLog', 'AuthController@accountLog');
    $api->any('accountTrans', 'AuthController@accountTrans');
    $api->any('addVillageOrder', 'AuthController@addVillageOrder');
    $api->any('village', 'AuthController@village');
    $api->any('inviteList', 'AuthController@inviteList');
    $api->any('inviteInfo', 'AuthController@inviteInfo');
    $api->any('children', 'AuthController@children');
    $api->any('child', 'AuthController@child');
    $api->any('achieve', 'AuthController@achieve');
    $api->any('capital', 'AuthController@capital');
    $api->any('addCaptialOrder', 'AuthController@addCaptialOrder');
    $api->any('capitalLog', 'AuthController@capitalLog');
    $api->any('delFollow', 'AuthController@delFollow');

    //公共接口
    $api->post('allArea', 'CommonController@allArea');
    $api->post('sms', 'CommonController@sms');
    $api->post('base64Upload', 'CommonController@base64Upload');
    $api->any('common/test', 'CommonController@test');
    $api->any('common/testpay', 'CommonController@testpay');
    $api->any('common/getConfigByName', 'CommonController@getConfigByName');

    //后台公共接口
    $api->any('backend/topCategory', 'BackendController@topCategory');
    $api->any('backend/getChildCategory', 'BackendController@getChildCategory');
    $api->any('backend/attribute/{storeid}', 'BackendController@attribute');
    $api->any('backend/attribute_values', 'BackendController@attribute_values');
    $api->any('backend/topTyfonCategory', 'BackendController@topTyfonCategory');
    $api->any('backend/getTyfonChildCategory', 'BackendController@getTyfonChildCategory');
    $api->any('backend/topArea', 'BackendController@topArea');
    $api->any('backend/getChildArea', 'BackendController@getChildArea');
    $api->any('backend/getUser', 'BackendController@getUser');
    $api->any('backend/confirmOrder', 'BackendController@confirmOrder');
    $api->any('backend/getStoreShipper', 'BackendController@getStoreShipper');
    $api->any('backend/confirmSend', 'BackendController@confirmSend');
    $api->any('backend/confirmGiveOrder', 'BackendController@confirmGiveOrder');
    $api->any('backend/confirmCode', 'BackendController@confirmCode');
    $api->any('backend/confirmGet', 'BackendController@confirmGet');
    $api->any('backend/storeClass', 'BackendController@storeClass');
    $api->any('backend/getOrder', 'BackendController@getOrder');
    $api->any('backend/getTyfon', 'BackendController@getTyfon');
    $api->any('backend/getChildStoreGoodsCategory', 'BackendController@getChildStoreGoodsCategory');
    $api->any('backend/storeClass', 'BackendController@storeClass');
    $api->any('backend/getGoods', 'BackendController@getGoods');
    $api->any('backend/spu', 'BackendController@spu');
    $api->any('backend/getGoodsSpecList', 'BackendController@getGoodsSpecList');


    //收货地址
    $api->post('address/add', 'AddressController@add');
    $api->post('address/edit', 'AddressController@edit');
    $api->post('address/index', 'AddressController@index');
    $api->post('address/del', 'AddressController@del');

    //大喇叭
    $api->post('tyfon/categoryTree', 'TyfonController@categoryTree');
    $api->post('tyfon/topCategory', 'TyfonController@topCategory');
    $api->post('tyfon/add', 'TyfonController@add');
    $api->post('tyfon/index', 'TyfonController@index');
    $api->post('tyfon/detail', 'TyfonController@detail');
    $api->post('tyfon/myTyfon', 'TyfonController@myTyfon');
    $api->post('tyfon/del', 'TyfonController@del');
    $api->post('tyfon/follow', 'TyfonController@follow');
    $api->post('tyfon/like', 'TyfonController@like');
    $api->post('tyfon/addComment', 'TyfonController@addComment');
    $api->post('tyfon/getCommentByTyfonId', 'TyfonController@getCommentByTyfonId');
    $api->post('tyfon/freeCount', 'TyfonController@freeCount');
    $api->post('tyfon/myFollow', 'TyfonController@myFollow');
    $api->post('tyfon/homePage', 'TyfonController@homePage');
    $api->post('tyfon/userTyfon', 'TyfonController@userTyfon');
    $api->post('tyfon/userCollect', 'TyfonController@userCollect');
    $api->post('tyfon/userLike', 'TyfonController@userLike');
    $api->post('tyfon/incrementShareNum', 'TyfonController@incrementShareNum');
    $api->post('tyfon/recommend', 'TyfonController@recommend');
    $api->post('tyfon/myFans', 'TyfonController@myFans');


    //分类
    $api->post('goods/categoryTree', 'GoodsController@categoryTree');
    $api->post('goods/topCategory', 'GoodsController@topCategory');
    $api->any('goods/storeRecGoods', 'GoodsController@storeRecGoods');

    //店铺
    $api->post('store/add', 'StoreController@add');
    $api->post('store/detail', 'StoreController@detail');
    $api->post('store/index', 'StoreController@index');
    $api->post('store/goodslist', 'StoreController@goodslist');
    $api->post('store/storeTyfon', 'StoreController@storeTyfon');
    $api->post('store/expose', 'StoreController@expose');
    $api->post('store/socore2coin', 'StoreController@socore2coin');
    $api->post('store/submitExchange', 'StoreController@submitExchange');
    $api->post('store/getMyCardId', 'StoreController@getMyCardId');
    $api->post('store/info', 'StoreController@info');
    $api->post('store/scan', 'StoreController@scan');
    $api->post('store/progress', 'StoreController@progress');
    $api->post('store/applyOrderPage', 'StoreController@applyOrderPage');
    $api->post('store/applyOrder', 'StoreController@applyOrder');
    $api->post('store/storeClass', 'StoreController@storeClass');
    $api->post('store/storeGoodsCategory', 'StoreController@storeGoodsCategory');
    $api->post('store/newGoods', 'StoreController@newGoods');
    $api->post('store/getStoreLicense', 'StoreController@getStoreLicense');


    //优惠券
    $api->any('coupon/index','CouponController@index');
    $api->any('coupon/getCoupon','CouponController@getCoupon');
    $api->any('coupon/myCoupon','CouponController@myCoupon');
    $api->any('coupon/storeCoupon','CouponController@storeCoupon');



    //商品
    $api->any('goods/index','GoodsController@index');
    $api->any('goods/detail','GoodsController@detail');
    $api->any('goods/getSpecPrice','GoodsController@getSpecPrice');
    $api->any('goods/buyNow','GoodsController@buyNow');
    $api->any('goods/getGoodsComment','GoodsController@getGoodsComment');
    $api->any('goods/history','GoodsController@history');
    $api->any('goods/search','GoodsController@search');
    $api->any('goods/promotionCategory','GoodsController@promotionCategory');
    $api->any('goods/promotion','GoodsController@promotion');
    $api->any('goods/coinGoods','GoodsController@coinGoods');
    $api->any('goods/agentGoods','GoodsController@agentGoods');
    $api->any('goods/recGoods','GoodsController@recGoods');
    $api->any('goods/hotSearch','GoodsController@hotSearch');
    $api->any('goods/shareCallback','GoodsController@shareCallback');
    $api->any('goods/agentImage','GoodsController@agentImage');
    $api->post('goods/friend', 'GoodsController@friend');
    $api->any('goods/delTestGoods', 'GoodsController@delTestGoods');
    $api->any('goods/friendImage', 'GoodsController@friendImage');


    //购物车
    $api->post('cart/add', 'CartController@add');
    $api->post('cart/index', 'CartController@index');
    $api->post('cart/changeNum', 'CartController@changeNum');
    $api->post('cart/changeSelect', 'CartController@changeSelect');
    $api->post('cart/del', 'CartController@del');
    $api->post('cart/computeCartPrice', 'CartController@computeCartPrice');
    $api->post('cart/confirmOrder', 'CartController@confirmOrder');
    $api->post('cart/addOrder', 'CartController@addOrder');

    //订单
    $api->any('alipay/index', 'AlipayController@index');
    $api->any('alipay/notify', 'AlipayController@notify');
    $api->any('alipay/web', 'AlipayController@web');
    $api->any('alipay/find', 'AlipayController@find');
    $api->any('alipay/transfer', 'AlipayController@transfer');
    $api->post('wechatpay/index', 'WechatpayController@index');
    $api->post('wechatpay/notify', 'WechatpayController@notify');
    $api->post('pay/selectPayType', 'PayController@selectPayType');
    $api->post('order/index', 'OrderController@index');
    $api->post('order/cancel', 'OrderController@cancel');
    $api->post('order/confirmOrder', 'OrderController@confirmOrder');//确认订单页面
    $api->post('order/getStoreShippingType', 'OrderController@getStoreShippingType');
    $api->post('order/index', 'OrderController@index');
    $api->post('order/cancel', 'OrderController@cancel');
    $api->post('order/getStoreCardCoupon', 'OrderController@getStoreCardCoupon');
    $api->any('order/queryEms', 'OrderController@queryEms');
    $api->any('order/del', 'OrderController@del');
    $api->any('order/return_goods', 'OrderController@return_goods');
    $api->any('order/returnGoodsInfo', 'OrderController@returnGoodsInfo');
    $api->any('order/returnGoodsList', 'OrderController@returnGoodsList');
    $api->any('order/orderConfirm', 'OrderController@orderConfirm'); //确认收货
    $api->any('order/goShop', 'OrderController@goShop');
    $api->any('order/userPay', 'OrderController@userPay');

    //订单评价
    $api->post('comment/add', 'CommentController@add');
    $api->post('comment/index', 'CommentController@index');
    $api->post('comment/detail', 'CommentController@detail');

    //收藏
    $api->post('collect/add', 'CollectController@add');
    $api->post('collect/goods', 'CollectController@goods');
    $api->post('collect/store', 'CollectController@store');
    $api->post('collect/tyfon', 'CollectController@tyfon');
    $api->post('collect/del', 'CollectController@del');

    //票
    $api->post('ticket/index', 'TicketController@index');

    //消息
    $api->post('message/index', 'MessageController@index');
    $api->post('message/category', 'MessageController@category');
    $api->post('message/detail', 'MessageController@detail');

    //签到
    $api->post('leesign/add', 'LeesignController@add');
    $api->post('leesign/index', 'LeesignController@index');
    $api->post('leesign/store', 'LeesignController@store');

    //首页
    $api->post('index/exchangeCoin', 'IndexController@exchangeCoin');
    $api->post('index/exchangeScore', 'IndexController@exchangeScore');
    $api->post('index/exchangeOrder', 'IndexController@exchangeOrder');
    $api->post('index/exchangeIndex', 'IndexController@exchangeIndex');
    $api->post('index/nav', 'IndexController@nav');
    $api->post('index/index', 'IndexController@index');
    $api->post('index/sideGoods', 'IndexController@sideGoods');
    $api->post('index/addFeedback', 'IndexController@addFeedback');
    $api->post('index/version', 'IndexController@version');
    $api->any('index/invite', 'IndexController@invite');
    $api->any('index/banner', 'IndexController@banner');
    $api->any('index/appDownload', 'IndexController@appDownload');


    //拼团
    $api->any('team_activity/index', 'TeamActivityController@index');
    $api->any('team_activity/addTeamFound', 'TeamActivityController@addTeamFound');
    $api->any('team_activity/teamFound', 'TeamActivityController@teamFound');
    $api->any('team_activity/teamFollow', 'TeamActivityController@teamFollow');
    $api->any('team_activity/canFollowTeams', 'TeamActivityController@canFollowTeams');
    $api->any('team_activity/autoRefund', 'TeamActivityController@autoRefund');

    //限时抢购
    $api->any('flash_sale/index', 'FlashSaleController@index');
    $api->any('flash_sale/scene', 'FlashSaleController@scene');
    $api->any('flash_sale/recommend', 'FlashSaleController@recommend');
    $api->any('flash_sale/buy', 'FlashSaleController@buy');

    //抽奖
    $api->any('lottery/index', 'LotteryController@index');
    $api->any('lottery/addLotteryFollow', 'LotteryController@addLotteryFollow');
    $api->any('lottery/winer', 'LotteryController@winer');
    $api->any('lottery/myLottery', 'LotteryController@myLottery');
    $api->any('lottery/incLotteryChance', 'LotteryController@incLotteryChance');

    //提现
    $api->any('withdrawals/add', 'WithdrawalsController@add');
    $api->any('withdrawals/info', 'WithdrawalsController@info');
    $api->any('withdrawals/withdrawalsLog', 'WithdrawalsController@withdrawalsLog');




});
