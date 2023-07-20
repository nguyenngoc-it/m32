<?php

namespace Modules\JNEI\Services;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\ShippingPartner\Services\ShippingPartnerSize;

class JNEIShippingPartner implements ShippingPartnerInterface
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'http://apiv2.jne.co.id:10102/';

    /**
     * @var array
     */
    protected $settings = [
        'customer_code' => '',
        'api_key' => '',
        'olshop_branch' => '',
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
        $this->logger   = LogService::logger('jnei');
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        if (!$receiverWardCode = $this->getJneiLocationId($order->receiver_ward_code)) {
            throw new ShippingPartnerException("JNEI: Can not find ward of {$order->receiver_ward_code}");
        }
        $sender               = $this->settings['sender'];
        $jneiSenderWard       = $this->getJneiLoation($sender[ShippingPartner::SENDER_WARD_CODE]);
        $jneiReceiverWard     = $this->getJneiLoation($order->receiver_ward_code);
        $jneiSenderDistrict   = $this->getJneiLoation($sender[ShippingPartner::SENDER_DISTRICT_CODE]);
        $splitReceiverAddress = Helper::splitByChar($order->receiver_address, 90);

        return [
            'username' => $this->settings['username'],
            'api_key' => $this->settings['api_key'],
            'PICKUP_NAME' => '',
            'PICKUP_DATE' => '',
            'PICKUP_TIME' => '',
            'PICKUP_PIC' => '',
            'PICKUP_PIC_PHONE' => '',
            'PICKUP_ADDRESS' => '',
            'PICKUP_DISTRICT' => '',
            'PICKUP_CITY' => '',
            'PICKUP_SERVICE' => '',
            'PICKUP_VEHICLE' => '',
            'BRANCH' => 'CGK000',
            'CUST_ID' => $order->cod ? '11901100' : '11901101',
            'ORDER_ID' => $this->makeReferenceNumber($order),
            'SHIPPER_NAME' => $sender[ShippingPartner::SENDER_NAME],
            'SHIPPER_ADDR1' => $sender[ShippingPartner::SENDER_ADDRESS],
            'SHIPPER_ADDR2' => $sender[ShippingPartner::SENDER_ADDRESS],
            'SHIPPER_CITY' => $jneiSenderDistrict->name,
            'SHIPPER_ZIP' => !empty($jneiSenderWard->meta_data['zip_code']) ? $jneiSenderWard->meta_data['zip_code'] : 'N/A',
            'SHIPPER_REGION' => $sender[ShippingPartner::SENDER_PROVINCE_CODE],
            'SHIPPER_COUNTRY' => 'Indonesia',
            'SHIPPER_CONTACT' => $sender[ShippingPartner::SENDER_PHONE],
            'SHIPPER_PHONE' => $sender[ShippingPartner::SENDER_PHONE],
            'RECEIVER_NAME' => $order->receiver_name,
            'RECEIVER_ADDR1' => !empty($splitReceiverAddress[0]) ? $splitReceiverAddress[0] : '',
            'RECEIVER_ADDR2' => !empty($splitReceiverAddress[1]) ? $splitReceiverAddress[1] : '',
            'RECEIVER_ADDR3' => !empty($splitReceiverAddress[2]) ? $splitReceiverAddress[2] : '',
            'RECEIVER_CITY' => $order->receiverDistrict->label,
            'RECEIVER_ZIP' => $jneiReceiverWard ? $jneiReceiverWard->meta_data['zip_code'] : 'N/A',
            'RECEIVER_COUNTRY' => 'Indonesia',
            'RECEIVER_REGION' => $order->receiverProvince->label,
            'RECEIVER_CONTACT' => $order->receiver_phone,
            'RECEIVER_PHONE' => $order->receiver_phone,
            'ORIGIN_CODE' => 'CGK10000',
            'DESTINATION_CODE' => !empty($jneiReceiverWard->meta_data['dest']) ? $jneiReceiverWard->meta_data['dest'] : 'N/A',
            'SERVICE_CODE' => 'REG',
            'WEIGHT' => ceil($order->weight),
            'QTY' => $order->items()->sum('quantity'),
            'GOODS_DESC' => Service::jnei()->getRemark($order),
            'GOODS_AMOUNT' => round($order->items->sum(function (OrderItem $orderItem) {
                return $orderItem->quantity;
            }), 0),
            'INSURANCE_FLAG' => 'N',
            'MERCHANT_ID' => $sender[ShippingPartner::SENDER_NAME],
            'SPECIAL_INS' => '',
            'TYPE' => 'DROP',
            'COD_FLAG' => $order->cod ? 'YES' : 'N',
            'COD_AMOUNT' => $order->cod,
        ];
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getJneiLocationId($locationCode)
    {
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_JNEI)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? $location->identity : $locationCode;
    }

    /**
     * @param $locationCode
     * @return ShippingPartnerLocation|mixed
     */
    protected function getJneiLoation($locationCode)
    {
        return ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_JNEI)
            ->where('location_code', $locationCode)
            ->first();
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
        return [ShippingPartner::CARRIER_JNEI];
    }

    /**
     * Test connection
     *
     * @throws ShippingPartnerException
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
     * @param Order|null $order
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerException
     */
    public function createOrder(Order $order)
    {
        $jsonParams = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $jsonParams);
        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('pickupcashless', [
                'form_params' => $jsonParams
            ]);
        });
        $this->logger->debug('CREATE_ORDER_RESPONSE', $jsonParams);

        if ($res->getData('status') === 'fail') {
            throw new ShippingPartnerException($res->getData('msg'));
        }

        $data    = $res->getData();
        $success = $res->success();
        if ($success && $detaiData = Arr::get($data, 'detail')) {
            $order             = new ShippingPartnerOrder();
            $order->code       = Arr::get($detaiData, '0.cnote_no');
            $order->trackingNo = Arr::get($detaiData, '0.cnote_no');
            $order->fee        = 0;
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $jsonParams;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
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
     */
    public function getOrderStampsUrl(array $orders)
    {
        return null;
    }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     * @throws ShippingPartnerException
     */
    public function getTrackings(array $trackings)
    {
        $jsonParams   = [
            'username' => $this->settings['username'],
            'api_key' => $this->settings['api_key'],
        ];
        $trackingCode = implode(',', $trackings);
        $this->logger->debug('TRACKING', array_merge($jsonParams, ['tracking' => $trackingCode]));

        $res     = $this->sendRequest(function () use ($jsonParams, $trackingCode) {
            return Helper::quickCurl(config('services.jnei.api_tracking'),
                'tracing/api/list/v1/cnote/' . $trackingCode,
                'post',
                [],
                $jsonParams,
                false
            );
        });
        $resData = $res->getData();
        $this->logger->debug('TRACKING_RESPONSE', $resData);
        $result = [];
        if ($res->success()) {
            $trackingCode = Arr::get($resData, 'cnote.cnote_no');
            $originStatus = Arr::get($resData, 'cnote.pod_code');
            $status       = Service::jnei()->mapStatus($originStatus);
            if ($trackingCode && $originStatus && $status) {
                $tracking = new Tracking($trackingCode, $originStatus, $status);
                $result[] = $tracking;
            }
        }
        return $result;
    }
}
