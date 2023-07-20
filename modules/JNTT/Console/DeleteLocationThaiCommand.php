<?php

namespace Modules\JNTT\Console;

use Illuminate\Console\Command;
use Modules\Location\Models\Location;

class DeleteLocationThaiCommand extends Command
{
    protected $signature = 'jntt:delete-locations-thai';

    protected $description = 'Delete locations Thai';

    public function handle()
    {
        /** @var Location $indoLocation */
        $indoLocation = Location::query()->where('code', 'F49179')->first();
        $indoLocation->children->each(function (Location $location) {
            $location->children()->delete();
        });
        $indoLocation->children()->delete();
    }

}
