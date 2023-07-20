<?php

namespace Modules\Auth\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\User as AuthenticatedUser;
use Modules\User\Models\User;
use Modules\User\Models\UserIdentity;

class SaveAuthenticatedUser
{
    /**
     * @var AuthenticatedUser
     */
    protected $authenticatedUser;

    /**
     * SaveAuthenticatedUser constructor
     *
     * @param AuthenticatedUser $authenticatedUser
     */
    public function __construct(AuthenticatedUser $authenticatedUser)
    {
        $this->authenticatedUser = $authenticatedUser;
    }

    /**
     * @return User
     * @throws Exception
     */
    public function handle()
    {
        $user = $this->saveUser();
        $this->saveUserIdentity($user);

        return $user;
    }

    /**
     * @return User|object
     * @throws Exception
     */
    protected function saveUser()
    {
        $authenticatedUser = $this->authenticatedUser;

        return User::query()->updateOrCreate([
            'email' => $authenticatedUser->getEmail(),
        ], array_filter([
            'username' => $authenticatedUser->getNickname(),
            'name' => $authenticatedUser->getName(),
            'phone' => Arr::get($authenticatedUser->getRaw(), 'phone'),
            'avatar' => $authenticatedUser->getAvatar(),
            'permissions' => Arr::get($authenticatedUser->getRaw(), 'permissions'),
            'synced_at' => new Carbon(),
        ]));
    }

    /**
     * @param User $user
     * @return UserIdentity|object
     */
    protected function saveUserIdentity(User $user)
    {
        $authenticatedUser = $this->authenticatedUser;

        return UserIdentity::query()->updateOrCreate([
            'user_id' => $user->id,
            'source' => UserIdentity::SOURCE_GOBIZ,
        ], array_filter([
            'source_user_id' => $authenticatedUser->getId(),
            'source_user_info' => $authenticatedUser->getRaw(),
            'access_token' => $authenticatedUser->token,
            'refresh_token' => $authenticatedUser->refreshToken,
        ]));
    }
}