<?php

namespace Modules\JNTP\Services;

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
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\OrderStampRenderable;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;

class JNTPShippingPartner extends AbstractShippingPartner implements OrderStampRenderable
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://test-api.jtexpress.ph/';

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
        $this->logger   = LogService::logger('jntp');
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
        if (!$receiverWardCode = $this->getJNTPLocationId($order->receiver_ward_code)) {
            throw new ShippingPartnerException("JNTP: Can not find ward of $order->receiver_ward_code");
        }

        if (!$receiverDistrictCode = $this->getJNTPLocationId($order->receiver_district_code)) {
            throw new ShippingPartnerException("JNTP: Can not find district of $order->receiver_district_code");
        }

        if (!$receiverProvinceCode = $this->getJNTPLocationId($order->receiver_province_code)) {
            throw new ShippingPartnerException("JNTP: Can not find province receiver of $order->receiver_province_code");
        }

        if (!$sender['area']) {
            throw new ShippingPartnerException("JNTP: Can not find ward of $order->receiver_ward_code");
        }

        if (!$sender['city']) {
            throw new ShippingPartnerException("JNTP: Can not find district of $order->receiver_district_code");
        }

        if (!$sender['prov']) {
            throw new ShippingPartnerException("JNTP: Can not find province sender of $order->receiver_province_code");
        }

        $items              = [
            [
                'itemname' => $order->ref,
                'number' => 1,
                'itemvalue' => $order->cod
            ]
        ];
        $countQuantityItems = $order->items->sum('quantity');
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
                'mobile' => $order->receiver_phone,
                'phone' => $order->receiver_phone,
                'prov' => $receiverProvinceCode,
                'city' => $receiverDistrictCode,
                'area' => $receiverWardCode,
                'postcode' => '',
                'address' => $order->receiver_address
            ],
            'createordertime' => Carbon::now()->format('Y-m-d H:i:s'),
            'sendstarttime' => Carbon::now()->addHours()->format('Y-m-d H:i:s'),
            'sendendtime' => Carbon::now()->addHours(48)->format('Y-m-d H:i:s'),
            'paytype' => '1',
            'weight' => round($order->weight / $totalQuantity, 2), // kg
            'itemsvalue' => $order->cod,
            'totalquantity' => $totalQuantity,
            'items' => $items,
            'isInsured' => 0,
            'remark' => Service::jntp()->getRemark($order),
        ];
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getJNTPLocationId($locationCode)
    {
        /** @var ShippingPartnerLocation $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_JNTP)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? ($location->identity ?: $location->name) : $locationCode;
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
            'prov' => $this->getJNTPLocationId($sender[ShippingPartner::SENDER_PROVINCE_CODE]),
            'city' => $this->getJNTPLocationId($sender[ShippingPartner::SENDER_DISTRICT_CODE]),
            'area' => $this->getJNTPLocationId($sender[ShippingPartner::SENDER_WARD_CODE]),
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
            return $this->http->post('philippines-ifd-web/baseData/findSortingCode.do', [
                'form_params' => array_merge($this->getProtocolParameters(JNTPConstants::MSG_TYPE_SORTINGCODE, $jsonParams), $jsonParams),
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
            return $this->http->post('philippines-ifd-web/baseData/findProvCityAreaList.do', [
                'form_params' => array_merge($this->getProtocolParameters(JNTPConstants::MSG_TYPE_OBTAINPROVCITYAREA, $jsonParams), $jsonParams),
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
        return [ShippingPartner::CARRIER_JNTP];
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
            return $this->http->post('jts-phl-order-api/api/order/create', [
                'form_params' => array_merge($this->getProtocolParameters(JNTPConstants::MSG_TYPE_ORDERCREATE, $jsonParams), $jsonParams),
            ]);
        });

        $this->logger->debug('CREATE_ORDER_RESPONSE ' . $order->ref, $res->getData());
        $success = $res->success();
        if ($success && $trackingNo = $res->getData('responseitems.0.mailno')) {
            $order              = new ShippingPartnerOrder();
            $order->code        = $res->getData('responseitems.0.txlogisticid');
            $order->trackingNo  = $trackingNo;
            $order->fee         = 0;
            $order->sender      = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query       = $jsonParams;
            $order->response    = (array)$res->getData();
            $order->sortingCode = $res->getData('responseitems.0.sortingcode');
            $order->sortingNo   = $res->getData('responseitems.0.sortingNo');
            return $order;
        }
        return null;
    }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     */
    public function getTrackings(array $trackings)
    {
        $jsonParams = [
            'billcode' => implode(',', $trackings),
            'lang' => 'en'
        ];
        $formParams = $this->getProtocolParameters(JNTPConstants::MSG_TYPE_TRACKQUERY, $jsonParams);
        $this->logger->debug('TRACKING', $formParams);
        $res = $this->sendRequest(function () use ($formParams) {
            return $this->http->post('jts-phl-order-api/api/track/trackForJson', [
                'form_params' => $formParams,
            ]);
        });
        $this->logger->info('RESPONSE_TRACKING', $res->getData());

        $result = [];
        if ($res->success() && $tracesList = $res->getData('responseitems', [])) {
            foreach ($tracesList as $trace) {
                $trackingCode = Arr::get($trace, 'billcode');
                $originStatus = Arr::get($trace, 'details.0.scantype');
                $status       = Service::jntp()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                    $result[] = $tracking;
                }
            }
        }
        return $result;
    }


    /**
     * Render danh sách tem đơn
     *
     * @param Order[] $orders
     * @return View|Application
     */
    public function renderOrderStamps(array $orders)
    {
        return view('order-stamps/jntp', ['orders' => $orders]);
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
