<?php

namespace App\Base;

use Gobiz\Transformer\TransformerService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Modules\App\Services\ResponseFactoryInterface;
use Modules\Application\Model\Application;
use Modules\Service;

class ApplicationApiController extends BaseController
{
    /**
     * @return Request
     */
    protected function request()
    {
        return app(Request::class);
    }

    /**
     * @return ResponseFactoryInterface
     */
    protected function response()
    {
        return Service::app()->response();
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function transform($data)
    {
        return TransformerService::transform($data);
    }

    /**
     * @return Application|Authenticatable|null
     */
    protected function getApplication()
    {
        return Auth::guard('application')->user();
    }
}