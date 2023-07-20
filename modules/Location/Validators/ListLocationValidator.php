<?php

namespace Modules\Location\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;

class ListLocationValidator extends Validator
{
    /**
     * Các key filter
     */
    public static $keyRequests = [
        'type',
        'parent_code',
    ];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'required|in:' . implode(',', Location::$types),
        ];
    }

    protected function customValidate()
    {
        $type = $this->input['type'];
        if ($type != Location::TYPE_COUNTRY && empty($this->input('parent_code'))) {
            $this->errors()->add('parent_code', static::ERROR_REQUIRED);
            return;
        }
    }
}
