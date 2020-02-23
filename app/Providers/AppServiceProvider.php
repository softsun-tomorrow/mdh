<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Encore\Admin\Config\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191);

        if (class_exists(Config::class)) {
            Config::load();
        }
        \Carbon\Carbon::setLocale('zh');

        Relation::morphMap([
            'user' => 'App\Models\User',
            'store' => 'App\Models\Store',
            'goods' => 'App\Models\Goods',
            'tyfon' => 'App\Models\Tyfon',
            'tyfon_comment' => 'App\Models\TyfonComment',
        ]);

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
