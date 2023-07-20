<?php

namespace Modules\Application\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Application\Model\Application;
use Modules\Service;

class AuthenticateApplication
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * @var Application $app
         */
        $auth = Auth::guard('application');

        if ($auth->guest()) {
            return Service::app()->response()->error(401, ['message' => 'Unauthenticated'], 401);
        }

        $app = $auth->user();
        $ip = $request->ip();
        if (!empty($app->allowed_ips) && !in_array($ip, $app->allowed_ips, true)) {
            return Service::app()->response()->error(403, ['message' => "Your IP {$ip} has been blocked"], 403);
        }

        return $next($request);
    }
}
