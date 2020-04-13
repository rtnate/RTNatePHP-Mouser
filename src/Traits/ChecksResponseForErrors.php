<?php

namespace RTNatePHP\Mouser\Traits;

use RTNatePHP\Mouser\MouserException;

trait ChecksResponseForErrors{

    protected function checkForErrors(array $data)
    {
        if (array_key_exists('Errors', $data))
        {
            $errors = $data['Errors'];
            if ($errors) return $errors;
        }
        else return [];
    }

    
    protected function verifyResponse(array $data)
    {
        $errors = $this->checkForErrors($data);
        if ($errors)
        {
            $readable = print_r($errors, true);
            $message = "Mouser Returned the following Errors: {$readable}";
            throw new MouserException($message);
        }
    }
}