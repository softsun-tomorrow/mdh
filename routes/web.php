<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::any('/uploadFile', 'UploadsController@uploadImg');
Route::get('web/index/goodsContent','Web\IndexController@goodsContent');
Route::any('web/index/article','Web\IndexController@article');
Route::any('web/index/download','Web\IndexController@download');
Route::any('web/index/share','Web\IndexController@share');
Route::any('web/register','Web\IndexController@register');
Route::any('web/download','Web\IndexController@download');
Route::get('web/goods/goodsContent','Web\GoodsController@goodsContent');
Route::get('web/goods/spu','Web\GoodsController@spu');
Route::get('web/goods/detail','Web\GoodsController@detail');
Route::get('web/goods/share','Web\GoodsController@share');


