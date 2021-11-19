<?php

namespace Avantis\Api\Helper;

use Bitrix\Main\ErrorCollection;

class Result extends \Bitrix\Main\Result
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addData($data)
    {
        $this->data[] = $data;
        return $this;
    }

    public function getResponse()
    {
        $response = array();

        if (!empty($this->getErrors())) {
            $response['success'] = false;
        } else {
            $response['success'] = true;
        }

        $response['message'] = implode("\n", $this->getErrorMessages());

        $response = array_merge($response, $this->getData());

        return $response;
    }

    /**
     * Sets data of the result.
     * @param mixed $data
     * @return \Bitrix\Main\Result
     */
    public function set(string $name, $data)
    {
        $this->data[$name] = $data;

        return $this;
    }

    /**
     * @param ErrorCollection $errors
     * @return Result
     */
    public function setErrorCollection(ErrorCollection $errors): Result
    {
        $this->errors = $errors;
        if (count($errors) > 0) {
            $this->isSuccess = false;
        }
        return $this;
    }
}
