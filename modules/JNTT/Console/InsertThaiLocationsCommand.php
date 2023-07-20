<?php

namespace Modules\JNTT\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Rap2hpoutre\FastExcel\FastExcel;

class InsertThaiLocationsCommand extends Command
{
    protected $signature = 'jntt:insert-thai-locations';

    protected $description = 'Insert Thai locations';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        (new FastExcel)->import(storage_path('thai_locations.xlsx'), function ($line) {
            $provinceName = $line['province'];
            $districtName = $line['district'];

            $province = Location::updateOrCreate(
                [
                    'type' => Location::TYPE_PROVINCE,
                    'parent_code' => Location::COUNTRY_CODE_THAILAND,
                    'label' => $provinceName
                ],
                [

                ]
            );
            $province->code = 'THAI_' . $province->id;
            $province->save();

            $district = Location::updateOrCreate(
                [
                    'type' => Location::TYPE_DISTRICT,
                    'parent_code' => $province->code,
                    'label' => $districtName
                ],
                [

                ]
            );
            $district->code = 'THAI_' . $district->id;
            $district->save();

            $this->info('inserted province ' . $province->label . ', district ' . $district->label);
        });
    }
}
