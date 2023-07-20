<?php

namespace Modules\SAPI\Services;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;

class SAPIShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://apisanbox.coresyssap.com/';

    /**
     * @var string
     */
    protected $trackUrl = 'https://track.coresyssap.com';

    /**
     * @var array
     */
    protected $settings = [
        'customer_code' => '',
        'customer_code_cod' => '',
        'api_key' => '',
        'sender' => [],
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * GHNLastMilePartner constructor.
     * @param array $settings
     * @param string $apiUrl
     * @param string $trackUrl
     */
    public function __construct(array $settings, $apiUrl = null, $trackUrl = null)
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->apiUrl   = $apiUrl ?: $this->apiUrl;
        $this->trackUrl = $trackUrl ?: $this->trackUrl;
        $this->http     = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'api-key' => $this->settings['api_key']
            ],
        ]);
        $this->logger   = LogService::logger('sapi');
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        if (!$receiverWardCode = $this->getSAPILocationId($order->receiver_ward_code)) {
            throw new ShippingPartnerException("SAPI: Can not find ward of $order->receiver_ward_code");
        }
        $sender = $this->settings['sender'];

        return [
            'customer_code' => $order->cod ? $this->settings['customer_code_cod'] : $this->settings['customer_code'],
            'reference_no' => $this->makeReferenceNumber($order),
            'pickup_name' => $sender[ShippingPartner::SENDER_NAME],
            'pickup_phone' => $sender[ShippingPartner::SENDER_PHONE],
            'pickup_address' => $sender[ShippingPartner::SENDER_ADDRESS],
            'pickup_district_code' => $this->getSAPILocationId($sender[ShippingPartner::SENDER_WARD_CODE]),
            'service_type_code' => 'UDRREG',
            'shipment_type_code' => 'SHTPC',
            'quantity' => 1,
            'weight' => ceil($order->weight), // kg
            'volumetric' => ($order->height ?: '1') . 'x' . ($order->width ?: '1') . 'x' . ($order->length ?: '1'),
            'insurance_flag' => 0,
            'insurance_value' => 0.3,
            'cod_flag' => $order->cod ? 2 : 1,
            'cod_value' => $order->cod,
            'item_value' => $order->cod,
            'shipper_name' => $sender[ShippingPartner::SENDER_NAME],
            'shipper_phone' => $sender[ShippingPartner::SENDER_PHONE],
            'shipper_address' => $sender[ShippingPartner::SENDER_ADDRESS],
            'destination_district_code' => $receiverWardCode,
            'receiver_name' => $order->receiver_name,
            'receiver_phone' => $order->receiver_phone,
            'receiver_address' => $order->receiver_address,
            'description_item' => Service::sapi()->getRemark($order)
        ];
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getSAPILocationId($locationCode)
    {
        /** @var ShippingPartnerLocation|null $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_SAPI)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? $location->identity : $locationCode;
    }

    /**
     * @param Closure $handler
     * @return RestApiResponse
     */
    protected function sendRequest(Closure $handler)
    {
        return $this->request($handler);
    }

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_SAPI];
    }

    /**
     * Test connection
     *
     */
    public function test()
    {
        $response = $this->sendRequest(function () {
            return $this->http->get('master/service_type/get');
        });
        return $response->getData();
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
        $jsonParams = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $jsonParams);

        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('shipment/pickup/single_push', ['json' => $jsonParams]);
        });

        if ($res->getData('status') === 'fail') {
            $this->logger->error('CREATE_ORDER_RESPONSE ' . $order->ref, $res->getData());
            throw new ShippingPartnerException($res->getData('msg'));
        }

        /**
         * Save log response
         */
        $data = $res->getData('data');
        $this->logger->debug('RESPONSE_CREATED_ORDER', $data);

        $success = $res->success();
        if ($success && $trackingNo = Arr::get($data, 'awb_no')) {
            $order             = new ShippingPartnerOrder();
            $order->code       = Arr::get($data, 'reference_no');
            $order->trackingNo = $trackingNo;
            $order->fee        = 0;
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $jsonParams;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
    }

    /**
     * Lấy thông tin đơn từ bên DVVC
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     */
    public function getOrderInfo(Order $order)
    {
        return new ShippingPartnerOrder();
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
        $urls = array_map(function (Order $order) {
            return "$this->trackUrl/shipment/label_100x80/printout?" . http_build_query([
                    'awb_no' => $order->tracking_no,
                    'api_key' => $this->settings['api_key'],
                ]);
        }, $orders);

        return $this->mergePdfStamps($urls);
    }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     */
    public function getTrackings(array $trackings)
    {
        $this->logger->debug('TRACKING', $trackings);
        $tracking = $trackings[0];

        $res = $this->sendRequest(function () use ($tracking) {
            return Helper::quickCurl($this->trackUrl,
                'shipment/tracking/awb',
                'get',
                [
                    'Content-Type' => 'application/json',
                    'api-key' => $this->settings['api_key']
                ],
                ['awb_no' => $tracking],
                false
            );
        });
        /**
         * Save log response
         */
        $data = $res->getData();
        $this->logger->debug('TRACKING_RESPONSE', $data);

        $result = [];
        if ($res->success() && $tracesList = $data) {
            $trace = last($tracesList);
            $trackingCode = Arr::get($trace, 'tracking_doc_no');
            $originStatus = Arr::get($trace, 'rowstate_name');
            $status       = Service::sapi()->mapStatus($originStatus);
            if ($trackingCode && $originStatus && $status) {
                $tracking = new Tracking($trackingCode, $originStatus, $status);
                $result[] = $tracking;
            }
        }
        return $result;
    }
}
