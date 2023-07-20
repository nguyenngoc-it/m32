<?php

namespace Modules\JNTP\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class MapLocationsByFileCommand extends Command
{
    protected $signature = 'jntp:map-locations-by-file';

    protected $description = 'Mapping JNTP locations with system locations';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        (new FastExcel)->import(storage_path('philippine.xlsx'), function ($line) {
            $province = $line['PROVINCE'];
            $district = $line['CITY'];
            $ward     = $line['AREA'];

            $wardPartnerMappings = ShippingPartnerLocation::query()->where([
                'name' => $ward,
                'type' => Location::TYPE_WARD
            ])->whereNull('identity')->get();
            /** @var ShippingPartnerLocation $wardPartnerMapping */
            foreach ($wardPartnerMappings as $wardPartnerMapping) {
                $districtPartnerMapping = $wardPartnerMapping->parentByLocationCode;
                if ($districtPartnerMapping && $districtPartnerMapping->name == $district) {
                    if($districtPartnerMapping->parentByLocationCode && $districtPartnerMapping->parentByLocationCode->name == $province){
                        $wardPartnerMapping->identity = $ward;
                        $wardPartnerMapping->save();
                        $wardPartnerMapping->parentByLocationCode->identity = $district;
                        $wardPartnerMapping->parentByLocationCode->save();
                        $this->info('update for ' . $wardPartnerMapping->name);
                    }
                }
            }
        });
    }
}
