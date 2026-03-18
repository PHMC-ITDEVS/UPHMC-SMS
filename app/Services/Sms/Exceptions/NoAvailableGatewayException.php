<?php

namespace App\Services\Sms\Exceptions;

class NoAvailableGatewayException extends GatewayException
{
    public function __construct()
    {
        parent::__construct(
            'No active SMS gateway is available. ' .
            'Check gateway configuration and modem connectivity.'
        );
    }
}