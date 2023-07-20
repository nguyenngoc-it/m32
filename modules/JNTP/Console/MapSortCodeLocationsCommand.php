<?php

namespace Modules\JNTP\Console;

use Illuminate\Console\Command;
use Modules\JNTP\Services\JNTPShippingPartner;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\ShippingPartnerException;

class MapSortCodeLocationsCommand extends Command
{
    protected $signature = 'jntp:map-sort-code-locations';

    protected $description = 'Mapping sort code locations for philippines locations';

    /**
     * @throws ShippingPartnerException
     */
    public function handle()
    {
        /** @var ShippingPartner $shippingPartner */
        $shippingPartner                  = ShippingPartner::find(29);
        $jntpShippingPartner              = new JNTPShippingPartner($shippingPartner->settings);
        $shippingPartnerLocationProvinces = ShippingPartnerLocation::query()->where([
            'partner_code' => ShippingPartner::PARTNER_JNTP,
            'type' => Location::TYPE_PROVINCE
        ])->get();
        /** @var ShippingPartnerLocation $shippingPartnerLocationProvince */
        foreach ($shippingPartnerLocationProvinces as $shippingPartnerLocationProvince) {
            $shippingPartnerLocationDistricts = ShippingPartnerLocation::query()->where([
                'partner_code' => ShippingPartner::PARTNER_JNTP,
                'type' => Location::TYPE_DISTRICT,
                'parent_location_code' => $shippingPartnerLocationProvince->location_code
            ])->get();
            /** @var ShippingPartnerLocation $shippingPartnerLocationDistrict */
            foreach ($shippingPartnerLocationDistricts as $shippingPartnerLocationDistrict) {
                try {
                    $jntDistricts = $jntpShippingPartner->getLocations($shippingPartnerLocationProvince->name, $shippingPartnerLocationDistrict->name);

                    if (is_array($jntDistricts)) {
                        foreach ($jntDistricts as $jntDistrict) {
                            $shippingPartnerLocation = ShippingPartnerLocation::query()->where([
                                'type' => Location::TYPE_WARD,
                                'name' => $jntDistrict['area'],
                                'parent_location_code' => $shippingPartnerLocationDistrict->location_code
                            ])->whereNull('meta_data')->first();
                            if ($shippingPartnerLocation instanceof ShippingPartnerLocation) {
                                $shippingPartnerLocation->identity  = $jntDistrict['area'];
                                $shippingPartnerLocation->code      = $jntDistrict['area'];
                                $shippingPartnerLocation->meta_data = [
                                    'sortingcode' => !empty($jntDistrict['sortingcode']) ? $jntDistrict['sortingcode'] : '',
                                    'sortingNo' => !empty($jntDistrict['sortingNo']) ? $jntDistrict['sortingNo'] : '',
                                ];
                                $shippingPartnerLocation->save();
                                $this->info('update for ' . $shippingPartnerLocation->name);
                            }
                        }
                    }
                } catch (\Exception $exception) {

                }
            }
        }
    }
}
