<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class EncryptedDataDecryptionFailedException extends Exception
{
    protected $message = 'Failed to decrypt the encrypted data.';

    protected $code = 500;
}
