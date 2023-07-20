<?php

namespace Modules\NIJAVAP\Validators;

use App\Base\Validator;
use Modules\ShippingPartner\Models\ShippingPartner;

class NijavapHookTrackingValidator extends Validator
{

    /** @var ShippingPartner|null */
    protected $shippingPartner;

    public function rules(): array
    {
        return [
            'x_ninjavan_hmac_sha256' => 'required',
            'info' => 'array',
            'app_id' => 'required',
            'shipping_partner_code' => 'required'
        ];
    }

    protected function customValidate()
    {
        $hmacSha256            = $this->input('x_ninjavan_hmac_sha256');
        $info                  = $this->input('info', []);
        $appId                 = $this->input('app_id');
        $shippingPartnerCode   = $this->input('shipping_partner_code');
        $this->shippingPartner = ShippingPartner::query()->where([
            'application_id' => $appId,
            'code' => $shippingPartnerCode
        ])->first();
        if (!$this->shippingPartner) {
            $this->errors()->add('shipping_partner', 'not_found');
            return;
        }

        $secret = $this->shippingPartner->settings['client_key'];
        if (!$this->verifyWebhook($info, $hmacSha256, $secret)) {
            $this->errors()->add('x_ninjavan_hmac_sha256', 'not_valid');
        }
    }

    /**
     * @param $info
     * @param $hmac_header
     * @param $secret
     * @return bool
     */
    private function verifyWebhook($info, $hmac_header, $secret): bool
    {
        $calculated_hmac = base64_encode(hash_hmac('sha256', json_encode($info), $secret, true));
        return ($hmac_header == $calculated_hmac);
    }
}
