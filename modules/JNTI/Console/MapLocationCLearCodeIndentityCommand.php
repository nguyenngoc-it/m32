<?php

namespace Modules\JNTI\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;

class MapLocationCLearCodeIndentityCommand extends Command
{
    protected $signature = 'jnti:map-locations-clear-identity-code';

    protected $description = 'Clear data column identity and code from db';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        ShippingPartnerLocation::query()
            ->where([
                'partner_code' => 'JNTI'
            ])
            ->update([
                'identity' => null,
                'code'     => null
            ]);
    }
}