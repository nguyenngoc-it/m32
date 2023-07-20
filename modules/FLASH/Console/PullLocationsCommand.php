<?php

namespace Modules\FLASH\Console;

use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;

class PullLocationsCommand extends Command
{
    protected $signature = 'flash:pull-locations';

    protected $description = 'Pull locations for Flash Thailand';

    public function handle()
    {

        /**
         * @var ShippingPartnerLocation[] $Provinces
         */
        $provinceLocations = Location::query()->where([
            'type'        => Location::TYPE_PROVINCE,
            'parent_code' => 'thailand'
        ])->get();

        /** @var Location $provinceLocation */
        foreach ($provinceLocations as $provinceLocation) {
            ShippingPartnerLocation::firstOrCreate(
                [
                    'partner_code'  => ShippingPartner::PARTNER_FLASH,
                    'type'          => Location       ::TYPE_PROVINCE,
                    'location_code' => $provinceLocation->code
                ],
                [
                    'location_code'        => $provinceLocation->code,
                    'parent_location_code' => 'thailand',
                    'identity'             => $provinceLocation->label,
                    'name'                 => $provinceLocation->label,
                    'postal_code'          => $provinceLocation->postal_code,
                ]
            );
            $this->info('update province ' . $provinceLocation->label);
            $districtLocations = Location::query()->where([
                'type'        => Location::TYPE_DISTRICT,
                'parent_code' => $provinceLocation->code
            ])->get();
            /** @var Location $districtLocation */
            foreach ($districtLocations as $districtLocation) {
                ShippingPartnerLocation::firstOrCreate(
                    [
                        'partner_code'  => ShippingPartner::PARTNER_FLASH,
                        'type'          => Location       ::TYPE_DISTRICT,
                        'location_code' => $districtLocation->code
                    ],
                    [
                        'location_code'        => $districtLocation->code,
                        'parent_location_code' => $provinceLocation->code,
                        'identity'             => $districtLocation->label,
                        'name'                 => $districtLocation->label,
                        'postal_code'          => $districtLocation->postal_code,
                    ]
                );
                $this->info('update district ' . $districtLocation->label);
                Location::query()->where([
                    'type'        => Location::TYPE_WARD,
                    'parent_code' => $districtLocation->code
                ])->chunk(10, function ($wardLocations) use ($districtLocation){
                    /** @var Location $wardLocation */
                    foreach ($wardLocations as $wardLocation) {
                        ShippingPartnerLocation::firstOrCreate(
                            [
                                'partner_code'  => ShippingPartner::PARTNER_FLASH,
                                'type'          => Location::TYPE_WARD,
                                'name'          => $wardLocation->label,
                                'location_code' => $wardLocation->code,
                            ],
                            [
                                'parent_location_code' => $districtLocation->code,
                                'identity'             => $wardLocation->label,
                                'name'                 => $wardLocation->label,
                                'postal_code'          => $wardLocation->postal_code,
                            ]
                        );
                    }
                });
            }
        }
    }

}
