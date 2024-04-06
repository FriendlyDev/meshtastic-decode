<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class FailedToCreateDataObjectFromDecryptedBytesException extends Exception
{
    protected $message = 'Failed to create a data object from the decrypted bytes.';

    protected $code = 500;
}
