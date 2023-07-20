<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Application\Model\Application;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class CreateOrderValidator extends Validator
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;


    /**
     * @var Location|null
     */
    protected $receiverProvince;

    /**
     * @var Location|null
     */
    protected $receiverDistrict;

    /**
     * @var Location|null
     */
    protected $receiverWard;


    /**
     * @var Location|null
     */
    protected $senderProvince;

    /**
     * @var Location|null
     */
    protected $senderDistrict;

    /**
     * @var Location|null
     */
    protected $senderWard;

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
            'shipping_connect_code' => 'required',
            'shipping_carrier_code' => 'required',
            'ref' => 'required',
            'receiver_name' => 'required',
            'receiver_phone' => 'required',
            'receiver_address' => 'required',
            'receiver_ward_code' => '',
            'receiver_district_code' => 'required',
            'weight' => '',
            'length' => '',
            'height' => '',
            'width'  => '',
            'cod' => ''
        ];
    }


    protected function customValidate()
    {
        $this->shippingPartner = $this->application->shippingPartners()->firstWhere('code', $this->input('shipping_connect_code'));
        if (!$this->shippingPartner instanceof ShippingPartner) {
            $this->errors()->add('shipping_connect_code', static::ERROR_NOT_EXIST);
            return;
        }

        /** @var Order|null $order */
        $order = $this->application->orders()->firstWhere('ref', $this->input['ref']);
        if (!empty($this->input['ref']) && $order) {
            if ($order->tracking_no) {
                $this->errors()->add('ref', static::ERROR_ALREADY_EXIST);
                return;
            } else {
                $order->delete();
            }
        }

        $shippingCarriers = $this->shippingPartner->partner()->getShippingCarriers();
        if(!in_array($this->input('shipping_carrier_code'), $shippingCarriers)) {
            $this->errors()->add('shipping_carrier_code', static::ERROR_NOT_EXIST);
            return;
        }

        if($this->validateReceiverLocation() !== true) {
            return;
        }

        if($this->validateSenderLocation() !== true) {
            return;
        }
    }

    /**
     * @return bool|void
     */
    protected function validateReceiverLocation()
    {

        if( !empty($this->input['receiver_district_code'])) {
            if(!$this->receiverDistrict = Location::query()->firstWhere([
                'code' => $this->input['receiver_district_code'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $this->errors()->add('receiver_district_code', static::ERROR_NOT_EXIST);
                return;
            }

            $this->receiverProvince = $this->receiverDistrict->parent;
        }


        if (!empty($this->input['receiver_ward_code'])) {
            if(!$this->receiverWard = Location::query()->firstWhere([
                'code' => $this->input['receiver_ward_code'],
                'type' => Location::TYPE_WARD
            ])) {
                $this->errors()->add('receiver_ward_code', static::ERROR_NOT_EXIST);
                return;
            }

            if(
                !$this->receiverDistrict instanceof Location ||
                (
                    $this->receiverWard && $this->receiverWard->parent_code != $this->receiverDistrict->code
                )
            ) {
                $this->errors()->add('receiver_ward_code', static::ERROR_INVALID);
                return;
            }
        }

        return true;
    }


    /**
     * @return bool|void
     */
    protected function validateSenderLocation()
    {

        if(!empty($this->input['sender_district_code'])) {
            if(!$this->senderDistrict = Location::query()->firstWhere([
                'code' => $this->input['sender_district_code'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $this->errors()->add('sender_district_code', static::ERROR_NOT_EXIST);
                return;
            }
        }

        if(!empty($this->input['sender_district_code'])) {
            if(!$this->senderWard = Location::query()->firstWhere([
                'code' => $this->input['sender_ward_code'],
                'type' => Location::TYPE_WARD
            ])) {
                $this->errors()->add('sender_ward_code', static::ERROR_NOT_EXIST);
                return;
            }

            if(
                !$this->senderDistrict instanceof Location ||
                (
                    $this->senderWard && $this->senderWard->parent_code != $this->senderDistrict->code
                )
            ) {
                $this->errors()->add('sender_ward_code', static::ERROR_INVALID);
                return;
            }

        }

        if($this->senderDistrict instanceof Location) {
            $this->senderProvince = $this->senderDistrict->parent;
        }

        return true;
    }


    /**
     * @return Location|null
     */
    public function getSenderProvince()
    {
        return $this->senderProvince;
    }

    /**
     * @return Location|null
     */
    public function getReceiverProvince()
    {
        return $this->receiverProvince;
    }

    /**
     * @return Location|null
     */
    public function getReceiverDistrict()
    {
        return $this->receiverDistrict;
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }
}
