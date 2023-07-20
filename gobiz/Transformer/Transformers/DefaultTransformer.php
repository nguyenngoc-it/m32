<?php

namespace Gobiz\Transformer\Transformers;

use DateTime;
use Illuminate\Support\Enumerable;
use JsonSerializable;
use Gobiz\Transformer\TransformerInterface;

class DefaultTransformer implements TransformerInterface
{
    /**
     * Transform the data
     *
     * @param object $data
     * @return mixed
     */
    public function transform($data)
    {
        if ($data instanceof Enumerable) {
            return $data->all();
        }

        if ($data instanceof DateTime) {
            return $data->format('Y-m-d h:i:s');
        }

        if ($data instanceof JsonSerializable) {
            return $data->jsonSerialize();
        }

        if (method_exists($data, 'toArray')) {
            return $data->toArray();
        }

        if (method_exists($data, 'toJson')) {
            return json_decode($data->toJson(), true);
        }

        if (get_class($data) === 'stdClass') {
            return (array)$data;
        }

        return $data;
    }
}