<?php

namespace Modules\JNTT\Console;

use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;

class MapLocationsCommand extends Command
{
    protected $signature = 'jntt:map-locations';

    protected $description = 'Mapping JNTT locations with system locations';


    public function handle()
    {
        $thaiLocation = Location::query()->where('code', 'thailand')->first();
        $thaiLocation->children->each(function (Location $location) {
            ShippingPartnerLocation::updateOrCreate(
                [
                    'partner_code' => ShippingPartner::PARTNER_JNTT,
                    'type' => Location::TYPE_PROVINCE,
                    'identity' => $location->label
                ],
                [
                    'code' => $location->label,
                    'name' => $location->label,
                    'location_code' => $location->code
                ]
            );
            if ($location->children->count()) {
                /** @var Location $chidLocation */
                foreach ($location->children as $chidLocation) {
                    $district = ShippingPartnerLocation::updateOrCreate(
                        [
                            'identity' => $chidLocation->label,
                            'type' => Location::TYPE_DISTRICT,
                            'partner_code' => ShippingPartner::PARTNER_JNTT,
                            'parent_location_code' => $location->code,
                        ],
                        [
                            'code' => $chidLocation->label,
                            'name' => $chidLocation->label,
                            'location_code' => $chidLocation->code,
                        ]
                    );

                    $this->info('insered district ' . $district->name);
                }
            }
        });
    }
}
