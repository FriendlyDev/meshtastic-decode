<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class FailedToDecodeMeshPacketJsonException extends Exception
{
    protected $message = 'Failed to decode the JSON in this Mesh Packet.';

    protected $code = 500;
}
