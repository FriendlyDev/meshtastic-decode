<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode\Exceptions;

use Exception;

class EncryptedDataDecryptionFailedException extends Exception
{
    protected $message = 'Failed to decrypt the encrypted data.';

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
