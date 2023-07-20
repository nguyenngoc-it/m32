<?php

namespace Modules\SAPI\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Rap2hpoutre\FastExcel\FastExcel;

class InsertIndoLocationsCommand extends Command
{
    protected $signature = 'sapi:insert-indo-locations';

    protected $description = 'Insert Indo locations';

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
            $province     = Location::updateOrCreate([
                'code' => $provinceCode,
                'type' => Location::TYPE_PROVINCE,
                'parent_code' => Location::COUNTRY_CODE_INDONESIA
            ], ['label' => $line['provinsi_name']]);
            $this->info('insered province ' . $province->label);
            $district = Location::updateOrCreate([
                'code' => $districtCode,
                'type' => Location::TYPE_DISTRICT,
                'parent_code' => $provinceCode
            ], ['label' => $line['city_name'],]);
            $this->info('insered district ' . $district->label);
            $ward = Location::updateOrCreate([
                'code' => $wardCode,
                'type' => Location::TYPE_WARD,
                'parent_code' => $districtCode,

            ], ['label' => $line['district_name']]);
            $this->info('insered ward ' . $ward->label);
        });
    }
}
