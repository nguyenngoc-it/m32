<?php

namespace Modules\NIJAVAI\Console;

use Illuminate\Console\Command;
use Modules\NIJAVAI\Services\NIJAVAIShippingPartner;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;

class MapLocationsCommand extends Command
{
    protected $signature = 'nijavai:map-locations';

    protected $description = 'Mapping NIJAVAI locations with system locations';

    /**
     */
    public function handle()
    {
        /** @var ShippingPartner $shippingPartner */
        $shippingPartner                  = ShippingPartner::find(1);
        $nijavaiShippingPartner              = new NIJAVAIShippingPartner($shippingPartner->settings);
        $shippingPartnerLocationProvinces = ShippingPartnerLocation::query()->where([
            'partner_code' => ShippingPartner::PARTNER_NIJAVAI,
            'type' => Location::TYPE_PROVINCE
        ])
            ->whereNull('identity')
            ->get();
        /** @var ShippingPartnerLocation $shippingPartnerLocationProvince */
        foreach ($shippingPartnerLocationProvinces as $shippingPartnerLocationProvince) {
            $shippingPartnerLocationDistricts = ShippingPartnerLocation::query()->where([
                'partner_code' => ShippingPartner::PARTNER_NIJAVAI,
                'type' => Location::TYPE_DISTRICT,
                'parent_location_code' => $shippingPartnerLocationProvince->location_code
            ])
                ->whereNull('identity')
                ->get();
            /** @var ShippingPartnerLocation $shippingPartnerLocationDistrict */
            foreach ($shippingPartnerLocationDistricts as $shippingPartnerLocationDistrict) {
                $jntDistricts = $nijavaiShippingPartner->getLocations($shippingPartnerLocationProvince->name, $shippingPartnerLocationDistrict->name);
                if (is_array($jntDistricts)) {
                    foreach ($jntDistricts as $jntDistrict) {
                        $shippingPartnerLocation = ShippingPartnerLocation::query()->where([
                            'type' => Location::TYPE_WARD,
                            'name' => $jntDistrict['area'],
                            'parent_location_code' => $shippingPartnerLocationDistrict->location_code
                        ])->whereNull('identity')->first();
                        if ($shippingPartnerLocation instanceof ShippingPartnerLocation) {
                            $shippingPartnerLocation->identity = $jntDistrict['area'];
                            $shippingPartnerLocation->code     = $jntDistrict['area'];
                            $shippingPartnerLocation->save();
                            $this->info('update for ' . $shippingPartnerLocation->name);
                        }
                    }
                }
            }
        }
    }
}
