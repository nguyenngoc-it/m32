<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentry\State\Scope;

class SentryContext
{
    /**
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (env('SENTRY_ENABLE') && app()->bound('sentry')) {
            if ($user = Auth::user()) {
                \Sentry\configureScope(function (Scope $scope) use ($user) {
                    $scope->setUser([
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                    ]);
                });
            }

            if ($app = Auth::guard('application')->user()) {
                \Sentry\configureScope(function (Scope $scope) use ($app) {
                    $scope->setUser([
                        'id' => 'application.'.$app->id,
                        'username' => $app->code,
                        'name' => $app->name,
                    ]);
                });
            }
        }

        return $next($request);
    }
}
