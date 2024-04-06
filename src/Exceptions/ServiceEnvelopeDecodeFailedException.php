<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class ServiceEnvelopeDecodeFailedException extends Exception
{
    protected $message = 'Failed create a Meshtastic ServiceEnvelope from the given string.';

    protected $code = 500;
}
