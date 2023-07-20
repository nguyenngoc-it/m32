<?php

namespace Gobiz\Transformer;

interface TransformerInterface
{
    /**
     * Transform the data
     *
     * @param object $data
     * @return mixed
     */
    public function transform($data);
}