<?php

namespace App\Jobs;

use Gobiz\Log\LogService;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class AddLocationJob extends \App\Base\Job
{
    /**
     * @var string
     */
    public $queue = 'maplocation';

    protected $shippingPartnerCode;

    protected $logger = null;

    public function __construct(string $shippingPartnerCode)
    {
        $this->shippingPartnerCode = $shippingPartnerCode;
    }

    public function handle()
    {
        switch ($this->shippingPartnerCode) {
            case ShippingPartnerLocation::SHIPPING_PARTNER_FLASH:
                $filePath = storage_path('Flash_Master_Address 20220526.xlsx');
                break;
            case ShippingPartnerLocation::SHIPPING_PARTNER_JNTT:
                $filePath = storage_path('J_T_THAI_ENG_ADDRESS.xlsx');
                break;
            default:
                $filePath = storage_path('Flash_Master_Address 20220526.xlsx');
        }
        $items = (new FastExcel())->import($filePath);
        foreach ($items as $item) {
            $this->updateShippingPartnerLocation($item, $this->shippingPartnerCode);
        }
    }

    protected function logger()
    {
        if ($this->logger == null) {
            $this->logger = LogService::logger('add location');
        }
        return $this->logger;
    }

    /** thêm địa chỉ = chữ Thái Lan
     * @param $item
     * @param $shippingPartnerCode
     * @return void
     */
    public function updateShippingPartnerLocation($item, $shippingPartnerCode)
    {
        $provinceShippingPartner = ShippingPartnerLocation::query()->select('shipping_partner_locations.*')
            ->where('partner_code', $shippingPartnerCode)
            ->where('type', 'PROVINCE')
            ->where('name', $item['province_en_name'])
            ->first();
        if ($provinceShippingPartner) {
            $provinceShippingPartner->name_local = $item['province_name'];
            $provinceShippingPartner->save();
            $districtShippingPartner = ShippingPartnerLocation::query()->select('shipping_partner_locations.*')
                ->where('partner_code', $shippingPartnerCode)
                ->where('type', 'DISTRICT')
                ->where('name', $item['city_en_name'])
                ->where('parent_location_code', $provinceShippingPartner->location_code)
                ->first();
            if ($districtShippingPartner) {
                $districtShippingPartner->name_local = $item['city_name'];
                $districtShippingPartner->save();
            } else {
                $this->logger()->info($shippingPartnerCode . '- district error ' . $item['city_en_name']);
            }
        } else {
            $this->logger()->info($shippingPartnerCode . '- province error ' . $item['province_en_name']);
        }
    }
}
