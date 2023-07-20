<?php

namespace Modules\JNTI\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class MapLocationsByFileCommand extends Command
{
    protected $signature = 'jnti:map-locations-by-file';

    protected $description = 'Mapping JNTI locations with system locations';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        (new FastExcel)->import(storage_path('indo_jnti_mapping.xlsx'), function ($line) {
            $province         = $line['province_name'];
            $district         = $line['city_name'];
            $ward             = $line['district_name'];
            $wardIdentity     = $line['JNT_Code'];
            $districtIdentity = $line['JNT_City_code'];

            $wardPartnerMappings = ShippingPartnerLocation::query()->where([
                'name'         => $ward,
                'type'         => Location::TYPE_WARD,
                'partner_code' => 'JNTI'
            ])->whereNull('identity')->get();
            /** @var ShippingPartnerLocation $wardPartnerMapping */
            foreach ($wardPartnerMappings as $wardPartnerMapping) {
                $districtPartnerMapping = $wardPartnerMapping->parentByLocationCode;
                if ($districtPartnerMapping && $districtPartnerMapping->name == $district) {
                    if ($districtPartnerMapping->parentByLocationCode && $districtPartnerMapping->parentByLocationCode->name == $province) {
                        $wardPartnerMapping->identity = $wardIdentity;
                        $wardPartnerMapping->save();
                        $wardPartnerMapping->parentByLocationCode->identity = $districtIdentity;
                        $wardPartnerMapping->parentByLocationCode->save();
                        $this->info('update for ' . $wardPartnerMapping->name);
                    }
                }
            }
        });
    }
}
