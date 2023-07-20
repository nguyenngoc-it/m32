<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;

class HandleFobizOrderEventJob extends Job
{
    const EVENT_ORDER_CREATED = "ORDER_CREATED";
    const EVENT_ORDER_CHANGE_STATUS = "ORDER_CHANGE_STATUS";
    const EVENT_ORDER_COD_CHANGED = "ORDER_COD_CHANGED";
    const EVENT_ORDER_ADDRESS_CHANGED = "ORDER_ADDRESS_CHANGED";
    const EVENT_ORDER_UPDATE_SKU = "ORDER_UPDATE_SKU";
    const EVENT_ORDER_SHIPPING_PARTNER = "ORDER_UPDATE_SHIPPING_PARTNER";

    public $queue = 'fobiz_order_event';

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * @var Order
     */
    protected $fobizOrder = [];

    /**
     * HandleFobizOrderEventJob constructor.
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @param $message
     * @param array $context
     */
    private function log($message, $context = [])
    {
        if(empty($contex)) {
            $context = $this->payload;
        }

        return LogService::logger('fobiz_order_job')->info($message, $context);
    }

    public function handle()
    {
        $this->log('debug');

        $event        = Arr::get($this->payload, 'event');
        $payload      = Arr::get($this->payload, 'payload', []);
        $this->fobizOrder  = Arr::get($payload, 'order', []);

        if($event == self::EVENT_ORDER_CREATED) {
            return $this->createLocation();
        }


    }

    /**
     * tạo location nếu chưa có
     */
    protected function createLocation()
    {
        $countryFobiz  = Arr::get($this->fobizOrder,'country', []);
        $provinceFobiz = Arr::get($this->fobizOrder,'province', []);
        $cityFobiz     = Arr::get($this->fobizOrder,'city', []);
        $districtFobiz = Arr::get($this->fobizOrder,'district', []);

        $input = [
            "receiver_country_code" => "F".Arr::get($countryFobiz,'id'),
            "receiver_province_code" => "F".Arr::get($provinceFobiz,'id'),
            "receiver_district_code" => "F".Arr::get($cityFobiz,'id'),
            "receiver_ward_code" => "F".Arr::get($districtFobiz,'id'),
        ];

        if(!empty($input['receiver_country_code'])) {
            $country =  Location::query()->firstOrCreate([
                'code' => $input['receiver_country_code'],
                'type' => Location::TYPE_COUNTRY
            ], [
                'label' => Arr::get($countryFobiz,'name'),
                'active' => true,
                'priority' =>1
            ]);
        }

        if(!empty($input['receiver_province_code']) && isset($country) && $country instanceof Location) {
            $province = Location::query()->firstOrCreate([
                'code' => $input['receiver_province_code'],
                'type' => Location::TYPE_PROVINCE
            ], [
                'label' => Arr::get($provinceFobiz,'name'),
                'active' => true,
                'parent_code' => $country->code,
                'priority' =>1
            ]);
        }

        if(!empty($input['receiver_district_code']) && isset($province) && $province instanceof Location) {
            $district = Location::query()->firstOrCreate([
                'code' => $input['receiver_district_code'],
                'type' => Location::TYPE_DISTRICT
            ], [
                'label' => Arr::get($cityFobiz,'name'),
                'active' => true,
                'parent_code' => $province->code,
                'priority' =>1
            ]);
        }

        if(!empty($input['receiver_ward_code']) && isset($district) && $district instanceof Location) {
            Location::query()->firstOrCreate([
                'code' => $input['receiver_ward_code'],
                'type' => Location::TYPE_WARD
            ], [
                'label' => Arr::get($districtFobiz,'name'),
                'active' => true,
                'parent_code' => $district->code,
                'priority' =>1
            ]);
        }

    }
}
