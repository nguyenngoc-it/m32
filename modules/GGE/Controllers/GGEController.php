<?php

namespace Modules\GGE\Controllers;

use App\Base\Controller;
use Modules\GGE\Jobs\SyncOrderStatusJob;
use Modules\GGE\Services\GGEShippingPartner;
use Illuminate\Http\JsonResponse;
use Modules\ShippingPartner\Models\ShippingPartner;

class GGEController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        $data = data_get($this->requests->only('data'), 'data');
        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($data, $this->getAuthUser()->id));

        return $this->response()->success(["errorCode" => "1", "state" => "success"]);
    }

    /**
     * Đăng ký webhook với GGE
     *
     * @return JsonResponse
     */
    public function webhookRegister()
    {
        $requests   = $this->requests->only('webhook_url');
        $webhookUrl = data_get($requests, 'webhook_url');
        $response   = [];

        if ($webhookUrl) {
            $GGE = ShippingPartner::where(['partner_code' => ShippingPartner::PARTNER_GGE])->first();
            if ($GGE) {
                $apiUrl = config('services.GGE.api_url');
                $shippingPartner = new GGEShippingPartner($GGE->settings, $apiUrl);
                $response = $shippingPartner->webhookRegister($webhookUrl);
                // dd($response);
            }
        }

        return $this->response()->success($response);
    }
}
