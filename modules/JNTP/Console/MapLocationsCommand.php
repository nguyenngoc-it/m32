<?php

namespace Modules\JNTP\Console;

use Illuminate\Console\Command;
use Modules\JNTP\Services\JNTPShippingPartner;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\ShippingPartnerException;

class MapLocationsCommand extends Command
{
    protected $signature = 'jntp:map-locations';

    protected $description = 'Mapping JNTP locations with system locations';

    /**
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        /** @var ShippingPartner $shippingPartner */
        $shippingPartner                  = ShippingPartner::find(1);
        $jntpShippingPartner              = new JNTPShippingPartner($shippingPartner->settings);
        $shippingPartnerLocationProvinces = ShippingPartnerLocation::query()->where([
            'partner_code' => ShippingPartner::PARTNER_JNTP,
            'type' => Location::TYPE_PROVINCE
        ])
            ->whereNull('identity')
            ->get();
        /** @var ShippingPartnerLocation $shippingPartnerLocationProvince */
        foreach ($shippingPartnerLocationProvinces as $shippingPartnerLocationProvince) {
            $shippingPartnerLocationDistricts = ShippingPartnerLocation::query()->where([
                'partner_code' => ShippingPartner::PARTNER_JNTP,
                'type' => Location::TYPE_DISTRICT,
                'parent_location_code' => $shippingPartnerLocationProvince->location_code
            ])
                ->whereNull('identity')
                ->get();
            /** @var ShippingPartnerLocation $shippingPartnerLocationDistrict */
            foreach ($shippingPartnerLocationDistricts as $shippingPartnerLocationDistrict) {
                $jntDistricts = $jntpShippingPartner->getLocations($shippingPartnerLocationProvince->name, $shippingPartnerLocationDistrict->name);
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
