<?php

namespace Modules\Application\Validators;

use App\Base\Validator;
use Modules\Application\Model\Application;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerProviderInterface;

class CreateShippingPartnerValidator extends Validator
{
    /**
     * @return array
     */
    public function rules()
    {
        $providers = Service::shippingPartner()->providers();
        $partnerCodes = collect($providers)->map(function (ShippingPartnerProviderInterface $provider){
            return $provider->getCode();
        })->toArray();
        return [
            'name' => 'required|string',
            'code' => 'required|alpha_num',
            'description' => 'string',
            'partner_code' => 'required|string|in:' . implode(',', $partnerCodes),
            'setting_params' => 'required|array',
        ];
    }

    public function customValidate()
    {
        /** @var Application $application */
        $application     = $this->input('application');
        $code            = $this->input('code');
        $shippingPartner = Service::shippingPartner()->findByCode($application->id, $code);
        if ($shippingPartner) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }
    }
}
