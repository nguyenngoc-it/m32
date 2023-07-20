<?php

namespace Modules\User\Transformers;

use App\Base\Transformer;
use Illuminate\Support\Facades\Storage;
use Modules\User\Models\User;

class UserTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param User $user
     * @return mixed
     */
    public function transform($user)
    {
        return array_merge($user->attributesToArray(), [
            'avatar' => $user->avatar ? Storage::url($user->avatar) : 'https://www.gravatar.com/avatar/'.md5($user->username),
        ]);
    }
}
