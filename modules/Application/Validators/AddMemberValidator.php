<?php

namespace Modules\Application\Validators;

use App\Base\Validator;
use Modules\Application\Model\Application;
use Modules\User\Models\User;

class AddMemberValidator extends Validator
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Application
     */
    protected $application;

    /**
     * AddMemberValidator constructor.
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
            'email' => 'required',
        ];
    }

    public function customValidate()
    {
        $email = trim($this->input['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors()->add('email', static::ERROR_INVALID);
            return;
        }

        $this->user = User::firstOrCreate([ 'email' => $email ]);

        if ($this->application->hasMember($this->user)) {
            $this->errors()->add('member', static::ERROR_ALREADY_EXIST);
            return;
        }
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }
}
