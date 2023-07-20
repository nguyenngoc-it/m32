<?php

namespace Modules\Application\Validators;

use App\Base\Validator;
use Modules\Application\Model\Application;
use Modules\User\Models\User;

class RemoveMemberValidator extends Validator
{
    /**
     * @var User
     */
    protected $member;

    /**
     * @var Application
     */
    protected $application;

    /**
     * RemoveMemberValidator constructor.
     * @param array $input
     */
    public function __construct(array $input, Application $application)
    {
        parent::__construct($input);
        $this->application = $application;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'member_id' => 'required|exists:users,id',
        ];
    }

    public function customValidate()
    {
        $this->member = User::find($this->input['member_id']);

        if (!$this->application->hasMember($this->member)) {
            $this->errors()->add('member_id', static::ERROR_INVALID);
            return;
        }
    }

    /**
     * @return User
     */
    public function getMember() {
        return $this->member;
    }
}
