<?php

namespace Modules\JNEI\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class InsertZipCodeCommand extends Command
{
    protected $signature = 'jnei:insert-zipcode';

    protected $description = 'Insert Zipcode for JNEI';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $insertedWard = [];
        (new FastExcel)->import(storage_path('jnei_update_ward.csv'), function ($line) use ($insertedWard) {
            $locationCode = $line['location_code'];
            $dest         = $line['DEST'];
            $zipCode      = $line['ZIP'];
            /** @var ShippingPartnerLocation|null $provincePartnerLocation */
            $provincePartnerLocation = ShippingPartnerLocation::query()->where('partner_code', 'JNEI')
                ->where('type', Location::TYPE_PROVINCE)
                ->where('name', $provinceName)
                ->first();
            if ($provincePartnerLocation) {
                $districtPartnerLocation = ShippingPartnerLocation::query()->where('partner_code', 'JNEI')
                    ->where('parent_location_code', $provincePartnerLocation->location_code)
                    ->where('name', $districtName)
                    ->first();
                if ($districtPartnerLocation) {
                    /** @var ShippingPartnerLocation|null $wardPartnerLocation */
                    $wardPartnerLocation = ShippingPartnerLocation::query()->where('partner_code', 'JNEI')
                        ->where('parent_location_code', $districtPartnerLocation->location_code)
                        ->where('name', $wardName)
                        ->first();
                    if ($wardPartnerLocation && !in_array($wardPartnerLocation->location_code, $insertedWard)) {
                        $wardPartnerLocation->meta_data = $wardPartnerLocation->meta_data ? array_merge($wardPartnerLocation->meta_data, ['zip_code' => $line['ZIP_CODE']]) : ['zip_code' => $line['ZIP_CODE']];
                        $wardPartnerLocation->save();
                        $this->info('Updated zip_code for ward ' . $wardPartnerLocation->name);
                        $insertedWard[] = $wardPartnerLocation->location_code;
                    }
                }
            }
        });
    }
}
