<?php

namespace Modules\JNTVN\Services;

use Carbon\Carbon;
use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;

class JNTVNShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'http://47.57.106.86/';

    /**
     * @var array
     */
    protected $settings = [
        'eccompanyid' => '',
        'key' => '',
        'customerid' => '',
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
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
        $this->logger   = LogService::logger('jntvn');
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
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        $sender = $this->makeSenderData();
        if (!$receiverWardCode = $this->getJNTVNLocationId($order->receiver_ward_code)) {
            throw new ShippingPartnerException("JNTVN: Can not find ward of {$order->receiver_ward_code}");
        }

        if (!$receiverDistrictCode = $this->getJNTVNLocationId($order->receiver_district_code)) {
            throw new ShippingPartnerException("JNTVN: Can not find district of {$order->receiver_district_code}");
        }

        if (!$receiverProvinceCode = $this->getJNTVNLocationId($order->receiver_province_code)) {
            throw new ShippingPartnerException("JNTVN: Can not find province of {$order->receiver_province_code}");
        }

        if (!$sender['area']) {
            throw new ShippingPartnerException("JNTVN: Can not find ward of {$order->receiver_ward_code}");
        }

        if (!$sender['city']) {
            throw new ShippingPartnerException("JNTVN: Can not find district of {$order->receiver_district_code}");
        }

        if (!$sender['prov']) {
            throw new ShippingPartnerException("JNTVN: Can not find province sender of {$order->receiver_province_code}");
        }

        $items = $order->items
            ->map(function (OrderItem $orderItem) {
                return [
                    'itemname' => $orderItem->name,
                    'englishName' => $orderItem->name,
                    'number' => strval($orderItem->quantity),
                    'itemvalue' => strval($orderItem->price),
                ];
            })
            ->toArray();

        return [
            'eccompanyid' => $this->settings['eccompanyid'],
            'customerid' => $this->settings['customerid'],
            'txlogisticid' => $this->makeReferenceNumber($order),
            "ordertype" => 1,
            "servicetype" => 1,
            "selfAddress" => 1,
            'sender' => $sender,
            'receiver' => [
                'name' => $order->receiver_name,
                'phone' => $order->receiver_phone,
                'mobile' => $order->receiver_phone,
                'prov' => $receiverProvinceCode,
                'city' => $receiverDistrictCode,
                'area' => $receiverWardCode,
                'address' => $order->receiver_address
            ],
            'createordertime' => Carbon::now()->format('Y-m-d H:i:s'),
            'sendstarttime' => Carbon::now()->addHours(1)->format('Y-m-d H:i:s'),
            'sendendtime' => Carbon::now()->addHours(48)->format('Y-m-d H:i:s'),
            'goodsvalue' => strval($order->cod),
            'itemsvalue' => strval($order->cod),
            'isInsured' => "1",
            'items' => $items,
            'paytype' => 'PP_PM',
            'weight' => strval(round($order->weight)), // kg
        ];
    }

    /**
     * @return array
     */
    protected function makeSenderData()
    {
        $sender = $this->settings['sender'];
        $sender = [
            'name' => $sender[ShippingPartner::SENDER_NAME],
            'mobile' => $sender[ShippingPartner::SENDER_PHONE],
            'phone' => $sender[ShippingPartner::SENDER_PHONE],
            'prov' => $this->getJNTVNLocationId($sender[ShippingPartner::SENDER_PROVINCE_CODE]),
            'city' => $this->getJNTVNLocationId($sender[ShippingPartner::SENDER_DISTRICT_CODE]),
            'area' => $this->getJNTVNLocationId($sender[ShippingPartner::SENDER_WARD_CODE]),
            'address' => $sender[ShippingPartner::SENDER_ADDRESS],
        ];

        return $sender;
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getJNTVNLocationId($locationCode)
    {
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_JNTVN)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? $location->code : null;
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
        return [ShippingPartner::CARRIER_JNTVN];
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
            return $this->http->post('yuenan-interface-web/order/orderAction!createOrder.action', [
                'form_params' => array_merge($this->getProtocolParameters(JNTVNConstants::MSG_TYPE_ORDERCREATE, $jsonParams), $jsonParams),
            ]);
        });

        $success = $res->getData('success');
        if ($success) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('responseitems.0.txlogisticid');
            $order->trackingNo = $res->getData('responseitems.0.billcode');
            $order->fee        = $res->getData('responseitems.0.inquiryFee');
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
