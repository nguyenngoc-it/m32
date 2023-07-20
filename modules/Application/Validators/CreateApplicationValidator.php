<?php

namespace Modules\Application\Validators;

use App\Base\Validator;

class CreateApplicationValidator extends Validator
{
    /**
     * CreateApplicationValidator constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }
}