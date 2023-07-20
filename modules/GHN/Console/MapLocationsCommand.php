<?php

namespace Modules\GHN\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;

class MapLocationsCommand extends Command
{
    protected $signature = 'ghn:map-locations';
    protected $description = 'Mapping GHN locations with system locations';
    /**
     * @var ShippingPartnerInterface $api
     */
    protected $api;

    public function handle()
    {
        $shippingPartner = ShippingPartner::query()
            ->where('code', ShippingPartner::PARTNER_GHN)
            ->first();

        if (!$shippingPartner instanceof ShippingPartner) {
            $this->error("Can't found GHN shipping partner");
            return;
        }

        $this->api    = $shippingPartner->partner();
        $ghnProvinces = $this->api->getProvinces();
        $this->mapProvinces($ghnProvinces);
    }

    private function mapProvinces(array $ghnProvinces)
    {
        foreach ($ghnProvinces as $ghnProvince) {
            $shippingPartnerLocation = ShippingPartnerLocation::query()
                ->where('partner_code', ShippingPartner::PARTNER_GHN)
                ->whereNull('identity')
                ->where('name', 'like', '%' . $this->gexMatchSearch($ghnProvince['ProvinceName']) . '%')
                ->where('type', Location::TYPE_PROVINCE)->first();
            if ($shippingPartnerLocation instanceof ShippingPartnerLocation) {
                DB::transaction(function () use ($shippingPartnerLocation, $ghnProvince) {
                    $shippingPartnerLocation->identity = $ghnProvince['ProvinceID'];
                    $shippingPartnerLocation->code     = $ghnProvince['Code'];
                    $shippingPartnerLocation->name     = $ghnProvince['ProvinceName'];
                    $shippingPartnerLocation->save();
                    $this->info('mapped for ' . $shippingPartnerLocation->name);
                    $ghnDistricts = $this->api->getDistricts($ghnProvince['ProvinceID']);
                    foreach ($ghnDistricts as $ghnDistrict) {
                        $shippingPartnerLocationDistrict = ShippingPartnerLocation::query()
                            ->where('partner_code', ShippingPartner::PARTNER_GHN)
                            ->where('parent_location_code', $shippingPartnerLocation->location_code)
                            ->whereNull('identity')
                            ->where('name', 'like', '%' . $this->gexMatchSearch($ghnDistrict['DistrictName']) . '%')
                            ->where('type', Location::TYPE_DISTRICT)->first();
                        if ($shippingPartnerLocationDistrict instanceof ShippingPartnerLocation) {
                            $shippingPartnerLocationDistrict->identity = $ghnDistrict['DistrictID'];
                            $shippingPartnerLocationDistrict->code     = $ghnDistrict['Code'];
                            $shippingPartnerLocationDistrict->name     = $ghnDistrict['DistrictName'];
                            $shippingPartnerLocationDistrict->save();
                            $this->info('mapped for district ' . $shippingPartnerLocationDistrict->name);
                            $ghnWards = (array)$this->api->getWards($ghnDistrict['DistrictID']);
                            foreach ($ghnWards as $ghnWard) {
                                $shippingPartnerLocationWard = ShippingPartnerLocation::query()
                                    ->where('partner_code', ShippingPartner::PARTNER_GHN)
                                    ->where('parent_location_code', $shippingPartnerLocationDistrict->location_code)
                                    ->whereNull('identity')
                                    ->where('name', 'like', '%' . $this->gexMatchSearch($ghnWard['WardName']) . '%')
                                    ->where('type', Location::TYPE_WARD)->first();
                                if ($shippingPartnerLocationWard instanceof ShippingPartnerLocation) {
                                    $shippingPartnerLocationWard->identity = $ghnWard['WardCode'];
                                    $shippingPartnerLocationWard->code     = $ghnWard['WardCode'];
                                    $shippingPartnerLocationWard->name     = $ghnWard['WardName'];
                                    $shippingPartnerLocationWard->save();
                                    $this->info('mapped for ward ' . $shippingPartnerLocationWard->name);
                                }
                            }
                        }
                    }
                });
            }
        }
    }

    private function gexMatchSearch($search)
    {
        $arr    = ['huyện đảo', 'huyện', 'thị xã', 'xã'];
        $search = strtolower($search);
        foreach ($arr as $item) {
            $search = str_replace($item, '', $search);
        }
        return trim($search);
    }
}
