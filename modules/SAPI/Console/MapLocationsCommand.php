<?php

namespace Modules\SAPI\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class MapLocationsCommand extends Command
{
    protected $signature = 'sapi:map-locations';

    protected $description = 'Mapping SAPI locations with system locations';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        (new FastExcel)->import(storage_path('indo_locations.csv'), function ($line) {
            $provinceCode = 'INDO' . $line['provinsi_code'];
            $districtCode = $line['city_code'];
            $wardCode     = $line['district_code'];
            $districtCode = $provinceCode . '_' . $districtCode;
            $wardCode     = $districtCode . '_' . $wardCode;
            $province     = ShippingPartnerLocation::updateOrCreate(
                [
                    'partner_code' => ShippingPartner::PARTNER_SAPI,
                    'type' => Location::TYPE_PROVINCE,
                    'identity' => $line['provinsi_code']
                ],
                [
                    'code' => $line['provinsi_code'],
                    'name' => $line['provinsi_name'],
                    'location_code' => $provinceCode
                ]
            );
            $this->info('insered province ' . $province->name);
            $district = ShippingPartnerLocation::updateOrCreate(
                [
                    'identity' => $line['city_code'],
                    'type' => Location::TYPE_DISTRICT,
                    'partner_code' => ShippingPartner::PARTNER_SAPI,
                ],
                [
                    'code' => $line['city_code'],
                    'name' => $line['city_name'],
                    'location_code' => $districtCode
                ]
            );
            $this->info('insered district ' . $district->name);
            $ward = ShippingPartnerLocation::updateOrCreate(
                [
                    'identity' => $line['district_code'],
                    'type' => Location::TYPE_WARD,
                    'partner_code' => ShippingPartner::PARTNER_SAPI,

                ],
                [
                    'code' => $line['district_code'],
                    'name' => $line['district_name'],
                    'location_code' => $wardCode
                ]
            );
            $this->info('insered ward ' . $ward->name);
        });
    }
}
