<?php

namespace Modules\ShippingPartner\Controllers;

use App\Base\ApplicationApiController;
use Illuminate\Http\JsonResponse;
use Modules\ShippingPartner\Validators\ShippingFeeValidator;
use Modules\ShippingPartner\Services\ShippingPartnerException;

class ShippingPartnerApplicationApiController extends ApplicationApiController
{
    /**
     * @param $code
     * @return JsonResponse
     * @throws ShippingPartnerException
     */
    public function shippingFee($code): JsonResponse
    {
        $app       = $this->getApplication();
        $input     = $this->request()->toArray();
        $input['code'] = trim($code);
        $validator = new ShippingFeeValidator($app, $input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $shippingPartner  = $validator->getShippingPartner();
        $shippingFee = $shippingPartner->partner()->shippingFee(
            $validator->getSenderWard(),
            $validator->getReceiverWard(),
            $validator->getShippingPartnerSize(),
        );

        return $this->response()->success(compact('shippingFee'));
    }
}
