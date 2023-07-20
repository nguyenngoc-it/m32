<?php

namespace Modules\JNTM\Services;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class JNTMShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var mixed|string
     */
    protected $apiUrl = 'https://api.jtexpress.my';

    /**
     * @var array
     */
    protected $settings = [
        'key' => '',
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
                'Accept-Language' => 'en'
            ],
        ]);
        $this->logger   = LogService::logger('jntm');
    }

    /**
     * Build Request Param JNTM
     *
     * @param string $params
     * @return false|string
     */
    protected function buildRequestParam(array $params)
    {
        $jsonParams = json_encode($params);
        $key        = data_get($this->settings, 'key');
        $signature  = base64_encode(md5($jsonParams . $key));
        $post       = array(
            'data_param' => $jsonParams,
            'data_sign' => $signature,
        );
        return $post;
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        $sender = $this->makeSenderData();

        $lastOrder    = Order::latest()->first();
        $itemOrders   = $order->items;
        $itemName     = '';
        $totalAmount  = 0;
        $totalProduct = 0;
        $name         = 'product';
        foreach ($itemOrders as $itemOrder) {
            $itemName     = $itemName . $itemOrder->quantity . '/' . $itemOrder->name . ' - ';
            $totalAmount  += doubleval($itemOrder->price) * intval($itemOrder->quantity);
            $totalProduct += intval($itemOrder->quantity);
        }
        if ($itemOrders->count() > 1) {
            $name = 'products';
        }

        $dataReturn = [
            'detail' => [
                'username' => $this->settings['username'],
                'api_key' => $this->settings['api_key'],
                'cuscode' => $this->settings['cuscode'],
                'password' => $this->settings['password'],
                'orderid' => $lastOrder->id,
                'shipper_name' => data_get($sender, 'name'),
                'shipper_addr' => data_get($sender, 'address'),
                'shipper_contact' => data_get($sender, 'name'),
                'shipper_phone' => data_get($sender, 'phone'),
                'sender_zip' => data_get($sender, 'postcode'),
                'receiver_name' => $order->receiver_name,
                'receiver_addr' => $order->receiver_address,
                'receiver_zip' => $order->receiver_postal_code,
                'receiver_phone' => $order->receiver_phone,
                'qty' => '1',
                'weight' => $order->weight,
                'servicetype' => '1',
                'item_name' => $itemName,
//                'item_name' => 'asdasd',
                'goodsdesc' => $totalProduct . '/' . $name,
                'goodsvalue' => $totalAmount,
                'goodsType' => 'PARCEL',
                'payType' => 'PP_PM',
                'offerFeeFlag' => '0',
                'expresstype' => 'EZ',
                'COD' => $order->cod
            ]
        ];
        return $dataReturn;
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
        return [ShippingPartner::PARTNER_JNTM];
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
        $params = $this->buildRequestParam($dataOrder);
        $detail     = $dataOrder['detail'];

        $res = $this->sendRequest(function () use ($params, $detail) {
            return $this->http->post($this->apiUrl . '/blibli/order/createOrder?' . http_build_query($params), [
                    'json' => $detail
                ]
            );
        });
        $this->logger->info('CREATE_ORDER_RESPONSE ' . $order->ref, $res->getData());
        $success = $res->success();
        if ($success) {
            $order             = new ShippingPartnerOrder();
            $order->code       = data_get($res->getData(), 'details.0.data.code');
            $order->trackingNo = data_get($res->getData(), 'details.0.awb_no');
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
        $url = env('JNTM_URL_TEM');

        $resUrls  = [];
        $fileName = 'jntm';
        $oMerger  = PDFMerger::init();

        $guzzle = new Client();
        foreach ($orders as $order) {
            $data = ['form_params' => [
                'logistics_interface' =>
                    '{"account": "' . $this->settings['username'] . '",
                    "password": "' . $this->settings['password_tem'] . '",
                    "customercode": "' . $this->settings['cuscode'] . '",
                    "billcode": "' . $order->tracking_no . '"}',
                'data_digest' => 'any',
                'msg_type' => '1'
            ]];
            $res  = $guzzle->request('POST', $url, $data);
            $oMerger->addString($res->getBody()->getContents(), [1]);
        }

        $oMerger->merge();
        $dataPdf = $oMerger->output();

        $path = "pdf/{$fileName}.$order->tracking_no.pdf";

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
        $key      = $this->settings['key_tracking']; //JTS PARTNER ID
        $jsonData = '{
                    "queryType": 2,
                    "language": "2",
                    "queryCodes":' . json_encode($trackings) . '
                    }';
        $a        = base64_encode(md5($jsonData . $key));
        $post     = array(
            'logistics_interface' => $jsonData,
            'data_digest' => $a,
            'msg_type' => 'TRACK',
            'eccompanyid' => $this->settings['eccompanyid']);
        $this->logger->debug('TRACKING', $post);
        $res = $this->sendRequest(function () use ($post) {
            return $this->http->post("/common/track/trackings?" . http_build_query($post), [
                'headers' => [
                    'Content-Type: application/x-www-form- urlencoded'
                ]
            ]);
        });

        $datas = $res->getData();
        $this->logger->debug('TRACKING_RESPONSE', $datas);
        $result = [];
        if ($res->success() && $responses = data_get($datas, 'responseitems.data')) {
            foreach ($responses as $response) {
                $trackingCode = data_get($response, 'orderId');
                $details      = data_get($response, 'details');
                if ($details) {
                    foreach ($details as $item) {
                        $originStatus = data_get($item, 'scanstatus');
                        $status       = Service::jntm()->mapStatus($originStatus);
                        if ($trackingCode && $originStatus && $status) {
                            $tracking = new Tracking($trackingCode, $originStatus, $status);
                            $result[] = $tracking;
                        }
                        $responses = data_get($datas, 'responseitems.data');
                        $result    = [];
                        foreach ($responses as $response) {
                            $trackingCode = data_get($response, 'orderId');
                            $details      = data_get($response, 'details');
                            if ($details) {
                                foreach ($details as $item) {
                                    $originStatus = data_get($item, 'scanstatus');
                                    $status       = Service::jntm()->mapStatus($originStatus);
                                    if ($trackingCode && $originStatus && $status) {
                                        $tracking = new Tracking($trackingCode, $originStatus, $status);
                                        $result[] = $tracking;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
}
