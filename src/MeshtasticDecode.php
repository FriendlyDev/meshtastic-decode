<?php

declare(strict_types=1);

namespace FriendlyDev\MeshtasticDecode;

use FriendlyDev\MeshtasticDecode\Exceptions\EncryptedDataDecryptionFailedException;
use FriendlyDev\MeshtasticDecode\Exceptions\FailedToCreateDataObjectFromDecryptedBytesException;
use FriendlyDev\MeshtasticDecode\Exceptions\FailedToDecodeMeshPacketJsonException;
use FriendlyDev\MeshtasticDecode\Exceptions\MeshPacketAlreadyContainsDecodedDataException;
use FriendlyDev\MeshtasticDecode\Exceptions\MeshPacketDoesNotContainEncryptedDataException;
use FriendlyDev\MeshtasticDecode\Exceptions\ServiceEnvelopeDecodeFailedException;
use FriendlyDev\MeshtasticDecode\Exceptions\ServiceEnvelopeDoesNotContainMeshPacketException;
use Meshtastic\Data;
use Meshtastic\MeshPacket;
use Meshtastic\ServiceEnvelope;
use Throwable;

class MeshtasticDecode
{
    /**
     * MeshtasticDecode constructor
     *
     * @param  bool  $debug
     * @param  string  $encryption_key
     */
    public function __construct(
        protected bool $debug = false,
        protected string $encryption_key = 'AQ==',
    ) {
    }

    /**
     * Enable debug mode
     *
     * @return void
     */
    public function enableDebug(): void
    {
        $this->debug = true;
    }

    /**
     * Disable debug mode
     *
     * @return void
     */
    public function disableDebug(): void
    {
        $this->debug = false;
    }

    /**
     * Get the encryption key
     *
     * @return string
     */
    public function getEncryptionKey(): string
    {
        return $this->encryption_key;
    }

    /**
     * Set the encryption key
     *
     * @param  string  $encryption_key
     * @return void
     */
    public function setEncryptionKey(string $encryption_key): void
    {
        $this->encryption_key = $encryption_key;
    }

    /**
     * Decode a ServiceEnvelope from binary data, base64 encoded
     *
     * @param  string  $message
     * @return ServiceEnvelope
     * @throws ServiceEnvelopeDecodeFailedException
     */
    public function decodeServiceEnvelope(string $message): ServiceEnvelope
    {
        $service_envelope = new ServiceEnvelope;

        try {
            $service_envelope->mergeFromString($message);
        } catch (Throwable $e) {
            throw new ServiceEnvelopeDecodeFailedException(
                previous: $e,
            );
        }

        return $service_envelope;
    }

    /**
     * Decode a MeshPacket from a ServiceEnvelope
     *
     * @param  ServiceEnvelope  $service_envelope
     * @throws ServiceEnvelopeDoesNotContainMeshPacketException
     */
    public function decodeMeshPacket(ServiceEnvelope $service_envelope): ServiceEnvelope
    {
        if (! $service_envelope->hasPacket()) {
            throw new ServiceEnvelopeDoesNotContainMeshPacketException;
        }

        $mesh_packet = $service_envelope->getPacket();

        if (null === $mesh_packet) {
            throw new ServiceEnvelopeDoesNotContainMeshPacketException;
        }

        return $service_envelope;
    }

    /**
     * Decrypt the payload of a MeshPacket
     *
     * @param  MeshPacket  $mesh_packet
     * @return MeshPacket
     * @throws FailedToDecodeMeshPacketJsonException
     * @throws EncryptedDataDecryptionFailedException
     * @throws FailedToCreateDataObjectFromDecryptedBytesException
     * @throws MeshPacketDoesNotContainEncryptedDataException
     * @throws MeshPacketAlreadyContainsDecodedDataException
     */
    public function decryptMeshPacketPayload(MeshPacket $mesh_packet): MeshPacket
    {
        if (! $mesh_packet->hasEncrypted()) {
            throw new MeshPacketDoesNotContainEncryptedDataException;
        }

        if ($mesh_packet->hasDecoded()) {
            throw new MeshPacketAlreadyContainsDecodedDataException;
        }

        if ($this->debug) {
            print "MeshPacket has encrypted data. Attempting to decrypt...\n";
            print base64_encode($mesh_packet?->getEncrypted()) . "\n\n";
        }

        // Get our normalized packet
        try {
            $packet = (object) json_decode(
                json: $mesh_packet->serializeToJsonString(),
                associative: false,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $e) {
            throw new FailedToDecodeMeshPacketJsonException(
                previous: $e,
            );
        }

        $decrypted_data = $this->decryptData(
            id: $packet->id,
            from: $packet->from,
            encrypted: $packet->encrypted,
            key: $this->encryption_key,
        );

        return $this->pushDecryptedDataBackIntoThePacket(
            mesh_packet: $mesh_packet,
            decrypted_data: $decrypted_data,
        );
    }

    /**
     * Decrypt encrypted data
     *
     * @param  int  $id
     * @param  int  $from
     * @param  string  $encrypted
     * @param  string  $key
     * @return string
     * @throws EncryptedDataDecryptionFailedException
     */
    protected function decryptData(int $id, int $from, string $encrypted, string $key = 'AQ=='): string
    {
        if ('AQ==' === $key) {
            // Default key, expanding to AES128 key
            $key = '1PG7OiApB1nwvP+rz05pAQ==';
        }

        try {
            $decrypted_bytes = openssl_decrypt(
                data: base64_decode($encrypted),
                cipher_algo: 'aes-128-ctr',
                passphrase: base64_decode($key),
                options: OPENSSL_RAW_DATA,
                iv: pack('P', $id) . pack('P', $from),
            );
        } catch (Throwable $e) {
            throw new EncryptedDataDecryptionFailedException(
                previous: $e,
            );
        }

        return $decrypted_bytes;
    }

    /**
     * Push decrypted data back into the MeshPacket
     *
     * @param  MeshPacket  $mesh_packet
     * @param  string  $decrypted_data
     * @return MeshPacket
     * @throws FailedToCreateDataObjectFromDecryptedBytesException
     */
    protected function pushDecryptedDataBackIntoThePacket(MeshPacket $mesh_packet, string $decrypted_data): MeshPacket
    {
        // Parse decrypted bytes into Data object
        $data = new Data;

        try {
            $data->mergeFromString($decrypted_data);
        } catch (Throwable $e) {
            throw new FailedToCreateDataObjectFromDecryptedBytesException(
                previous: $e,
            );
        }

        if ($this->debug) {
            print "Data was decrypted successfully.\n";
            print json_encode(
                value: json_decode($data->serializeToJsonString()),
                flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK,
            );
            print "\n\n";
        }

        // Push the decoded data back into the MeshPacket
        $mesh_packet->setDecoded($data);

        return $mesh_packet;
    }
}
