<?php

namespace Modules\JNEI\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class UpdateZipDestCommand extends Command
{
    protected $signature = 'jnei:update-zip-dest';

    protected $description = 'Update Zipcode and Dest for JNEI';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $insertedWard = [];
        (new FastExcel)->import(storage_path('jnei_update_zip_dest.csv'), function ($line) use ($insertedWard) {
            $locationCode = $line['code'];
            $dest         = $line['destcode'];
            $zipCode      = $line['zipcode'];
            /** @var ShippingPartnerLocation|null $wardPartnerLocation */
            $wardPartnerLocation = ShippingPartnerLocation::query()->where('partner_code', 'JNEI')
                ->where('type', Location::TYPE_WARD)
                ->where('location_code', $locationCode)
                ->first();
            if ($wardPartnerLocation) {
                $wardPartnerLocation->meta_data = $wardPartnerLocation->meta_data ? array_merge($wardPartnerLocation->meta_data, ['zip_code' => $zipCode,'dest' => $dest]) : ['zip_code' => $zipCode,'dest' => $dest];
                $wardPartnerLocation->save();
                $this->info('Updated zip_code and dest for ward ' . $wardPartnerLocation->name);
                $insertedWard[] = $wardPartnerLocation->location_code;
            }
        });
    }
}
