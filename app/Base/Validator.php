<?php

namespace App\Base;

use Gobiz\Validation\Validator as BaseValidator;

abstract class Validator extends BaseValidator
{
    const ERROR_NOT_EXIST     = 'exists';
    const ERROR_REQUIRED      = 'required';
    const ERROR_GREATER       = 'greater';
    const ERROR_LESSER        = 'lesser';
    const ERROR_INVALID       = 'invalid';
    const ERROR_ALREADY_EXIST = 'already_exist';
    const ERROR_UNIQUE        = 'unique';
    const ERROR_NOT_NUMBER    = 'not_number';
    const ERROR_403           = 'unauthorized';
    const ERROR_DUPLICATED    = 'duplicated';
}
