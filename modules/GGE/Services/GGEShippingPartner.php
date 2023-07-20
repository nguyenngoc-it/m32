<?php

namespace Modules\GGE\Services;

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
use Firebase\JWT\JWT;

class GGEShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://api.staging.gogoxpress.com';

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
        $this->settings  = array_merge($this->settings, $settings);
        $this->apiUrl    = $apiUrl ?: $this->apiUrl;
        $this->apiAWBUrl = env('GGE_URL_AWB', 'https://api.staging.quadx.xyz');
        $this->http      = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept-Language' => 'en'
            ],
        ]);
        $this->logger    = LogService::logger('gge');
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
     * Generate sign param GGE
     *
     * @param string $str
     * @return string
     */
    protected function signParam(string $str)
    {
        return strtoupper(hash("sha256", $str));
    }

    /**
     * Build Sign GGE
     *
     * @param array $params
     * @return false|string
     */
    protected function makeSign()
    {
        $key     = data_get($this->settings, 'key');
        $payload = [
            'iat' => time(),
            'jti' => hash('sha256', 'VELA' . time()),
            'sub' => data_get($this->settings, 'mchid'),
            'merchant' => data_get($this->settings, 'merchant'),
            'metadata' => null
        ];
        $jwt     = JWT::encode($payload, $key, 'HS256');

        return $jwt;
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        $sender = $this->makeSenderData();

        if (!$receiverProvince = $this->getGGELocationId($order->receiver_province_code)) {
            throw new ShippingPartnerException("GGE: Can not find district of {$order->receiver_province_code}");
        }

        if (!$receiverCity = $this->getGGELocationId($order->receiver_district_code)) {
            throw new ShippingPartnerException("GGE: Can not find district of {$order->receiver_district_code}");
        }

        if (!$receiverDistrict = $this->getGGELocationId($order->receiver_ward_code)) {
            throw new ShippingPartnerException("GGE: Can not find province sender of {$order->receiver_ward_code}");
        }

        $countQuantityItems = $order->items->sum('quantity');
        $totalQuantity      = $countQuantityItems ?: 1;

        $receiverProvinceName = $receiverProvince->identity;
        $receiverCityName     = $receiverCity->identity;
        $receiverDistrictName = $receiverDistrict->identity;

        $this->logger->debug('DATA_ORDER', $order->toArray());

        // dd($order->toArray(), $sender);

        $items = [];
        if ($order->items) {
            foreach ($order->items as $orderItem) {
                $items[] = [
                    'description' => $orderItem->name,
                    'quantity' => $orderItem->quantity,
                    'amount' => $orderItem->price
                ];
            }
        }

        $receiverPostalCode = $order->receiver_postal_code;
        if (!$receiverPostalCode) {
            $receiverPostalCode = $receiverDistrict->postal_code;
        }

        // Test data
        // $sender['prov']     = 'Metro Manila';
        // $sender['city']     = 'Las Pinas City';
        // $sender['district'] = 'B.F. International Village';

        // $receiverProvinceName = 'Metro Manila';
        // $receiverCityName     = 'Las Pinas City';
        // $receiverDistrictName = 'Almanza Dos';

        $dataJson = '{
            "data":{
                "type":"orders",
                "attributes":{
                    "shipment":"small-pouch",
                    "fees_absorbed_by":"seller",
                    "item_price_absorbed_by":"buyer",
                    "item_protection_value":' . $order->cod . ',
                    "items":' . json_encode($items) . ',
                    "total":' . $totalQuantity . ',
                    "service":"next_day",
                    "payment_method":"cash",
                    "pickup_address":{
                        "email":"' . $sender['email'] . '",
                        "line_1":"' . $sender['address'] . '",
                        "line_2":null,
                        "state":"' . $sender['prov'] . '",
                        "city":"' . $sender['city'] . '",
                        "district":"' . $sender['district'] . '",
                        "postal_code":"' . $sender['postcode'] . '",
                        "name":"' . $sender['name'] . '",
                        "mobile_number":"' . $sender['phone'] . '",
                        "phone_number":"' . $sender['phone'] . '",
                        "country":"PH",
                        "location":null,
                        "region":"MMB"
                    },
                    "delivery_address":{
                        "line_1":"' . $order->receiver_address . '",
                        "line_2":null,
                        "state":"' . $receiverProvinceName . '",
                        "city":"' . $receiverCityName . '",
                        "district":"' . $receiverDistrictName . '",
                        "postal_code":"' . $receiverPostalCode . '",
                        "name":"' . $order->receiver_name . '",
                        "mobile_number":"' . $order->receiver_phone . '",
                        "phone_number":"' . $order->receiver_phone . '",
                        "country":"PH",
                        "location":null,
                        "region":"MMB"
                    }
                }
            }
        }';

        // dd($dataJson);

        return json_decode($dataJson, true);
    }

    /**
     * @param string $locationCode
     * @return ShippingPartnerLocation|null
     */
    protected function getGGELocationId($locationCode)
    {
        $query = [
            'partner_code' => ShippingPartner::PARTNER_GGE,
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

        $receiverProvince     = $this->getGGELocationId($sender[ShippingPartner::SENDER_PROVINCE_CODE]);
        $receiverCity         = $this->getGGELocationId($sender[ShippingPartner::SENDER_DISTRICT_CODE]);
        $receiverWard         = $this->getGGELocationId($sender[ShippingPartner::SENDER_WARD_CODE]);
        $receiverProvinceName = $receiverProvince->identity;
        $receiverCityName     = $receiverCity->identity;
        $receiverWardName     = $receiverWard->identity;

        $sender = [
            'name' => $sender[ShippingPartner::SENDER_NAME],
            'mobile' => $sender[ShippingPartner::SENDER_PHONE],
            'phone' => $sender[ShippingPartner::SENDER_PHONE],
            'email' => $sender[ShippingPartner::SENDER_EMAIL],
            'prov' => $receiverProvinceName,
            'city' => $receiverCityName,
            'district' => $receiverWardName,
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
        return [ShippingPartner::CARRIER_GGE];
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
     * @param Order|null $order
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerException
     */
    public function createOrder(Order $order)
    {
        $dataOrder = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $dataOrder);
        $token = $this->makeSign();
        $headers = [
            'Authorization' => 'Bearer ' . $token
        ];

        $res = $this->sendRequest(function () use ($dataOrder, $headers) {
            return $this->http->post('v1/book', ['headers' => $headers, 'json' => $dataOrder]);
        });
        $this->logger->debug('CREATE_ORDER_RESPONSE', $res->getData());

        $success = $res->getData('success');
        if ($success) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('data.attributes.tpl_tracking_number');
            $order->trackingNo = $res->getData('data.id');
            $order->fee        = 0;
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $dataOrder;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
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
        $headers = [
            "Accept" => "application/pdf, */*"
        ];

        $resUrls = [];
        $token   = $this->makeSign();

        // dd($token);
        $listWayBill = [];

        foreach ($orders as $order) {
            $listWayBill[] = $order->tracking_no;
        }

        $dataWayBills = [
            'tracking_number' => $listWayBill
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $token
        ];

        $this->logger->debug('QUERY_WAYBILL', ['data_input' => ['list_waybill' => $dataWayBills]]);

        $oMerger = PDFMerger::init();

        foreach ($listWayBill as $wayBill) {
            $res     = $this->sendRequest(function () use ($headers, $wayBill) {
                return $this->http->get($this->apiAWBUrl . '/v2/print/awb/' . $wayBill . '?type=zebra', ['headers' => $headers]);
            });
            $dataRes = $res->getData();
            $oMerger->addString(file_get_contents($dataRes['message']['awb']), [1]);
            // $resUrls[] = $dataRes['message']['awb'];
        }

        $fileName = hash('sha256', time());

        $oMerger->merge();

        $dataPdf = $oMerger->output();

        $path = "pdf/" . ShippingPartner::CARRIER_GGE . "_{$fileName}.pdf";

        if (Storage::put($path, $dataPdf)) {
            $resUrls[] = Storage::url($path);
        }

        // $res = $this->sendRequest(function () use ($headers, $dataWayBills) {
        //     return $this->http->post($this->apiAWBUrl . '/v2/print/awb?type=zebra', ['headers' => $headers, 'json' => $dataWayBills]);
        // });

        // $idAwb = $res->getData('id');

        // $resAwb = $this->sendRequest(function () use ($headers, $idAwb) {
        //     return $this->http->get($this->apiAWBUrl . "/v2/print/awb/poll/" . $idAwb, ['headers' => $headers]);
        // });

        // $dataAwbs = collect($resAwb->getData());

        // $this->logger->debug('QUERY_WAYBILL', ['data_awb' => $resAwb->getData()]);

        // if ($dataAwbs) {
        //     foreach ($dataAwbs as $dataAwb) {
        //         $resUrls[] = $dataAwb['url'];
        //     }
        // }

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
        return view('order-stamps/GGE', ['orders' => $orders]);
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
        $this->logger->debug('TRACKING', [$trackings]);

        $result = [];

        if ($trackings) {
            foreach ($trackings as $trackingNumber) {

                $token = $this->makeSign();

                $headers = [
                    'Authorization' => 'Bearer ' . $token
                ];
                $res     = $this->sendRequest(function () use ($trackingNumber, $headers) {
                    return $this->http->get("v1/orders/{$trackingNumber}", ['headers' => $headers]);
                });
                $this->logger->debug('RESPONSE', $res->getData());
                if ($res->success()) {
                    $trace        = $res->getData('data', []);
                    $trackingCode = data_get($trace, 'attributes.tracking_number');
                    $originStatus = data_get($trace, 'attributes.status');
                    $status       = Service::gge()->mapStatus($originStatus);
                    if ($trackingCode && $originStatus && $status) {
                        $tracking = new Tracking($trackingCode, $originStatus, $status);
                        $result[] = $tracking;
                    }
                }
            }
        }

        return $result;
    }

    protected function base64UrlEncode(string $data): string
    {
        $base64Url = base64_encode($data);

        return $base64Url;
    }
}
