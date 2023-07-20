<?php

namespace Modules\Location\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Location
 *
 * @property int $id
 * @property string $code
 * @property string postal_code
 * @property string $type
 * @property string $parent_code
 * @property string $label
 * @property string $detail
 * @property boolean $active
 * @property int $priority
 * @property Location|null $parent
 * @property Collection|null $children
 */
class Location extends Model
{
    protected $table = 'locations';

    protected $casts = [
        'active' => 'boolean'
    ];

    const TYPE_COUNTRY = 'COUNTRY';
    const TYPE_PROVINCE = 'PROVINCE';
    const TYPE_DISTRICT = 'DISTRICT';
    const TYPE_WARD = 'WARD';

    /**
     * @var array
     */
    public static $types = [
        self::TYPE_COUNTRY,
        self::TYPE_PROVINCE,
        self::TYPE_DISTRICT,
        self::TYPE_WARD,
    ];

    const COUNTRY_CODE_VIETNAM     = 'vietnam';
    const COUNTRY_CODE_INDONESIA   = 'F54888';
    const COUNTRY_CODE_PHILIPPINES = 'F2484';
    const COUNTRY_CODE_THAILAND    = 'thailand';
    const COUNTRY_CODE_MALAYSIA    = 'malaysia';
    const COUNTRY_CODE_CAMBODIA    = 'cambodia';

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_code', 'code');
    }

    /**
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(Location::class, 'parent_code', 'code')
            ->orderBy('priority','desc')
            ->orderBy('label', 'asc');
    }
}
