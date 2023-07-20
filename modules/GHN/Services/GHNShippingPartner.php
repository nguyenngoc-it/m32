<?php

namespace Modules\GHN\Services;

use Closure;
use Gobiz\Log\LogService;
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

class GHNShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://online-gateway.ghn.vn/';

    /**
     * @var array
     */
    protected $settings = [
        'token' => '',
        'shop_id' => '',
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * GHNLastMilePartner constructor.
     * @param string $apiUrl
     * @param array $settings
     */
    public function __construct(array $settings, $apiUrl = null)
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->apiUrl   = $apiUrl ?: $this->apiUrl;
        $this->http     = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Token' => $this->settings['token'],
                'ShopId' => (int)$this->settings['shop_id'],
            ],
        ]);
        $this->logger   = LogService::logger('ghn');
    }

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_GHN];
    }

    /**
     * Test connection
     *
     * @throws ShippingPartnerException
     */
    public function test()
    {
        $response = $this->sendRequest(function () {
            return $this->http->post('shiip/public-api/v2/shipping-order/available-services', [
                'json' => [
                    'shop_id' => (int)$this->settings['shop_id'],
                    'from_district' => 1447,
                    'to_district' => 1442,
                ],
            ]);
        });
        return $response;
    }

    /**
     * Create order
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerException
     */
    public function createOrder(Order $order)
    {
        $request = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $request);
        $res = $this->sendRequest(function () use ($request) {
            return $this->http->post('shiip/public-api/v2/shipping-order/create', ['json' => $request]);
        });
        $this->logger->debug('CREATE_ORDER_RESPONSE', $res->getData());

        $success = $res->success();
        if ($success) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('data.order_code');
            $order->trackingNo = $res->getData('data.order_code');
            $order->fee        = $res->getData('data.total_fee');
            $order->sender     = $this->getSenderShipping($order->code);
            $order->query      = $request;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        if (!$wardName = $this->getGHNLocationName($order->receiver_ward_code)) {
            throw new ShippingPartnerException("GHN: Can not find ward of $order->receiver_ward_code");
        }
        if (!$districtName = $this->getGHNLocationName($order->receiver_district_code)) {
            throw new ShippingPartnerException("GHN: Can not find district of $order->receiver_district_code");
        }
        if (!$provinceName = $this->getGHNLocationName($order->receiver_province_code)) {
            throw new ShippingPartnerException("GHN: Can not find province of $order->receiver_province_code");
        }

        return [
            'to_name' => $order->receiver_name,
            'to_phone' => $order->receiver_phone,
            'to_address' => $order->receiver_address,
            'to_ward_name' => (string)$wardName,
            'to_district_name' => (string)$districtName,
            'to_province_name' => (string)$provinceName,
            'client_order_code' => $this->makeReferenceNumber($order),
            'cod_amount' => $order->cod,
            'content' => $order->items->pluck('code')->implode(', '),
            'weight' => (int)($order->weight * 1000), // kg => g
            'length' => (int)($order->length * 100), // m => cm
            'width' => (int)($order->width * 100), // m => cm
            'height' => (int)($order->height * 100), // m => cm
            'service_type_id' => 2, // Gói cước dịch vụ Chuẩn
            'payment_type_id' => 1, // Người gửi trả tiền
            'required_note' => 'KHONGCHOXEMHANG',
            'items' => $order->items()->get()->only(['name', 'code', 'quantity']),
        ];
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getGHNLocationId($locationCode)
    {
        /** @var ShippingPartnerLocation $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_GHN)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? $location->identity : $location->code;
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getGHNLocationName($locationCode)
    {
        /** @var ShippingPartnerLocation $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_GHN)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? $location->name : '';
    }

    /**
     * Lấy url tem của danh sách đơn
     *
     * @param Order[] $orders
     * @return string|null
     * @throws ShippingPartnerException
     */
    public function getOrderStampsUrl(array $orders)
    {
        $token = $this->sendRequest(function () use ($orders) {
            return $this->http->post('shiip/public-api/v2/a5/gen-token', ['json' => ['order_codes' => Arr::pluck($orders, 'code')]]);
        })->getData('data.token');

        return "{$this->apiUrl}a5/public-api/printA5?token={$token}";
    }

    /**
     * @param $ordeCode
     * @return array
     * @throws ShippingPartnerException
     */
    public function getSenderShipping($ordeCode)
    {
        $res = $this->sendRequest(function () use ($ordeCode) {
            return $this->http->post('shiip/public-api/v2/shipping-order/detail', ['json' => ['order_code' => $ordeCode]]);
        });

        if ($res->success()) {

            $data             = $res->getData('data');
            $sender           = [
                'name' => $data['from_name'],
                'phone' => $data['from_phone'],
                'address' => $data['from_address'],
            ];
            $m32LocationCodes = $this->getCodeLocationsFromShippingProvider(ShippingPartner::PARTNER_GHN, [
                (string)$data['from_district_id'],
                (string)$data['from_ward_code'],
            ], 'identity');

            $sender[ShippingPartner::SENDER_DISTRICT_CODE] = !empty($m32LocationCodes[$data['from_district_id']]) ? $m32LocationCodes[$data['from_district_id']] : '';
            $sender[ShippingPartner::SENDER_WARD_CODE]     = !empty($m32LocationCodes[$data['from_ward_code']]) ? $m32LocationCodes[$data['from_ward_code']] : '';
            if ($sender[ShippingPartner::SENDER_DISTRICT_CODE]) {
                $districtLocation = Location::query()->where('code', $sender[ShippingPartner::SENDER_DISTRICT_CODE])->first();
                if ($districtLocation instanceof Location) {
                    $sender[ShippingPartner::SENDER_PROVINCE_CODE] = $districtLocation->parent_code;
                }
            }
            return $sender;
        }

        return [];
    }

    /**
     * Tính phí VC
     * @param Location $senderWard
     * @param Location $receiveWard
     * @param ShippingPartnerSize $shippingPartnerSize
     * @return float|mixed
     * @throws ShippingPartnerException
     */
    public function shippingFee(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize)
    {
        $request = $this->makeShippingFeeData($senderWard, $receiveWard, $shippingPartnerSize);

        $this->logger->debug('SHIPPING_FEE', $request);

        $res = $this->sendRequest(function () use ($request) {
            return $this->http->post('shiip/public-api/v2/shipping-order/fee', ['json' => $request]);
        });

        return $res->getData('data.total');
    }

    /**
     * @param Location $senderWard
     * @param Location $receiveWard
     * @param ShippingPartnerSize $shippingPartnerSize
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeShippingFeeData(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize)
    {
        if (!$fromDistrictId = $this->getGHNLocationId($senderWard->parent_code)) {
            throw new ShippingPartnerException("GHN: Can not find district of {$senderWard->code}");
        }

        if (!$wardCode = $this->getGHNLocationId($receiveWard->code)) {
            throw new ShippingPartnerException("GHN: Can not find ward of {$receiveWard->code}");
        }

        if (!$districtCode = $this->getGHNLocationId($receiveWard->parent_code)) {
            throw new ShippingPartnerException("GHN: Can not find district of {$receiveWard->parent_code}");
        }

        return [
            "from_district_id" => (int)$fromDistrictId,
            'to_ward_code' => (string)$wardCode,
            'to_district_id' => (int)$districtCode,
            'service_id' => null,
            'service_type_id' => 2,
            'weight' => round($shippingPartnerSize->weight * 1000), // kg => g
            'length' => round($shippingPartnerSize->length * 100), // m => cm
            'width' => round($shippingPartnerSize->width * 100), // m => cm
            'height' => round($shippingPartnerSize->height * 100), // m => cm
        ];
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
            return $this->http->get('shiip/public-api/master-data/province');
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
            return $this->http->get('shiip/public-api/master-data/district', ['json' => ['province_id' => (int)$provinceId]]);
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
            return $this->http->get('shiip/public-api/master-data/ward?district_id', ['json' => ['district_id' => (int)$districtId]]);
        })->getData('data');
    }

    /**
     * @param Closure $handler
     * @return RestApiResponse
     * @throws ShippingPartnerException
     */
    protected function sendRequest(Closure $handler)
    {
        return $this->request($handler);
    }


    /**
     * Lấy thông tin đơn từ bên DVVC
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     */
    public function getOrderInfo(Order $order)
    {
        $order = new ShippingPartnerOrder();
        return $order;
    }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return array
     * @throws ShippingPartnerException
     */
    public function getTrackings(array $trackings)
    {
        $this->logger->debug('TRACKING', $trackings);
        $tracking = $trackings[0];
        $res      = $this->sendRequest(function () use ($tracking) {
            return $this->http->post("shiip/public-api/v2/shipping-order/detail", ['json' => ['order_code' => $tracking]]);
        });
        $this->logger->debug('TRACKING_RESPONSE', $res->getData());
        /**
         * Save log response
         */
        $data = $res->getData();
        $this->logger->debug('RESPONSE_TRACKING', $data);

        $result     = [];
        if ($res->success() && $tracesList = $res->getData('data', [])) {
            foreach ($tracesList as $trace) {
                $trackingCode = Arr::get($trace, 'order_code');
                $originStatus = Arr::get($trace, 'status');
                $status       = Service::ghn()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                    $result[] = $tracking;
                }
            }
        }

        return $result;
    }
}
