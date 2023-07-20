<?php /** @noinspection ALL */

namespace Modules\NIJAVAP\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Gobiz\Validation\Validator;
use Modules\NIJAVAP\Jobs\SyncOrderStatusJob;
use Modules\NIJAVAP\Validators\NijavapHookTrackingValidator;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;

class NIJAVAPController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook($appId, $shippingPartnerCode)
    {
        $inputs     = $this->request()->all();
        $hmacSha256 = $this->request()->header('X-Ninjavan-Hmac-SHA256');
        LogService::logger('nijavap-hook')->debug($hmacSha256, $inputs);
        $validator = new NijavapHookTrackingValidator(array_merge(
            ['info' => $inputs, 'app_id' => $appId, 'shipping_partner_code' => $shippingPartnerCode],
            ['x_ninjavan_hmac_sha256' => $hmacSha256]
        ));
        if ($validator->fails()) {
            LogService::logger('nijavap-hook')->debug('error - ' . $hmacSha256, $inputs);
            return $this->response()->error($validator);
        }

        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($this->requests->toArray(), Service::user()->getSystemUser()->id));

        return $this->response()->success(true);
    }
}
