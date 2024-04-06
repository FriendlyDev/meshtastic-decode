<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class MeshPacketDoesNotContainEncryptedDataException extends Exception
{
    protected $message = 'This Mesh Packet does not contain any encrypted data.';

    protected $code = 500;
}
