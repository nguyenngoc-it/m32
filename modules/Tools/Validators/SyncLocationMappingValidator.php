<?php

namespace Modules\Tools\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;

class SyncLocationMappingValidator extends Validator
{
    /** @var Location $country */
    protected $country;

    public function __construct(Location $country, array $input = [])
    {
        parent::__construct($input);
        $this->country = $country;
    }
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'label' => 'required',
            'name' => 'required',
            'type' => 'required|in:' . Location::TYPE_WARD . ',' . Location::TYPE_DISTRICT . ',' . Location::TYPE_PROVINCE,
            'code' => 'required',
            'parent_code' => 'required',
            'postal_code' => '',
            'identity' => '',
            'name_local' => ''
        ];
    }

    protected function customValidate()
    {
        $parentCode = $this->input('parent_code');
        $type       = $this->input('type');
        /** @var Location|null $parentLocation */
        $parentLocation = Location::query()->firstWhere('code', $parentCode);
        if (empty($parentLocation)) {
            $this->errors()->add('parent_code', 'not_found');
            return;
        }
        if (($type == Location::TYPE_PROVINCE && $parentLocation->type != Location::TYPE_COUNTRY)
            || ($type == Location::TYPE_DISTRICT && $parentLocation->type != Location::TYPE_PROVINCE)
            || ($type == Location::TYPE_WARD && $parentLocation->type != Location::TYPE_DISTRICT)) {
            $this->errors()->add('type', 'invalid');
        }

    }
}
