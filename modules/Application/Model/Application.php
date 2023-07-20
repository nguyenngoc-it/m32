<?php

namespace Modules\Application\Model;

use App\Base\Model;
use Gobiz\Support\Traits\CachedPropertiesTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Application\Services\ApplicationWebhook;
use Modules\Application\Services\ApplicationWebhookInterface;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Order\Models\Order;
use Modules\User\Models\User;
use Tymon\JWTAuth\Contracts\JWTSubject;
use function Clue\StreamFilter\fun;

/**
 * Class Application
 *
 * @property int $id
 * @property string $code
 * @property string $secret
 * @property string $name
 * @property string $description
 * @property string $avatar
 * @property array $allowed_ips
 * @property int|null webhook_id
 * @property string|null webhook_url
 * @property string|null webhook_secret
 * @property string $status
 * @property int $creator_id
 * @property string $hidden_secret
 * @property User|null $creator
 * @property ShippingPartner[]|Collection $shippingPartners
 * @property Order[]|Collection $orders
 */
class Application extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;
    use CachedPropertiesTrait;

    protected $table = 'applications';

    protected $casts = [
        'allowed_ips' => 'array',
    ];

    protected $hidden = [
        'secret',
    ];

    const STATUS_ACTIVE   = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * @return string
     */
    public function getHiddenSecretAttribute()
    {
        return str_repeat('*', 12) . substr($this->getAttribute('secret'), -4);
    }

    /**
     * @return HasMany
     */
    public function shippingPartners()
    {
        return $this->hasMany(ShippingPartner::class);
    }

    /**
     * @return HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return BelongsToMany
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'application_members')->withTimestamps();
    }

    /**
     * @return HasMany
     */
    public function applicationMembers()
    {
        return $this->hasMany(ApplicationMember::class, 'application_id');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasMember(User $user)
    {
        return !!$this->getAttribute('members')->firstWhere('id', $user->id);
    }

    /**
     * @return ApplicationWebhookInterface
     */
    public function webhook()
    {
        return $this->getCachedProperty('webhook', function () {
            return new ApplicationWebhook($this);
        });
    }
}
