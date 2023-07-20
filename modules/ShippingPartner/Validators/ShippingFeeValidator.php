<?php

namespace Modules\ShippingPartner\Validators;

use App\Base\Validator;
use Modules\Application\Model\Application;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerSize;

class ShippingFeeValidator extends Validator
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

    protected $shippingPartnerSize;

    /**
     * CreateOrderValidator constructor.
     * @param Application $application
     * @param array $input
     */
    public function __construct(Application $application, array $input)
    {
        $this->application = $application;
        $this->shippingPartnerSize = new ShippingPartnerSize();
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required',
            'sender_district_code' => 'required',
            'receiver_ward_code' => 'required',
            'receiver_district_code' => 'required',
            'weight' => 'required',
            'length' => 'required',
            'height' => 'required',
            'width'  => 'required',
        ];
    }


    protected function customValidate()
    {
        $this->shippingPartner = $this->application->shippingPartners()->firstWhere('code', $this->input('code'));
        if (!$this->shippingPartner instanceof ShippingPartner) {
            $this->errors()->add('code', static::ERROR_NOT_EXIST);
            return;
        }

        if($this->validateReceiverLocation() !== true) {
            return;
        }

        if($this->validateSenderLocation() !== true) {
            return;
        }

        foreach (['weight', 'length', 'height', 'width'] as $key) {
            $this->shippingPartnerSize->{$key} = floatval($this->input[$key]);
        }

    }

    /**
     * @return bool|void
     */
    protected function validateReceiverLocation()
    {
        if(!$this->receiverDistrict = Location::query()->firstWhere([
            'code' => $this->input['receiver_district_code'],
            'type' => Location::TYPE_DISTRICT
        ])) {
            $this->errors()->add('receiver_district_code', static::ERROR_NOT_EXIST);
            return;
        }

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

        $this->receiverProvince = $this->receiverDistrict->parent;

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
    public function getSenderDistrict()
    {
        return $this->senderDistrict;
    }


    /**
     * @return Location|null
     */
    public function getSenderWard()
    {
        return $this->senderWard;
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
        return $this->senderDistrict;
    }


    /**
     * @return Location|null
     */
    public function getReceiverWard()
    {
        return $this->senderWard;
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }

    /**
     * @return ShippingPartnerSize
     */
    public function getShippingPartnerSize()
    {
        return $this->shippingPartnerSize;
    }
}
