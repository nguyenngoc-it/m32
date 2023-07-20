<?php

namespace Modules\JNTM\Controllers;

use App\Base\Controller;
use Modules\FLASH\Jobs\SyncOrderStatusJob;
use Modules\FLASH\Services\FLASHShippingPartner;
use Illuminate\Http\JsonResponse;
use Modules\JNTM\Services\JNTMShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartner;

class JntmController extends Controller
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
            $jntm = ShippingPartner::where(['partner_code' => ShippingPartner::PARTNER_JNTM])->first();
            if ($jntm) {
                $apiUrl = config('services.jntm.api_url');
                $shippingPartner = new JNTMShippingPartner($jntm->settings, $apiUrl);
                $response = $shippingPartner->webhookRegister($webhookUrl);
                // dd($response);
            }
        }

        return $this->response()->success($response);
    }
}
