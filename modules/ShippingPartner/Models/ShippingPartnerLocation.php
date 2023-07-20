<?php

namespace Modules\ShippingPartner\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;

/**
 * Modules\ShippingPartner\Models\ShippingPartnerLocation
 *
 * @property int $id
 * @property string $partner_code
 * @property string $type
 * @property string $identity
 * @property string $code
 * @property string postal_code
 * @property string $name
 * @property string $parent_identity
 * @property string $location_code
 * @property string $parent_location_code
 * @property array meta_data
 *
 * @property ShippingPartnerLocation $parent
 * @property ShippingPartnerLocation|null parentByLocationCode
 * @property Collection|ShippingPartnerLocation[] $children
 * @property Location $location
 * @mixin Model
 */
class ShippingPartnerLocation extends Model
{
    protected $table = 'shipping_partner_locations';

    public $timestamps = false;

    protected $casts = [
        'meta_data' => 'array'
    ];

    const SHIPPING_PARTNER_JNTT = 'JNTT';
    const SHIPPING_PARTNER_FLASH = 'FLASH';
    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(ShippingPartnerLocation::class, 'parent_identity', 'identity')
            ->where($this->only('partner_code'))
            ->where('type', $this->getParentType());
    }

    /**
     * @return BelongsTo
     */
    public function parentByLocationCode()
    {
        return $this->belongsTo(ShippingPartnerLocation::class, 'parent_location_code', 'location_code')
            ->where($this->only('partner_code'))
            ->where('type', $this->getParentType());
    }

    /**
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(ShippingPartnerLocation::class, 'parent_identity', 'identity')
            ->where($this->only('partner_code'))
            ->where('type', $this->getChildType());
    }

    /**
     * @return BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_code', 'code');
    }

    /**
     * Get location type of parent
     *
     * @return string|null
     */
    protected function getParentType()
    {
        return Arr::get([
            Location::TYPE_WARD => Location::TYPE_DISTRICT,
            Location::TYPE_DISTRICT => Location::TYPE_PROVINCE,
            Location::TYPE_COUNTRY => Location::TYPE_DISTRICT,
        ], $this->getAttribute('type'));
    }

    /**
     * Get location type of parent
     *
     * @return string|null
     */
    protected function getChildType()
    {
        return Arr::get([
            Location::TYPE_COUNTRY => Location::TYPE_PROVINCE,
            Location::TYPE_PROVINCE => Location::TYPE_DISTRICT,
            Location::TYPE_DISTRICT => Location::TYPE_WARD,
        ], $this->getAttribute('type'));
    }
}
