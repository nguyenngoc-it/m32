<?php

namespace Modules\ShippingPartner\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Service;

class ShippingProviderController extends Controller
{
    /**
     * Danh sách các providers có thể lựa chọn để tạo shipping partner
     *
     * @return JsonResponse
     */
    public function listProviders()
    {
        return $this->response()->success([
            'providers' => Service::shippingPartner()->providers(),
        ]);
    }
}
