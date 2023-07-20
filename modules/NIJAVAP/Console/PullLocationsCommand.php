<?php

namespace Modules\NIJAVAP\Console;

use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;

class PullLocationsCommand extends Command
{
    protected $signature = 'nijavap:pull-locations';

    protected $description = 'Pull locations for NIJAVAP';

    public function handle()
    {

        /**
         * @var ShippingPartnerLocation[] $ghnProvinces
         */
        $provinceLocations = Location::query()->where([
            'type' => Location::TYPE_PROVINCE,
            'parent_code' => 'F2484'
        ])->get();

        /** @var Location $provinceLocation */
        foreach ($provinceLocations as $provinceLocation) {
            ShippingPartnerLocation::updateOrCreate(
                [
                    'partner_code' => 'NIJAVAP',
                    'type' => Location::TYPE_PROVINCE,
                    'name' => $provinceLocation->label
                ],
                [
                    'location_code' => $provinceLocation->code,
                    'parent_location_code' => 'F2484'
                ]
            );
            $this->info('update province ' . $provinceLocation->label);
            $districtLocations = Location::query()->where([
                'type' => Location::TYPE_DISTRICT,
                'parent_code' => $provinceLocation->code
            ])->get();
            /** @var Location $districtLocation */
            foreach ($districtLocations as $districtLocation) {
                ShippingPartnerLocation::updateOrCreate(
                    [
                        'partner_code' => 'NIJAVAP',
                        'type' => Location::TYPE_DISTRICT,
                        'name' => $districtLocation->label
                    ],
                    [
                        'location_code' => $districtLocation->code,
                        'parent_location_code' => $provinceLocation->code
                    ]
                );
                $this->info('update district ' . $districtLocation->label);
                Location::query()->where([
                    'type' => Location::TYPE_WARD,
                    'parent_code' => $districtLocation->code
                ])->chunk(10, function ($wardLocations) use ($districtLocation){
                    /** @var Location $wardLocation */
                    foreach ($wardLocations as $wardLocation) {
                        ShippingPartnerLocation::updateOrCreate(
                            [
                                'partner_code' => 'NIJAVAP',
                                'type' => Location::TYPE_WARD,
                                'name' => $wardLocation->label
                            ],
                            [
                                'location_code' => $wardLocation->code,
                                'parent_location_code' => $districtLocation->code
                            ]
                        );
                    }
                });
            }
        }
    }

}
