<?php

namespace Modules\Order\Validators;

use App\Base\Validator;

class ListOrdersValidator extends Validator
{
    protected $application = '';

    /**
     * Các key filter
     */
    public static $keyRequests = [
        'ref',
        'code',
        'status',
        'page',
        'per_page',
        'application_id'
    ];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sort' => 'in:desc,asc',
            'sortBy' => 'in:created_at,id,code,name',
            'page' => 'numeric',
            'per_page' => 'numeric',
            'application_id' => 'numeric'
        ];
    }
}
