<?php

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Service;

class Authorize
{
    public function handle($request, $next, $abilities, $object = null)
    {
        return $this->authorize('check', $request, $next, $abilities, $object);
    }

    protected function any($request, $next, $abilities, $object = null)
    {
        return $this->authorize('any', $request, $next, $abilities, $object);
    }

    /**
     * @param string $method
     * @param Request $request
     * @param Closure $next
     * @param string $abilities
     * @param string|null $object
     * @return mixed
     */
    protected function authorize($method, $request, $next, $abilities, $object)
    {
        $abilities = explode('|', $abilities);
        $arguments = $object ? [$request->route($object)] : [];

        return !$this->performAuthorize($method, $abilities, $arguments)
            ? $this->responseUnauthorized()
            : $next($request);
    }

    /**
     * @param string $method
     * @param array $abilities
     * @param array $arguments
     * @return bool
     */
    protected function performAuthorize($method, array $abilities, array $arguments)
    {
        return call_user_func(Gate::class.'::'.$method, $abilities, $arguments);
    }

    /**
     * @return mixed
     */
    protected function responseUnauthorized()
    {
        return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
    }
}