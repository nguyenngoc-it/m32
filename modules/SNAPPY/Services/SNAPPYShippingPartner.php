<?php

namespace Modules\SNAPPY\Services;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\ShippingPartner\Services\ShippingPartnerSize;

class SNAPPYShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://pos.pages.fm/api/v1/';

    /**
     * @var array
     */
    protected $settings = [
        'business_id' => '',
        'business_address_id' => '',
        'access_token' => '',
        'is_allow_checking_good' => false
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * SNAPPYLastMilePartner constructor.
     * @param string|null $apiUrl
     * @param array $settings
     */
    public function __construct(array $settings, string $apiUrl = null)
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->apiUrl   = $apiUrl ?: $this->apiUrl;

        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';

        $this->http   = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => $headers,
        ]);
        $this->logger = LogService::logger('snappy');
    }

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers(): array
    {
        return [ShippingPartner::CARRIER_SNAPPY];
    }

    /**
     * Test connection
     *
     * @throws ShippingPartnerException
     */
    public function test()
    {
        $this->sendRequest(function () {

        });
    }

    /**
     * @param Order $order
     * @return ShippingPartnerOrder|null
     * @throws ShippingPartnerException
     */
    public function createOrder(Order $order): ?ShippingPartnerOrder
    {
        $request = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $request);
        $res = $this->sendRequest(function () use ($request) {
            return $this->http->post('snappy/trackings/create?access_token=' . $this->settings['access_token'], ['body' => json_encode($request)]);
        });
        $this->logger->debug('RESPONSE_CREATE_ORDER', (array)$res->getData());
        if ($res->success()) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('tracking.id');
            $order->trackingNo = $res->getData('tracking.id');
            $order->fee        = $res->getData('tracking.services.shipping_cost');
            $order->sender     = $this->getSenderShipping($res->getData('tracking.from'));
            $order->query      = $request;
            $order->response   = (array)$res->getData();
            return $order;
        }

        return null;
    }

    /**
     * @param $callbackUrl
     * @return array
     */
    public function webhookRegister($callbackUrl)
    {
        $res = $this->sendRequest(function () use ($callbackUrl) {
            $request = [
                'callback_url' => $callbackUrl
            ];
            return $this->http->post('snappy/businesses/'.$this->settings['business_id'].'/webhook?access_token=' . $this->settings['access_token'], ['body' => json_encode($request)]);
        });
        return (array)$res->getData();
    }

    /**
     * Tính phí VC
     * @param Location $senderWard
     * @param Location $receiveWard
     * @param ShippingPartnerSize $shippingPartnerSize
     * @return float|mixed
     * @throws ShippingPartnerException
     */
    public function shippingFee(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize): ?float
    {
        $request = $this->makeShippingFeeData($senderWard, $receiveWard, $shippingPartnerSize);

        $this->logger->debug('SHIPPING_FEE', $request);

        $res = $this->sendRequest(function () use ($request) {
            return $this->http->post('snappy/trackings/cal_shipping_cost?access_token=' . $this->settings['access_token'], ['body' => json_encode($request)]);
        });

        $success = $res->getData('success', true);
        if ($success === false) {
            $message = $res->getData('message');
            $this->logger->debug('Error: ' . $message, $request);

            throw new ShippingPartnerException("SNAPPY - Shipping Fee Error - $message");
        }

        return $res->getData('shipping_cost');
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order): array
    {
        if (!$provinceCode = $this->getSNAPPYLocationCode($order->receiver_province_code)) {
            throw new ShippingPartnerException("SNAPPY: Can not find province of $order->receiver_province_code");
        }

        if (!$wardCode = $this->getSNAPPYLocationCode($order->receiver_ward_code)) {
            throw new ShippingPartnerException("SNAPPY: Can not find ward of $order->receiver_ward_code");
        }

        if (!$districtCode = $this->getSNAPPYLocationCode($order->receiver_district_code)) {
            throw new ShippingPartnerException("SNAPPY: Can not find district of $order->receiver_district_code");
        }

        return [
            'service_name' => 'express',
            'business_id' => $this->settings['business_id'],
            'business_address_id' => $this->settings['business_address_id'],
            'receiver_name' => $order->receiver_name,
            'receiver_phone_number' => $order->receiver_phone,
            'receiver_address' => $order->receiver_address,
            'receiver_province_id' => $provinceCode,
            'receiver_district_id' => $districtCode,
            'receiver_commune_id' => $wardCode,
            'total_weight' => round($order->weight * 1000),
            'items' => $this->makeOrderItems($order),
            'is_allow_checking_good' => (bool)$this->settings['is_allow_checking_good'],
            'cod' => (int)$order->cod,
            'value' => (int)$order->total_amount,
            'shop_note' => $this->makeReferenceNumber($order)
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function makeOrderItems(Order $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'weight' => 0,
            ];
        }

        return $items;
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getSNAPPYLocationCode(string $locationCode): ?string
    {
        /** @var Location|null $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_SNAPPY)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? $location->code : null;
    }

    /**
     * @param Location $senderWard
     * @param Location $receiveWard
     * @param ShippingPartnerSize $shippingPartnerSize
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeShippingFeeData(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize): array
    {
        if (!$senderDistrictId = $this->getSNAPPYLocationCode($senderWard->parent_code)) {
            throw new ShippingPartnerException("SNAPPY: Can not find district of $senderWard->code");
        }

        if (!$receiverDistrictId = $this->getSNAPPYLocationCode($receiveWard->parent_code)) {
            throw new ShippingPartnerException("SNAPPY: Can not find district of $senderWard->code");
        }

        return [
            'business_id' => $this->settings['business_id'],
            'sender_district_id' => $senderDistrictId,
            'receiver_district_id' => $receiverDistrictId,
            'total_weight' => round($shippingPartnerSize->weight * 1000),
        ];
    }

    /**
     * @param Closure $handler
     * @return RestApiResponse
     */
    protected function sendRequest(Closure $handler): RestApiResponse
    {
        return $this->request($handler);
    }

    /**
     * @param array $senderSnappy
     * @return array
     */
    private function getSenderShipping(array $senderSnappy): array
    {
        $sender           = [
            'name' => $senderSnappy['name'],
            'phone' => $senderSnappy['phone_number'],
            'address' => $senderSnappy['address'],
        ];
        $m32LocationCodes = $this->getCodeLocationsFromShippingProvider(ShippingPartner::PARTNER_SNAPPY, [
            $senderSnappy['province_id'],
            $senderSnappy['district_id'],
            $senderSnappy['commune_id'],
        ]);

        $sender[ShippingPartner::SENDER_PROVINCE_CODE] = !empty($m32LocationCodes[$senderSnappy['province_id']]) ? $m32LocationCodes[$senderSnappy['province_id']] : '';
        $sender[ShippingPartner::SENDER_DISTRICT_CODE] = !empty($m32LocationCodes[$senderSnappy['district_id']]) ? $m32LocationCodes[$senderSnappy['district_id']] : '';
        $sender[ShippingPartner::SENDER_WARD_CODE]     = !empty($m32LocationCodes[$senderSnappy['commune_id']]) ? $m32LocationCodes[$senderSnappy['commune_id']] : '';

        return $sender;
    }


    /**
     * Lấy thông tin đơn từ bên DVVC
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     */
    public function getOrderInfo(Order $order): ShippingPartnerOrder
    {
        return new ShippingPartnerOrder();
    }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     */
    public function getTrackings(array $trackings): array
    {
        $this->logger->debug('TRACKING', $trackings);
        $tracking = implode(',', $trackings);
        $res      = $this->sendRequest(function () use ($tracking) {
            return $this->http->get("snappy/trackings?access_token={$this->settings['access_token']}&business_id={$this->settings['business_id']}&keyword=$tracking");
        });
        $this->logger->debug('TRACKING', $res->getData());

        $result = [];
        if ($res->success()) {
            $tracesList = $res->getData('trackings_data.trackings', []);
            foreach ($tracesList as $trace) {
                $trackingCode = Arr::get($trace, 'id');
                $originStatus = Arr::get($trace, 'current_status_en');
                $status       = Service::snappy()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                    $result[] = $tracking;
                }
            }
        }
        return $result;
    }

    /**
     * Lấy url tem của danh sách đơn
     *
     * @param Order[] $orders
     * @return string
     * @throws RestApiException
     */
    public function getOrderStampsUrl(array $orders)
    {
        $trackings = array_map(function ($order) {
            return $order->tracking_no;
        }, $orders);
        return "https://snappy.vn/print/" . implode(',', $trackings) . '&' . http_build_query([
                'business_id' => $this->settings['business_id'],
                'access_token' => $this->settings['access_token'],
            ]);
    }

    /**
     * Get list provinces
     *
     * @return array
     * @throws ShippingPartnerException
     */
    public function getProvinces()
    {
        return $this->sendRequest(function () {
            return $this->http->get('geo/provinces');
        })->getData('data');
    }

    /**
     * Get list districts of a province
     *
     * @param int $provinceId
     * @return array
     * @throws ShippingPartnerException
     */
    public function getDistricts($provinceId)
    {
        return $this->sendRequest(function () use ($provinceId) {
            return $this->http->get('geo/districts?province_id=' . $provinceId);
        })->getData('data');
    }

    /**
     * @param int $districtId
     * @return array|mixed
     * @throws ShippingPartnerException
     */
    public function getWards($districtId)
    {
        return $this->sendRequest(function () use ($districtId) {
            return $this->http->get('geo/communes?district_id=' . $districtId);
        })->getData('data');
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function makeReferenceNumber(Order $order)
    {
        return ($order->ref) ? $order->ref : "TRACKING_".$order->id;
    }
}
