<?php

namespace Modules\JNTT\Services;

use Carbon\Carbon;
use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
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

class JNTTShippingPartner extends AbstractShippingPartner implements OrderStampRenderable
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://jtpay-uat.jtexpress.co.th/jts-tha-openplatform-api/';

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
        $this->logger   = LogService::logger('jntt');
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
        if (!$receiverDistrictCode = $this->getJNTTLocationId($order->receiver_district_code)) {
            throw new ShippingPartnerException("JNTT: Can not find district of {$order->receiver_district_code}");
        }

        if (!$receiverProvinceCode = $this->getJNTTLocationId($order->receiver_province_code)) {
            throw new ShippingPartnerException("JNTT: Can not find province receiver of {$order->receiver_province_code}");
        }

        if (!$sender['area']) {
            throw new ShippingPartnerException("JNTT: Can not find district of {$order->receiver_district_code}");
        }

        if (!$sender['city']) {
            throw new ShippingPartnerException("JNTT: Can not find province sender of {$order->receiver_province_code}");
        }

        $items              = $order->items->map(function (OrderItem $orderItem) {
            return [
                'itemname' => $orderItem->name,
                'number' => $orderItem->quantity,
                'itemvalue' => round($orderItem->price),
            ];
        });
        $countQuantityItems = $items->sum('number');
        $totalQuantity      = $countQuantityItems ?: 1;

        return [
            'actiontype' => 'add',
            'environment' => 'yes',
            'eccompanyid' => $this->settings['eccompanyid'],
            'customerid' => $this->settings['customerid'],
            'txlogisticid' => $this->makeReferenceNumber($order),
            'ordertype' => '1',
            'servicetype' => '1',
            'deliverytype' => '1',
            'sender' => $sender,
            'receiver' => [
                'name' => $order->receiver_name,
                'mobile' => Helper::correctThailandMobile($order->receiver_phone),
                //'phone' => $order->receiver_phone,
                'city' => $receiverProvinceCode,
                'area' => $receiverDistrictCode,
                'postcode' => '',
                'address' => $order->receiver_address
            ],
            'createordertime' => Carbon::now()->format('Y-m-d H:i:s'),
            'sendstarttime' => Carbon::now()->addHours(1)->format('Y-m-d H:i:s'),
            'sendendtime' => Carbon::now()->addHours(48)->format('Y-m-d H:i:s'),
            'paytype' => '1',
            'weight' => round($order->weight / $totalQuantity, 2), // kg
            'itemsvalue' => round($order->cod),
            'totalquantity' => $totalQuantity,
            'items' => $items->toArray(),
            'isInsured' => 0,
            'remark' => Service::jntt()->getRemark($order),
        ];
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getJNTTLocationId($locationCode)
    {
        /** @var ShippingPartnerLocation $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_JNTT)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? ($location->name_local ?: $location->name) : $locationCode;
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
            //'phone' => $sender[ShippingPartner::SENDER_PHONE],
            'city' => $this->getJNTTLocationId($sender[ShippingPartner::SENDER_PROVINCE_CODE]),
            'area' => $this->getJNTTLocationId($sender[ShippingPartner::SENDER_DISTRICT_CODE]),
            'postcode' => '',
            'address' => $sender[ShippingPartner::SENDER_ADDRESS],
        ];

        return $sender;
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
     * @param $province
     * @param string $district
     * @param string $ward
     * @return RestApiResponse
     * @throws ShippingPartnerException
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
            return $this->http->post('philippines-ifd-web/baseData/findSortingCode.do', [
                'form_params' => array_merge($this->getProtocolParameters(JNTTConstants::MSG_TYPE_SORTINGCODE, $jsonParams), $jsonParams),
            ]);
        });
        return $response->getData('responseitems.0.baseList');
    }

    /**
     * @return RestApiResponse
     * @throws ShippingPartnerException
     */
    public function getDataLocations()
    {
        $jsonParams = [
            'customerid' => $this->settings['customerid'],
        ];
        $response   = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('philippines-ifd-web/baseData/findProvCityAreaList.do', [
                'form_params' => array_merge($this->getProtocolParameters(JNTTConstants::MSG_TYPE_OBTAINPROVCITYAREA, $jsonParams), $jsonParams),
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
        return [ShippingPartner::CARRIER_JNTT];
    }

    /**
     * Test connection
     *
     * @throws ShippingPartnerException
     */
    public function test()
    {
        return $this->getLocations('BENGUET', 'BAGUIO-CITY');
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
            return $this->http->post('api/order/create', [
                'form_params' => array_merge($this->getProtocolParameters(JNTTConstants::MSG_TYPE_ORDERCREATE, $jsonParams), $jsonParams),
            ]);
        });

        $data = $res->getData('responseitems');
        $this->logger->debug('RESPONSE_CREARE_ORDER ' . $order->ref, $data);

        $success = $res->success();
        if ($success && $trackingNo = Arr::get($data, '0.mailno')) {
            $order             = new ShippingPartnerOrder();
            $order->code       = Arr::get($data, '0.txlogisticid');
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
     * Lấy thông tin đơn từ đối tác vận chuyển
     *
     * @param Order $order
     * @return mixed
     * @throws ShippingPartnerException
     */
    public function getOrder(Order $order)
    {
        $jsonParams = [
            'eccompanyid' => $this->settings['eccompanyid'],
            'customerid' => $this->settings['customerid'],
            'command' => 1,
            'serialnumber' => 'ORDER_' . $order->id
        ];

        $this->logger->debug('QUERY_WAYBILL', $jsonParams);

        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('jts-phl-order-api/api/order/queryOrder', [
                'form_params' => array_merge($this->getProtocolParameters(JNTTConstants::MSG_TYPE_ORDERQUERY, $jsonParams), $jsonParams),
            ]);
        });

        $order             = new ShippingPartnerOrder();
        $order->code       = $res->getData('responseitems.0.txlogisticid');
        $order->trackingNo = $res->getData('responseitems.0.mailno');
        $order->status     = $res->getData('responseitems.0.orderStatus');
        $order->fee        = 0;

        return $order;
    }

    /**
     * Render danh sách tem đơn
     *
     * @param Order[] $orders
     * @return Response
     */
    public function renderOrderStamps(array $orders)
    {
        return view('order-stamps/jntt', ['orders' => $orders]);
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
     * @throws ShippingPartnerException
     */
    public function getTrackings(array $trackings)
    {
        $jsonParams = [
            'billcode' => implode(',', $trackings),
            'querytype' => 1,
            'lang' => 'en',
            'customerid' => $this->settings['customerid']
        ];
        $this->logger->debug('TRACKING', $jsonParams);
        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('api/track/trackForJson', [
                'form_params' => array_merge($this->getProtocolParameters(JNTTConstants::MSG_TYPE_TRACKQUERY, $jsonParams), $jsonParams),
            ]);
        });

        $data = $res->getData();
        $this->logger->debug('TRACKING_RESPONSE', $data);
        $result = [];
        if ($res->success() && $responseitems = $res->getData('responseitems', [])) {
            if (!empty($responseitems[0]['tracesList'])) {
                $tracesList = $responseitems[0]['tracesList'];
                foreach ($tracesList as $trace) {
                    $trackingCode = Arr::get($trace, 'billcode');
                    $details = Arr::get($trace, 'details', []);
                    $originStatus = Arr::get(last($details), 'scantype');
                    $status       = Service::jntt()->mapStatus($originStatus);
                    if ($trackingCode && $originStatus && $status) {
                        $tracking = new Tracking($trackingCode, $originStatus, $status);
                        $result[] = $tracking;
                    }
                }
            }
        }
        return $result;
    }
}
