<?php

namespace Denbog\Oauth2Wellcomesid\Provider\Exception;

class WellcomesIdException extends \Exception
{
    protected $errors;

    public function __construct($message, $code, $errors)
    {
        $this->errors = $errors;

        parent::__construct($message, $code);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}