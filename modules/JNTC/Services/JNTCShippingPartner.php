<?php

namespace Modules\JNTC\Services;

use Carbon\Carbon;
use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Laravel\Lumen\Application;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\OrderStampRenderable;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;

class JNTCShippingPartner extends AbstractShippingPartner implements OrderStampRenderable
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'http://47.57.86.134/jandt-khm-api/api/';

    /**
     * @var array
     */
    protected $settings = [
        'eccompanyid' => '',
        'data_digest' => '',
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
        $this->logger   = LogService::logger('jntc');
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
        return [
            'logistics_interface' => json_encode($jsonParams),
            'data_digest' => trim($this->settings['data_digest']),
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
        if (!$receiverWard = $this->getJNTCLocation($order->receiver_ward_code)) {
            throw new ShippingPartnerException("JNTC: Can not find ward of $order->receiver_ward_code");
        }

        if (!$receiverDistrict = $this->getJNTCLocation($order->receiver_district_code)) {
            throw new ShippingPartnerException("JNTC: Can not find district of $order->receiver_district_code");
        }

        if (!$receiverProvince = $this->getJNTCLocation($order->receiver_province_code)) {
            throw new ShippingPartnerException("JNTC: Can not find province receiver of $order->receiver_province_code");
        }

        $sender = $this->makeSenderData();
        foreach (['prov', 'city', 'area', 'address', 'mobile', 'name'] as $s) {
            if (empty($sender[$s])) {
                throw new ShippingPartnerException("JNTC: Can not find sender ".$s);
            }
        }

        $items  = $order->items->map(function (OrderItem $orderItem) {
            return [
                'itemname' => $orderItem->name,
                'number' => $orderItem->quantity,
                'itemvalue' => round($orderItem->price),
            ];
        });

        $countQuantityItems = $order->items->sum('quantity');
        $totalQuantity      = $countQuantityItems ?: 1;

        return [
            'eccompanyid' => $this->settings['eccompanyid'],
            'customerid' => $this->settings['customerid'],
            'txlogisticid' => $this->makeReferenceNumber($order),
            'ordertype' => '1',
            'servicetype' => '1',
            'deliverytype' => '1',
            'sender' => $sender,
            'receiver' => [
                'name' => $order->receiver_name,
                'mobile' => $order->receiver_phone,
                'phone' => $order->receiver_phone,
                'prov' => $receiverProvince,
                'city' => $receiverDistrict,
                'area' => $receiverWard,
                'address' => $order->receiver_address
            ],
            'createordertime' => Carbon::now()->format('Y-m-d H:i:s'),
            'sendstarttime' => Carbon::now()->addHours()->format('Y-m-d H:i:s'),
            'sendendtime' => Carbon::now()->addHours(48)->format('Y-m-d H:i:s'),
            'paytype' => '1',
            'weight' => round($order->weight, 2), // kg
            'itemsvalue' => $order->cod,
            'totalquantity' => $totalQuantity,
            'items' => $items,
            "goodsvalue" => "",
            'remark' => Service::jntc()->getRemark($order),
        ];
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getJNTCLocation($locationCode)
    {
        if(empty($locationCode)) {
            return '';
        }

        /** @var ShippingPartnerLocation $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_JNTC)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? ($location->name) : $locationCode;
    }

    /**
     * @return array
     */
    protected function makeSenderData()
    {
        $sender = $this->settings['sender'];
        return [
            'name' => $sender[ShippingPartner::SENDER_NAME],
            'mobile' => $sender[ShippingPartner::SENDER_PHONE],
            'phone' => $sender[ShippingPartner::SENDER_PHONE],
            'prov' => $sender[ShippingPartner::SENDER_PROVINCE_CODE],
            'city' => $sender[ShippingPartner::SENDER_DISTRICT_CODE],
            'area' => $sender[ShippingPartner::SENDER_WARD_CODE],
            'postcode' => '',
            'address' => $sender[ShippingPartner::SENDER_ADDRESS],
        ];
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
     * @param $province
     * @param string $district
     * @param string $ward
     * @return RestApiResponse
     */
    public function getLocations($province, $district, $ward = '')
    {
        $jsonParams = [
            'customerid' => $this->settings['customerid'],
            'province' => $province,
            'city' => $district,
            'area' => $ward,
            'town' => ''
        ];
        $response   = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('baseData/findAllProvincesAndCities.do', [
                'form_params' => array_merge($this->getProtocolParameters(JNTCConstants::MSG_TYPE_OBTAINPROVCITYAREA, $jsonParams), $jsonParams),
            ]);
        });
        return $response->getData('responseitems.0.baseList');
    }

    /**
     * @return RestApiResponse
     */
    public function getDataLocations()
    {
        $jsonParams = [
            'customerid' => $this->settings['customerid'],
        ];
        $response   = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('baseData/findAllProvincesAndCities.do', [
                'form_params' => array_merge($this->getProtocolParameters(JNTCConstants::MSG_TYPE_OBTAINPROVCITYAREA, $jsonParams), $jsonParams),
            ]);
        });
        return $response->getData('responseitems.0.baseList');
    }

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_JNTC];
    }

    /**
     * Test connection
     *
     */
    public function test()
    {
        return $this->getLocations('BENGUET', 'BAGUIO-CITY');
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
            return $this->http->post('order/createOrder.do', [
                'form_params' => $this->getProtocolParameters(JNTCConstants::MSG_TYPE_ORDERCREATE, $jsonParams),
            ]);
        });
        $this->logger->debug('CREATE_ORDER_RESPONSE', ['order' => $order->ref, 'res' => $res->getData()]);

        $success = $res->success();
        if ($success && $trackingNo = $res->getData('responseitems.0.mailno')) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('responseitems.0.txlogisticid');
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
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking
     */
    public function getTrackings(array $trackings)
    {
        if(!isset($trackings[0])) {
            return null;
        }

        $jsonParams = [
            'billCode' => $trackings[0],
            'lang' => 'en'
        ];
        $this->logger->debug('TRACKING', $jsonParams);

        $formParams = $this->getProtocolParameters(JNTCConstants::MSG_TYPE_TRACKQUERY, $jsonParams);
        $res = $this->sendRequest(function () use ($formParams) {
            return $this->http->post('track/orderTrack.do', [
                'form_params' => $formParams,
            ]);
        });
        $this->logger->info('RESPONSE_TRACKING', ['res' => $res->getData(), 'params' => $jsonParams]);

        $tracking = null;
        if ($res->success() && $tracesList = $res->getData('responseitems.details', [])) {
                $trackingCode = $res->getData('billcode');
                $originStatus = $res->getData('responseitems.details.0.scantype', '');
                $originStatus = trim(strtolower($originStatus));
                $status       = Service::jntc()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                }
        }

        return $tracking;
    }


    /**
     * @param array $orders
     * @return \Illuminate\Http\Response|View
     */
    public function renderOrderStamps(array $orders)
    {
        return view('order-stamps/jntc', ['orders' => $orders]);
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
}
