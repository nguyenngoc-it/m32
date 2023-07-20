<?php

namespace Modules\Application\Transformers;

use App\Base\Transformer;
use Illuminate\Support\Facades\Storage;
use Modules\Application\Model\Application;

class ApplicationTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Application $app
     * @return mixed
     */
    public function transform($app)
    {
        return array_merge($app->attributesToArray(), [
            'avatar' => $app->avatar ? Storage::url($app->avatar) : 'https://www.gravatar.com/avatar/'.md5($app->id),
            'hidden_secret' => $app->hidden_secret,
        ]);
    }
}
