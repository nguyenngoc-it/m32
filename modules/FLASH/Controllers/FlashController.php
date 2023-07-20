<?php

namespace Modules\FLASH\Controllers;

use App\Base\Controller;
use Modules\FLASH\Jobs\SyncOrderStatusJob;
use Modules\FLASH\Services\FLASHShippingPartner;
use Illuminate\Http\JsonResponse;
use Modules\ShippingPartner\Models\ShippingPartner;

class FlashController extends Controller
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
     * Đăng ký webhook với Flash
     *
     * @return JsonResponse
     */
    public function webhookRegister()
    {
        $requests   = $this->requests->only('webhook_url');
        $webhookUrl = data_get($requests, 'webhook_url');
        $response   = [];

        if ($webhookUrl) {
            $flash = ShippingPartner::where(['partner_code' => ShippingPartner::PARTNER_FLASH])->first();
            if ($flash) {
                $apiUrl = config('services.flash.api_url');
                $shippingPartner = new FLASHShippingPartner($flash->settings, $apiUrl);
                $response = $shippingPartner->webhookRegister($webhookUrl);
                // dd($response);
            }
        }

        return $this->response()->success($response);
    }
}
