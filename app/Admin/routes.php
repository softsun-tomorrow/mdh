<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();


Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    //ajax
//    $router->any('api/topCategory', 'ApiController@topCategory');
    $router->any('api/goods', 'ApiController@goods');
    $router->any('api/promotion_category', 'ApiController@promotion_category');
    $router->any('api/store_class', 'ApiController@store_class');
    $router->any('api/user', 'ApiController@user');
    $router->any('api/team', 'ApiController@team');
    $router->any('api/lottery', 'ApiController@lottery');


    $router->get('/', 'HomeController@index');
    $router->resource('category', CategoryController::class);
    $router->resource('tyfon_category', TyfonCategoryController::class);
    $router->resource('tyfon', TyfonController::class);
    $router->resource('tyfon_comment', TyfonCommentController::class);
    $router->resource('store', StoreController::class);
    $router->resource('coupon', CouponController::class);
    $router->resource('goods', GoodsController::class);
    $router->resource('ems', EmsController::class);
    $router->resource('order', OrderController::class);
    $router->resource('service', ServiceController::class);
    $router->resource('message', MessageController::class);
    $router->resource('article', ArticleController::class);
    $router->resource('expense_log', ExpenseLogController::class);
    $router->resource('account_log', AccountLogController::class);
    $router->resource('store_withdrawals', StoreWithdrawalsController::class);
    $router->resource('store_account_log', StoreAccountLogController::class);
    $router->resource('expose', ExposeController::class);
    $router->resource('exchange', ExchangeController::class);
    $router->resource('exchange_order', ExchangeOrderController::class);
    $router->resource('store_class', StoreClassController::class);
    $router->resource('nav', NavController::class);
    $router->resource('banner', BannerController::class);
    $router->resource('feedback', FeedBackController::class);
    $router->resource('version', VersionController::class);
    $router->resource('flash_sale', FlashSaleController::class);
    $router->resource('team_activity', TeamActivityController::class);
    $router->resource('promotion_category', PromotionCategoryController::class);
    $router->resource('tag', TagController::class);
    $router->resource('lottery', LotteryController::class);
    $router->resource('promotion', PromotionController::class);
    $router->resource('level', LevelController::class);
    $router->resource('user', UserController::class);
    $router->resource('withdrawals', WithdrawalsController::class);
    $router->resource('village_level', VillageLevelController::class);
    $router->resource('hot_search', HotSearchController::class);
    $router->resource('order_statis', OrderStatisController::class);
    $router->resource('team_found', TeamFoundController::class);
    $router->resource('lottery_follow', LotteryFollowController::class);
    $router->resource('user_rebate_log', UserRebateLogController::class);
    $router->resource('spu', SpuController::class);
    $router->resource('store_notice', StoreNoticeController::class);


});
