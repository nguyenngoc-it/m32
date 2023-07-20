<?php

namespace Modules\Order\Controllers;

use App\Base\ApplicationApiController;
use Gobiz\Log\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Order\Validators\CreateOrderValidator;
use Modules\Order\Validators\GetOrderStampsUrlValidator;
use Modules\Order\Validators\MappingTrackingValidator;
use Modules\Service;
use Modules\ShippingPartner\Services\ShippingPartnerException;

class OrderApplicationApiController extends ApplicationApiController
{
    /**
     * @return JsonResponse
     * @throws ShippingPartnerException
     */
    public function create()
    {
        $app   = $this->getApplication();
        $input = $this->request()->toArray();

        $ref                 = Arr::get($input, 'ref');
        $shippingCarrierCode = Arr::get($input, 'shipping_carrier_code');

        if ($ref && $shippingCarrierCode) {
            /** @var Order $exitsOrder */
            $exitsOrder = Order::query()->where('ref', $ref)
                ->where('application_id', $app->id)
                ->where('shipping_carrier_code', $shippingCarrierCode)
                ->where('status', '<>', Order::STATUS_CANCEL)
                ->first();
            if ($exitsOrder && $exitsOrder->tracking_no) {
                // Get Stamps Url
                $this->request()->merge(['tracking_nos' => [$exitsOrder->tracking_no]]);
                $stampsUrl = $this->getStampsUrl($exitsOrder->shipping_partner_code);
                return $this->response()->success(['order' => $exitsOrder, 'stampsUrl' => $stampsUrl]);
            }
        }

        $validator = new CreateOrderValidator($app, $input);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $shippingPartner = $validator->getShippingPartner();
        $order = Service::order()->create($shippingPartner, array_merge($input, [
            'sender_province_code' => $validator->getSenderProvince() ? $validator->getSenderProvince()->code : '',
            'receiver_province_code' => $validator->getReceiverProvince() ? $validator->getReceiverProvince()->code : '',
            'shipping_connect_code' => $validator->getShippingPartner() ? $validator->getShippingPartner()->partner_code : '',
        ]));

        // Get Stamps Url
        $stampsUrl = null;
        if ($order) {
            $this->request()->merge(['tracking_nos' => [$order->tracking_no]]);
            $stampsUrl = $this->getStampsUrl($order->shippingPartner->code);
        }

        return $this->response()->success(compact('order', 'stampsUrl'));
    }

    /**
     * @return JsonResponse
     */
    public function mappingTracking()
    {
        $app    = $this->getApplication();
        $inputs = $this->request()->only([
            'ref',
            'shipping_carrier_code',
            'shipping_connect_code',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'receiver_district_code',
            'receiver_ward_code',
            'weight',
            'cod',
            'items',
            'freight_bill_code'
        ]);

        LogService::logger('create-local-order')->info('INPUT', $inputs);
        $validator = new MappingTrackingValidator($app, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Service::order()->mappingTracking($validator->getInputOrder());

        return $this->response()->success(true);
    }

    /**
     * @param $code
     * @return array|JsonResponse
     * @throws ShippingPartnerException
     */
    public function getStampsUrl($code)
    {
        $validator = new GetOrderStampsUrlValidator($this->getApplication(), [
            'shipping_connect_code' => $code,
            'tracking_nos' => $this->request()->get('tracking_nos'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $shippingPartner = $validator->getShippingPartner();
        $orders          = $validator->getOrders();
        return [
            'url' => $shippingPartner->partner()->getOrderStampsUrl($orders->all()),
        ];
    }
}
