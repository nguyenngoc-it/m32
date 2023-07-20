<?php

namespace Modules\SAPI\Console;

use Illuminate\Console\Command;
use Modules\Location\Models\Location;

class DeleteLocationIndoCommand extends Command
{
    protected $signature = 'sapi:delete-locations-indo';

    protected $description = 'Delete locations Indo';

    public function handle()
    {
        /** @var Location $indoLocation */
        $indoLocation = Location::query()->where('code', 'F54888')->first();
        $indoLocation->children->each(function (Location $location) {
            $location->children()->delete();
        });
        $indoLocation->children()->delete();
    }

}
