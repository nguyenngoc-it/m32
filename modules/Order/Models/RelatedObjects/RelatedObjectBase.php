<?php

namespace Modules\Order\Models\RelatedObjects;

use Gobiz\Support\Helper;

abstract class RelatedObjectBase
{
    public function attributes()
    {
        $attributes = get_object_vars($this);
        foreach ($attributes as $key => $value) {
            $newKey = Helper::decamelize($key);
            if ($newKey !== $key) {
                unset($attributes[$key]);
                $attributes[$newKey] = $value;
            }
        }
        return $attributes;
    }
}
