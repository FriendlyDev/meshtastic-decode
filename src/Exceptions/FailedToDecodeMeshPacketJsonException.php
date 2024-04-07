<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class FailedToDecodeMeshPacketJsonException extends Exception
{
    protected $message = 'Failed to decode the JSON in this Mesh Packet.';

    protected $code = 500;

    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if ($message !== '') {
            $this->message = $message;
        }

        if ($code !== 0) {
            $this->code = $code;
        }

        parent::__construct($this->message, $this->code, $previous);
    }
}
