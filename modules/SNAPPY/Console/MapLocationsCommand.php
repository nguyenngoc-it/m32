<?php

namespace Modules\SNAPPY\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;

class MapLocationsCommand extends Command
{
    protected $signature = 'snappy:map-locations';

    protected $description = 'Mapping Snappy locations with system locations';
    /**
     * @var ShippingPartnerInterface $api
     */
    protected $api;

    public function handle()
    {
        $shippingPartner = ShippingPartner::query()
            ->where('code', ShippingPartner::PARTNER_SNAPPY)
            ->first();

        if (!$shippingPartner instanceof ShippingPartner) {
            $this->error("Can't found SNAPPY shipping partner");
            return;
        }

        $this->api    = $shippingPartner->partner();
        $snappyProvinces = $this->api->getProvinces();
        $this->mapProvinces($snappyProvinces);
    }

    private function mapProvinces(array $snappyProvinces)
    {
        foreach ($snappyProvinces as $snappyProvince) {
            $shippingPartnerLocation = ShippingPartnerLocation::query()
                ->where('partner_code', ShippingPartner::PARTNER_SNAPPY)
                ->whereNull('identity')
                ->where('name', 'like', '%' . $this->gexMatchSearch($snappyProvince['name']) . '%')
                ->where('type', Location::TYPE_PROVINCE)->first();
            if ($shippingPartnerLocation instanceof ShippingPartnerLocation) {
                DB::transaction(function () use ($shippingPartnerLocation, $snappyProvince) {
                    $shippingPartnerLocation->identity = $snappyProvince['id'];
                    $shippingPartnerLocation->code     = $snappyProvince['id'];
                    $shippingPartnerLocation->name     = $snappyProvince['name'];
                    $shippingPartnerLocation->save();
                    $this->info('mapped for ' . $shippingPartnerLocation->name);
                    $ghnDistricts = $this->api->getDistricts($snappyProvince['id']);
                    foreach ($ghnDistricts as $ghnDistrict) {
                        $shippingPartnerLocationDistrict = ShippingPartnerLocation::query()
                            ->where('partner_code', ShippingPartner::PARTNER_SNAPPY)
                            ->where('parent_location_code', $shippingPartnerLocation->location_code)
                            ->whereNull('identity')
                            ->where('name', 'like', '%' . $this->gexMatchSearch($ghnDistrict['name']) . '%')
                            ->where('type', Location::TYPE_DISTRICT)->first();
                        if ($shippingPartnerLocationDistrict instanceof ShippingPartnerLocation) {
                            $shippingPartnerLocationDistrict->identity = $ghnDistrict['id'];
                            $shippingPartnerLocationDistrict->code     = $ghnDistrict['id'];
                            $shippingPartnerLocationDistrict->name     = $ghnDistrict['name'];
                            $shippingPartnerLocationDistrict->save();
                            $this->info('mapped for district ' . $shippingPartnerLocationDistrict->name);
                            $ghnWards = (array)$this->api->getWards($ghnDistrict['id']);
                            foreach ($ghnWards as $ghnWard) {
                                $shippingPartnerLocationWard = ShippingPartnerLocation::query()
                                    ->where('partner_code', ShippingPartner::PARTNER_SNAPPY)
                                    ->where('parent_location_code', $shippingPartnerLocationDistrict->location_code)
                                    ->whereNull('identity')
                                    ->where('name', 'like', '%' . $this->gexMatchSearch($ghnWard['name']) . '%')
                                    ->where('type', Location::TYPE_WARD)->first();
                                if ($shippingPartnerLocationWard instanceof ShippingPartnerLocation) {
                                    $shippingPartnerLocationWard->identity = $ghnWard['id'];
                                    $shippingPartnerLocationWard->code     = $ghnWard['id'];
                                    $shippingPartnerLocationWard->name     = $ghnWard['name'];
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
