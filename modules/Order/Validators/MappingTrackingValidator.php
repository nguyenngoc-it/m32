<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Application\Model\Application;
use Modules\Location\Models\Location;
use Modules\Order\Models\RelatedObjects\InputOrder;
use Modules\ShippingPartner\Models\ShippingPartner;

class MappingTrackingValidator extends Validator
{
    /**
     * @var Application
     */
    protected $application;
    /** @var InputOrder */
    protected $inputOrder;

    /**
     * CreateOrderValidator constructor.
     * @param Application $application
     * @param array $input
     */
    public function __construct(Application $application, array $input)
    {
        $this->application = $application;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'ref' => 'required',
            'shipping_carrier_code' => 'required',
            'shipping_connect_code' => 'required',
            'receiver_name' => 'required',
            'receiver_phone' => 'required',
            'receiver_address' => 'required',
            'receiver_district_code' => 'required',
            'receiver_ward_code' => 'required',
            'weight' => '',
            'cod' => '',
            'items' => 'array',
            'freight_bill_code' => ''
        ];
    }

    /**
     * @return InputOrder
     */
    public function getInputOrder(): InputOrder
    {
        return $this->inputOrder;
    }

    protected function customValidate()
    {
        if (empty($this->application)) {
            $this->errors()->add('application', 'notfound');
            return;
        }

        $shippingPartner = $this->application->shippingPartners()->firstWhere('code', $this->input('shipping_connect_code'));
        if (!$shippingPartner instanceof ShippingPartner) {
            $this->errors()->add('shipping_connect_code', static::ERROR_NOT_EXIST);
            return;
        }

        if (!empty($this->input['ref']) && $this->application->orders()->firstWhere('ref', $this->input['ref'])) {
            $this->errors()->add('ref', static::ERROR_ALREADY_EXIST);
            return;
        }

        $shippingCarriers = $shippingPartner->partner()->getShippingCarriers();
        if (!in_array($this->input('shipping_carrier_code'), $shippingCarriers)) {
            $this->errors()->add('shipping_carrier_code', static::ERROR_NOT_EXIST);
            return;
        }

        if (!empty($this->input['receiver_district_code'])) {
            if (!$receiverDistrict = Location::query()->firstWhere([
                'code' => $this->input['receiver_district_code'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $this->errors()->add('receiver_district_code', static::ERROR_NOT_EXIST);
                return;
            }

            if (!empty($this->input['receiver_ward_code'])) {
                if (!$receiverWard = Location::query()->firstWhere([
                    'code' => $this->input['receiver_ward_code'],
                    'type' => Location::TYPE_WARD
                ])) {
                    $this->errors()->add('receiver_ward_code', static::ERROR_NOT_EXIST);
                    return;
                }

                if (
                    !$receiverDistrict instanceof Location ||
                    (
                        $receiverWard && $receiverWard->parent_code != $receiverDistrict->code
                    )
                ) {
                    $this->errors()->add('receiver_ward_code', static::ERROR_INVALID);
                    return;
                }
            }
        }

        $this->inputOrder = new InputOrder([
            'ref' => $this->input('ref'),
            'shipping_carrier_code' => $this->input('shipping_carrier_code'),
            'shipping_connect_code' => $this->input('shipping_connect_code'),
            'receiver_name' => $this->input('receiver_name'),
            'receiver_phone' => $this->input('receiver_phone'),
            'receiver_address' => $this->input('receiver_address'),
            'receiver_district_code' => $this->input('receiver_district_code'),
            'receiver_ward_code' => $this->input('receiver_ward_code'),
            'weight' => $this->input('weight'),
            'cod' => $this->input('cod'),
            'items' => $this->input('items'),
            'tracking_no' => $this->input('freight_bill_code'),
        ]);
        $this->inputOrder->setShippingPartner($shippingPartner);
    }
}
