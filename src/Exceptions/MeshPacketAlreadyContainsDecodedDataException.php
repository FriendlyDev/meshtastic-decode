<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class MeshPacketAlreadyContainsDecodedDataException extends Exception
{
    protected $message = 'This Mesh Packet already contains decoded data.';

    protected $code = 500;
}
