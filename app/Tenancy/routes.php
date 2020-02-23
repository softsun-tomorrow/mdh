<?php
use App\Http\Middleware\CheckStoreEnabled;

Route::get('/', 'HomeController@index');
//ajax接口
Route::get('api/topCategory', 'ApiController@topCategory');
Route::any('api/goods', 'ApiController@goods');
Route::any('api/editGoods', 'ApiController@editGoods');
Route::any('api/cardType', 'ApiController@cardType');
Route::any('api/getTopStoreGoodsCategory', 'ApiController@getTopStoreGoodsCategory');
Route::any('api/team', 'ApiController@team');
Route::any('api/cat1', 'ApiController@cat1');
Route::any('api/cat2', 'ApiController@cat2');
Route::any('api/exportOrder', 'ApiController@exportOrder');

//自定义接口
Route::any('goods_spec/addspec', 'GoodsSpecController@addspec');
Route::resource('store',StoreController::class)->middleware(CheckStoreEnabled::class);
Route::resource('tyfon',TyfonController::class)->middleware(CheckStoreEnabled::class);
Route::resource('tyfon_comment',TyfonCommentController::class)->middleware(CheckStoreEnabled::class);

Route::resource('spec_key',SpecKeyController::class)->middleware(CheckStoreEnabled::class);
Route::resource('goods',GoodsController::class)->middleware(CheckStoreEnabled::class);
Route::resource('goods_spec',GoodsSpecController::class)->middleware(CheckStoreEnabled::class);
Route::resource('coupon',CouponController::class)->middleware(CheckStoreEnabled::class);
Route::resource('card_type',CardTypeController::class)->middleware(CheckStoreEnabled::class);
Route::resource('card',CardController::class)->middleware(CheckStoreEnabled::class);
Route::resource('shipping_type',ShippingTypeController::class)->middleware(CheckStoreEnabled::class);
Route::resource('store_shipper',StoreShipperController::class)->middleware(CheckStoreEnabled::class);
Route::resource('order',OrderController::class)->middleware(CheckStoreEnabled::class);
Route::resource('order_goods',OrderGoodsController::class)->middleware(CheckStoreEnabled::class);
Route::resource('comment',CommentController::class)->middleware(CheckStoreEnabled::class);
Route::resource('return_goods',ReturnGoodsController::class)->middleware(CheckStoreEnabled::class);
Route::resource('card_account_log',CardAccountLogController::class)->middleware(CheckStoreEnabled::class);
Route::resource('card_score_log',CardScoreLogController::class)->middleware(CheckStoreEnabled::class);
Route::resource('store_account_log',StoreAccountLogController::class)->middleware(CheckStoreEnabled::class);
Route::resource('store_withdrawals',StoreWithdrawalsController::class)->middleware(CheckStoreEnabled::class);
Route::resource('exchange',ExchangeController::class)->middleware(CheckStoreEnabled::class);
Route::resource('exchange_order',ExchangeOrderController::class)->middleware(CheckStoreEnabled::class);
Route::resource('flash_sale',FlashSaleController::class)->middleware(CheckStoreEnabled::class);
Route::resource('team_activity',TeamActivityController::class)->middleware(CheckStoreEnabled::class);
Route::resource('store_goods_category',StoreGoodsCategoryController::class)->middleware(CheckStoreEnabled::class);
Route::resource('lottery',LotteryController::class)->middleware(CheckStoreEnabled::class);
Route::resource('order_statis',OrderStatisController::class)->middleware(CheckStoreEnabled::class);
Route::resource('team_found',TeamFoundController::class)->middleware(CheckStoreEnabled::class);
Route::resource('lottery_follow',LotteryFollowController::class)->middleware(CheckStoreEnabled::class);





