<?php

namespace Modules\User\Models;

use App\Base\Model;
use Carbon\Carbon;
use Gobiz\Activity\ActivityCreatorInterface;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Lumen\Auth\Authorizable;
use Modules\Application\Model\Application;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $address
 * @property string $avatar
 * @property string $language
 * @property array $permissions
 * @property Carbon $synced_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject, ActivityCreatorInterface
{
    use Authenticatable, Authorizable;

    protected $table = 'users';

    protected $casts = [
        'permissions' => 'array',
        'synced_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    const USERNAME_SYSTEM = 'system';

    /**
     * @return HasMany
     */
    public function identities()
    {
        return $this->hasMany(UserIdentity::class, 'user_id', 'id');
    }

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
     * Get the creator id
     *
     * @return int
     */
    public function getId()
    {
        return $this->getKey();
    }

    /**
     * Get the creator username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getAttribute('username');
    }

    /**
     * Get the creator name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Get the tenant id
     *
     * @return int
     */
    public function getTenantId()
    {
        return $this->getAttribute('tenant_id');
    }

    /**
     * Return true if current user is admin
     *
     * @return bool
     */
    public function getIsAdmin()
    {
        return true;
    }

    /**
     * Return list of application
     *
     * @return hasMany
     *
     */
    public function applications()
    {
        return $this->belongsToMany(Application::class, 'application_members')->withTimestamps();
    }
}
