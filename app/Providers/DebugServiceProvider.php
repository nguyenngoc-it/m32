<?php

namespace App\Providers;

use Gobiz\Log\LogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    public function boot()
    {
        if (env('DEBUG_SQL')) {
            DB::listen(function ($query) {
                $sql = str_replace(['?'], ['\'%s\''], $query->sql);
                $sql = vsprintf($sql, $query->bindings);

                LogService::logger('sql')->debug($sql);
            });
        }
    }
}
