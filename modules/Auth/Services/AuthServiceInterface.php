<?php

namespace Modules\Auth\Services;

use Laravel\Socialite\Two\AbstractProvider as OAuthProvider;
use Laravel\Socialite\Two\User as AuthenticatedUser;
use Modules\User\Models\User;

interface AuthServiceInterface
{
    /**
     * Make OAuth handler
     *
     * @return OAuthProvider
     */
    public function oauth();

    /**
     * Save the authenticated user
     *
     * @param AuthenticatedUser $user
     * @return User
     */
    public function saveUser(AuthenticatedUser $user);
}
