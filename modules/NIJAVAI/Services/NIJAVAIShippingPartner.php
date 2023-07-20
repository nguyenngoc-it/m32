<?php

namespace Modules\NIJAVAI\Services;

use Carbon\Carbon;
use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\ShippingPartner\Services\ShippingPartnerSize;

class NIJAVAIShippingPartner implements ShippingPartnerInterface
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://api-sandbox.ninjavan.co/SG/';

    /**
     * @var array
     */
    protected $settings = [
        'token' => '',
        'sender' => [],
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
                'Authorization' => 'Bearer ' . $this->settings['token'],
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->logger   = LogService::logger('nijavai');
    }

    /**
     * Lấy tham số bắt buộc cho mỗi request
     * Do J&t không truyền qua headers nên sẽ phải gán qua form_params với
     * Json parameters
     *
     * @param $messageType
     * @param array $jsonParams
     * @return array
     */
    protected function getProtocolParameters($messageType, array $jsonParams)
    {
        $dataDigest = base64_encode(md5(json_encode($jsonParams) . $this->settings['key']));
        return [
            'logistics_interface' => json_encode($jsonParams),
            'data_digest' => $dataDigest,
            'msg_type' => $messageType,
            'eccompanyid' => $this->settings['eccompanyid'],
            'customerid' => $this->settings['customerid']
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function makeOrderData(Order $order)
    {
        $items = $order->items->map(function (OrderItem $orderItem) {
            return [
                'item_description' => $orderItem->name,
                'quantity' => $orderItem->quantity,
                'is_dangerous_good' => false
            ];
        });

        return [
            'service_type' => 'Parcel',
            'service_level' => 'Standard',
            'requested_tracking_number' => 'ORDER_' . $order->id,
            'from' => [
                'name' => $order->sender_name,
                'phone_number' => $order->sender_phone,
                'address' => [
                    'address1' => $order->sender_address,
                    'city' => $order->senderWard ? $order->senderWard->parent->label : '',
                    'province' => $order->senderWard ? $order->senderWard->parent->parent->label : '',
                    'country' => 'ID'
                ]
            ],
            'to' => [
                'name' => $order->receiver_name,
                'phone_number' => $order->receiver_phone,
                'address' => [
                    'address1' => $order->receiver_address,
                    'city' => $order->receiverWard ? $order->receiverWard->parent->label : '',
                    'province' => $order->receiverWard ? $order->receiverWard->parent->parent->label : '',
                    'country' => 'ID'
                ],
            ],
            'parcel_job' => [
                'is_pickup_required' => true,
                'pickup_service_type' => 'Scheduled',
                'pickup_service_level' => 'Premium',
                'delivery_start_date' => Carbon::now()->addDay(),
                'delivery_timeslot' => [
                    'start_time' => '09:00',
                    'end_time' => '22:00',
                    'timezone' => 'Asia/Jakarta'
                ],
                'dimensions' => [
                    'weight' => $order->weight
                ]
            ],
            'items' => $items
        ];
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
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_NIJAVAI];
    }

    /**
     * Test connection
     *
     */
    public function test()
    {

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
            return $this->http->post('jts-phl-order-api/api/order/create', [
                'form_params' => array_merge($this->getProtocolParameters(NIJAVAIConstants::MSG_TYPE_ORDERCREATE, $jsonParams), $jsonParams),
            ]);
        });
        $this->logger->debug('CREATE_ORDER_RESPONSE', $res->getData());

        $success = $res->getData('success');
        if ($success && ($res->getData('responseitems.0.success') !== 'false')) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('responseitems.0.txlogisticid');
            $order->trackingNo = $res->getData('responseitems.0.mailno');
            $order->fee        = 0;
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $jsonParams;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
    }

    /**
     * Get order's stamp url
     *
     * @param Order $order
     * @return string
     * @throws ShippingPartnerException
     */
    public function getOrderStamp(Order $order)
    {
        $token = $this->sendRequest(function () use ($order) {
            return $this->http->post('shiip/public-api/v2/a5/gen-token', ['json' => ['order_codes' => [$order->code]]]);
        })->getData('data.token');

        return "{$this->apiUrl}a5/public-api/printA5?token={$token}";
    }

    /**
     * @param array $waybills
     */
    public function getWaybills(array $waybills)
    {
        $jsonParams = [
            'eccompanyid' => $this->settings['eccompanyid'],
            'customerid' => $this->settings['customerid'],
            'billcode' => implode(',', $waybills)
        ];

        $this->logger->debug('QUERY_WAYBILL', $jsonParams);
    }

    /**
     * Tính phí VC
     * @param Location $senderWard
     * @param Location $receiveWard
     * @param ShippingPartnerSize $shippingPartnerSize
     * @return float|mixed
     */
    public function shippingFee(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize)
    {
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
        $order = new ShippingPartnerOrder();
        return $order;
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
        // TODO: Implement getOrderStampsUrl() method.
    }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     */
    public function getTrackings(array $trackings)
    {
        // TODO: Implement getTrackings() method.
    }
}
