<?php

namespace Modules\SNAPPY\Controllers;

use App\Base\Controller;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\SNAPPY\Jobs\SyncOrderStatusJob;
use Modules\SNAPPY\Services\SNAPPYShippingPartner;

class SNAPPYController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        $input = $this->request()->only([
            'id',
            'current_status_en',
            'services',
        ]);

        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($input, $this->getAuthUser()->id));

        return $this->response()->success();
    }

    /**
     *  Đăng ký webhook với SNAPPY
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhookRegister()
    {
        $requests   = $this->requests->only('webhook_url');
        $webhookUrl = data_get($requests, 'webhook_url');
        $response   = [];

        $registered = [];
        if ($webhookUrl) {
            $snappys = ShippingPartner::query()->where('partner_code', ShippingPartner::PARTNER_SNAPPY)->get();
            foreach ($snappys as $snappy) {
                if (
                    $snappy instanceof ShippingPartner &&
                    isset($snappy->settings['business_id']) &&
                    !isset($registered[$snappy->settings['business_id']])
                ) {

                    $shippingPartner = (new SNAPPYShippingPartner($snappy->settings, config('services.snappy.api_url')));
                    $registered[$snappy->settings['business_id']] = $shippingPartner->webhookRegister($webhookUrl);
                }
            }
        }

        return $this->response()->success(compact('registered'));
    }
}
