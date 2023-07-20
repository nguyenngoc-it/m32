<?php

namespace Modules\Tools\Events\PublicEvents;

use Gobiz\Event\PublicEvent;
use Modules\Location\Models\Location;

class SyncLocation extends PublicEvent
{
    /** @var Location $location */
    protected $location;

    public function __construct(Location $location)
    {
        $this->location = $location;
    }

    /**
     * Get the event name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'SYNC_LOCATION';
    }

    /**
     * Get the event payload
     *
     * @return array
     */
    public function getPayload(): array
    {
        return $this->location->attributesToArray();
    }

    /**
     * Get the event key
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->location->code;
    }
}
