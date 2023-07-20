<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use mmghv\LumenRouteBinding\RouteBindingServiceProvider as BaseServiceProvider;
use Modules\Application\Model\Application;
use Modules\User\Models\User;

class RouteBindingServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $models = [
            'user' => User::class,
            'application' => Application::class,
        ];

        foreach ($models as $key => $model) {
            $this->bindModel(new $model, $key);
        }
    }

    /**
     * @param Model $model
     * @param string $key
     */
    protected function bindModel(Model $model, $key)
    {
        $this->binder->bind($key, function ($id) use ($model) {
            return $model->query()->where($model->getKeyName(), $id)->firstOrFail();
        });
    }
}
