<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Application\Model\Application;
use Modules\Application\Services\ApplicationPolicy;
use Modules\User\Models\User;

class AuthServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $policies = [
        Application::class => ApplicationPolicy::class,
    ];

    public function register()
    {
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
    }

    public function boot()
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Gate::after(function (User $user, $ability) {
            return !!count(array_intersect([$ability, '*'], $user->permissions ?: []));
        });
    }

    public function provides()
    {
        return [AuthServiceInterface::class];
    }
}
