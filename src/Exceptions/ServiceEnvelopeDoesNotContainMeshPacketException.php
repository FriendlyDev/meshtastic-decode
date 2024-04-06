<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class ServiceEnvelopeDoesNotContainMeshPacketException extends Exception
{
    protected $message = 'No Mesh Packet was found within this Service Envelope';

    protected $code = 500;
}
