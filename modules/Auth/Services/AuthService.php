<?php

namespace Modules\Auth\Services;

use Exception;
use Laravel\Socialite\Two\AbstractProvider as OAuthProvider;
use Laravel\Socialite\Two\User as AuthenticatedUser;
use Modules\Auth\Commands\SaveAuthenticatedUser;
use Modules\User\Models\User;

class AuthService implements AuthServiceInterface
{
    /**
     * @var OAuthProvider
     */
    protected $oauth;

    /**
     * Make OAuth handler
     *
     * @return OAuthProvider
     */
    public function oauth()
    {
        return $this->oauth ?? $this->oauth = new GobizOAuth(request(), config('gobiz.m10.client_id'), config('gobiz.m10.client_secret'), url('admin/login/callback'));
    }

    /**
     * Save the authenticated user
     *
     * @param AuthenticatedUser $user
     * @return User
     * @throws Exception
     */
    public function saveUser(AuthenticatedUser $user)
    {
        return (new SaveAuthenticatedUser($user))->handle();
    }
}
