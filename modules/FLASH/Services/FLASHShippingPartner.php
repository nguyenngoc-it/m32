<?php

namespace Modules\FLASH\Services;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class FLASHShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://open-api-tra.flashexpress.com';

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
                'Accept-Language' => 'th'
            ],
        ]);
        $this->logger   = LogService::logger('flash');
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
     * Generate sign param FLASH
     *
     * @param string $str
     * @return string
     */
    protected function signParam(string $str)
    {
        return strtoupper(hash("sha256", $str));
    }

    /**
     * Build Request Param FLASH
     *
     * @param array $params
     * @return false|string
     */
    protected function buildRequestParam(array $params)
    {
        $sign       = '';
        $merchantPW = data_get($this->settings, 'key');

        $params['mchId']    = data_get($this->settings, 'mchid');
        $params['nonceStr'] = time();

        ksort($params);
        foreach ($params as $k => $v) {
            if ((($v != null) || $v === 0) && ($k != 'sign')) {
                $sign .= $k . '=' . $v . '&';
            } else {
                unset($params[$k]);
            }
        }
        $sign .= "key=" . $merchantPW;

        $params['sign'] = $this->signParam($sign);

        $requestStr = '';
        foreach ($params as $k => $v) {
            $requestStr .= $k . "=" . urlencode($v) . '&';
        }
        return substr($requestStr, 0, -1);
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        $sender = $this->makeSenderData();

        if (!$receiverDistrict = $this->getFLASHLocationId($order->receiver_district_code)) {
            throw new ShippingPartnerException("FLASH: Can not find district of {$order->receiver_district_code}");
        }

        if (!$sender['city']) {
            throw new ShippingPartnerException("FLASH: Can not find district of {$order->receiver_district_code}");
        }

        if (!$sender['prov']) {
            throw new ShippingPartnerException("FLASH: Can not find province sender of {$order->receiver_province_code}");
        }

        $countQuantityItems = $order->items->sum('quantity');
        $totalQuantity      = $countQuantityItems ?: 1;
        $insureDeclareValue = (int)($order->cod) * 100;
        $codEnabled         = 0;
        if ($insureDeclareValue) {
            $codEnabled = 1;
        }

        $dstPostalCode = $receiverDistrict->postal_code;
        $explode       = explode(',', $dstPostalCode);

        if (isset($explode[0])) {
            $dstPostalCode = $explode[0];
        }

        $receiverDistrictName = $receiverDistrict->name_local ? : $receiverDistrict->identity;

        // Lấy thông tin province từ district
        $receiverProvinceName = '';
        $province             = ShippingPartnerLocation::query()->where(['location_code' => $receiverDistrict->parent_location_code])->get()->first();
        if ($province) {
            $receiverProvinceName = $province->name_local ? : $province->identity;
        }

        $this->logger->debug('DATA_ORDER', $order->toArray());

        $data = [
            "outTradeNo" => time(),
            "expressCategory" => "1",
            "srcName" => data_get($sender, 'name'),
            "srcPhone" => data_get($sender, 'phone'),
            "srcProvinceName" => data_get($sender, 'prov'),
            "srcCityName" => data_get($sender, 'city'),
            "srcPostalCode" => data_get($sender, 'postcode'),
            "srcDetailAddress" => data_get($sender, 'address'),
            "dstName" => $order->receiver_name,
            "dstPhone" => $order->receiver_phone,
            "dstProvinceName" => $receiverProvinceName,
            "dstCityName" => $receiverDistrictName,
            "dstPostalCode" => $dstPostalCode,
            "dstDetailAddress" => $order->receiver_address,
            "articleCategory" => "1",
            "weight" => (round($order->weight / $totalQuantity, 2)) * 1000, // gr
            "insured" => "0",
            "codEnabled" => $codEnabled,
            "codAmount" => $insureDeclareValue,
        ];

        return $data;
    }

    /**
     * @param string $locationCode
     * @return ShippingPartnerLocation|null
     */
    protected function getFLASHLocationId($locationCode)
    {
        $query = [
            'partner_code' => ShippingPartner::PARTNER_FLASH,
            'location_code' => $locationCode
        ];
        /** @var ShippingPartnerLocation $location */
        $location = ShippingPartnerLocation::query()
            ->where($query)
            ->first();

        // $locationLabel =  '';
        // if ($location) {
        //     $locationLabel = $location->identity ? $location->identity : $location->name;
        // }
        // return $locationLabel ? $locationLabel : $locationCode;
        return $location;
    }

    /**
     * @return array
     */
    protected function makeSenderData()
    {
        $sender = $this->settings['sender'];

        $receiverProvince     = $this->getFLASHLocationId($sender[ShippingPartner::SENDER_PROVINCE_CODE]);
        $receiverCity         = $this->getFLASHLocationId($sender[ShippingPartner::SENDER_DISTRICT_CODE]);
        $receiverProvinceName = $receiverProvince->name_local ? : $receiverProvince->identity;
        $receiverCityName     = $receiverCity->name_local ? : $receiverCity->identity;
        $sender               = [
            'name' => $sender[ShippingPartner::SENDER_NAME],
            'mobile' => $sender[ShippingPartner::SENDER_PHONE],
            'phone' => $sender[ShippingPartner::SENDER_PHONE],
            'prov' => $receiverProvinceName,
            'city' => $receiverCityName,
            'postcode' => $sender[ShippingPartner::SENDER_POSTAL_CODE],
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
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_FLASH];
    }

    /**
     * Test connection
     *
     * @return string
     */
    public function test()
    {
        return 'Aloha';
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
        $dataOrder = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $dataOrder);
        $jsonParams = $this->buildRequestParam($dataOrder);

        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('open/v3/orders?' . $jsonParams);
        });

        $this->logger->debug('CREATE_RESPONSE_ORDER ' . $order->ref, $res->getData());

        $success = $res->success();

        if ($success) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('data.outTradeNo');
            $order->trackingNo = $res->getData('data.pno');
            $order->fee        = 0;
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $dataOrder;
            $order->response   = (array)$res->getData();
            return $order;
        }
    }

    /**
     * Lấy url tem của danh sách đơn
     *
     * @param Order[] $orders
     * @return array
     * @throws ShippingPartnerException
     */
    public function getOrderStampsUrl(array $orders)
    {
        $jsonParams = $this->buildRequestParam([]);
        $headers    = [
            "Accept" => "application/pdf, */*"
        ];

        $resUrls  = [];
        $fileName = '';
        $oMerger  = PDFMerger::init();

        $data = [];
        foreach ($orders as $order) {
            $data = [
                'params' => $jsonParams,
                'order' => $order,
                'headers' => $headers
            ];

            $res = $this->sendRequest(function () use ($data) {
                return $this->http->post("open/v1/orders/{$data['order']->tracking_no}/pre_print?{$data['params']}", ['headers' => $data['headers']]);
            });

            $fileName .= "_" . $data['order']->id;
            $oMerger->addString($res->getBody(), [1]);
        }

        $fileName = md5($fileName);

        $oMerger->merge();

        $dataPdf = $oMerger->output();

        $path = "pdf/{$data['order']->shipping_partner_code}_{$fileName}.pdf";

        if (Storage::put($path, $dataPdf)) {
            $resUrls[] = Storage::url($path);
        }

        return $resUrls;
    }

    /**
     * Đăng ký webhook
     *
     * @param string $webhookUrl
     * @return void
     * @throws ShippingPartnerException
     */
    public function webhookRegister(string $webhookUrl)
    {
        $params     = [
            'serviceCategory' => 1,
            'url' => $webhookUrl,
            'webhookApiCode' => 0 // web hook status
        ];
        $jsonParams = $this->buildRequestParam($params);

        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post("/open/v1/setting/web_hook_service?{$jsonParams}");
        });

        if ($res->getData('code') != 1) {
            throw new ShippingPartnerException($res->getData('message'));
        }

        return $res->getData('data');
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
        $jsonParams = $this->buildRequestParam([]);

        $data = [
            'params' => $jsonParams,
            'tracking_no' => $order->tracking_no
        ];

        $res = $this->sendRequest(function () use ($data) {
            return $this->http->post("open/v1/orders/{$data['tracking_no']}/routes?{$data['params']}");
        });

        if ($res->getData('code') != 1) {
            throw new ShippingPartnerException($res->getData('message'));
        }

        $orderRes             = new ShippingPartnerOrder();
        $orderRes->code       = $order->code;
        $orderRes->trackingNo = $res->getData('data.pno');
        $orderRes->status     = $res->getData('data.state');
        $orderRes->fee        = 0;

        return $orderRes;
    }

    /**
     * Render danh sách tem đơn
     *
     * @param Order[] $orders
     * @return Response
     */
    public function renderOrderStamps(array $orders)
    {
        return view('order-stamps/FLASH', ['orders' => $orders]);
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
        $jsonParams = $this->buildRequestParam(['pnos' => implode(',', $trackings)]);
        $this->logger->debug('TRACKING', [$jsonParams]);
        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post("/open/v1/orders/routesBatch?$jsonParams");
        });
        $this->logger->debug('TRACKING_RESPONSE', $res->getData());

        $result = [];
        if ($res->success() && $res->getData('code') == 1) {
            $tracesList = $res->getData('data', []);
            foreach ($tracesList as $trace) {
                $trackingCode = Arr::get($trace, 'pno');
                $originStatus = Arr::get($trace, 'state');
                $status       = Service::flash()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                    $result[] = $tracking;
                }
            }
        }
        return $result;
    }
}
