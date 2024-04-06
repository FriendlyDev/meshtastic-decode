<?php
/** @noinspection StaticClosureCanBeUsedInspection */

use FriendlyDev\MeshtasticDecode\Exceptions\MeshPacketAlreadyContainsDecodedDataException;
use FriendlyDev\MeshtasticDecode\Exceptions\MeshPacketDoesNotContainEncryptedDataException;
use FriendlyDev\MeshtasticDecode\Exceptions\ServiceEnvelopeDecodeFailedException;
use FriendlyDev\MeshtasticDecode\Exceptions\ServiceEnvelopeDoesNotContainMeshPacketException;
use FriendlyDev\MeshtasticDecode\MeshtasticDecode;
use Meshtastic\MeshPacket;
use Meshtastic\ServiceEnvelope;

test('you can enable debug if constructed with it disabled', function (): void {
    $meshtasticDecode = new MeshtasticDecode(debug: false);

    expect(invade($meshtasticDecode)->debug)
        ->toBeFalse();

    $meshtasticDecode->enableDebug();

    expect(invade($meshtasticDecode)->debug)
        ->toBeTrue();
});

test('you can disable debug if constructed with it enabled', function (): void {
    $meshtasticDecode = new MeshtasticDecode(debug: true);

    expect(invade($meshtasticDecode)->debug)
        ->toBeTrue();

    $meshtasticDecode->disableDebug();

    expect(invade($meshtasticDecode)->debug)
        ->toBeFalse();
});

test('you can get the current encryption key', function (): void {
    $meshtasticDecode = new MeshtasticDecode(encryption_key: 'encryption-key');

    expect($meshtasticDecode->getEncryptionKey())
        ->toEqual('encryption-key');
});

test('you can change the encryption key', function (): void {
    $meshtasticDecode = new MeshtasticDecode(encryption_key: 'default-encryption-key');

    expect(invade($meshtasticDecode)->encryption_key)
        ->toEqual('default-encryption-key');

    $meshtasticDecode->setEncryptionKey('changed-encryption-key');

    expect(invade($meshtasticDecode)->encryption_key)
        ->toEqual('changed-encryption-key');
});

test('it can decrypt a given string with the correct values and key', function (): void {
    $meshtasticDecode = Mockery::mock(MeshtasticDecode::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    $decrypted_data = $meshtasticDecode->decryptData(
        id: 1994624120,
        from: 1978765680,
        encrypted: 'FIft4Bw0T4N8i+WiFCCncEz1PhpqzQ==',
        key: 'AQ==',
    );

    expect(base64_encode($decrypted_data))
        ->toEqual('CAMSEg2Uu9kXFchnos4leIkQZrgBIA==')
        ->and($decrypted_data)
        ->toEqual(base64_decode('CAMSEg2Uu9kXFchnos4leIkQZrgBIA=='));
});

test('it can decrypt the MeshPacket encrypted data', function (): void {
    $mesh_packet = new MeshPacket;
    $mesh_packet->mergeFromJsonString('{"from":1978765680,"to":4294967295,"channel":8,"encrypted":"FIft4Bw0T4N8i+WiFCCncEz1PhpqzQ==","id":1994624120,"rxTime":1712359800,"hopLimit":3,"priority":"BACKGROUND"}');

    expect($mesh_packet)
        ->toBeInstanceOf(MeshPacket::class)
        ->and($mesh_packet->hasEncrypted())
        ->toBeTrue()
        ->and($mesh_packet->hasDecoded())
        ->toBeFalse();

    $meshtasticDecode = new MeshtasticDecode();
    $decrypted_mesh_packet = $meshtasticDecode->decryptMeshPacketPayload($mesh_packet);

    expect($decrypted_mesh_packet)
        ->toBeInstanceOf(MeshPacket::class)
        ->and($decrypted_mesh_packet->hasEncrypted())
        ->toBeFalse()
        ->and($decrypted_mesh_packet->hasDecoded())
        ->toBeTrue();
});

test('it throws an exception if the MeshPacket has no encrypted data', function (): void {
    $mesh_packet = new MeshPacket;
    $mesh_packet->mergeFromJsonString('{"from":1978765680,"to":4294967295,"channel":8,"id":1994624120,"rxTime":1712359800,"hopLimit":3,"priority":"BACKGROUND"}');

    $meshtasticDecode = new MeshtasticDecode;
    $meshtasticDecode->decryptMeshPacketPayload($mesh_packet);
})->throws(MeshPacketDoesNotContainEncryptedDataException::class);

test('it throws an exception if the MeshPacket already has decoded data when trying to decrypt it', function (): void {
    $mesh_packet = new MeshPacket;
    $mesh_packet->mergeFromJsonString('{"from":1978765680,"to":4294967295,"channel":8,"decoded":{"portnum":"POSITION_APP","payload":"DZS72RcVyGeiziV4iRBmuAEg"},"id":1994624120,"rxTime":1712359800,"hopLimit":3,"priority":"BACKGROUND"}');

    $meshtasticDecode = new MeshtasticDecode;
    $meshtasticDecode->decryptMeshPacketPayload($mesh_packet);
})->throws(MeshPacketAlreadyContainsDecodedDataException::class);

test('it can decode a MeshPacket out of a ServiceEnvelope', function (): void {
    $serviceEnvelope = new ServiceEnvelope;
    $serviceEnvelope->mergeFromJsonString('{"packet":{"from":1978765680,"to":4294967295,"channel":8,"encrypted":"FIft4Bw0T4N8i+WiFCCncEz1PhpqzQ==","id":1994624120,"rxTime":1712359800,"hopLimit":3,"priority":"BACKGROUND"},"channelId":"LongFast","gatewayId":"!75f19170"}');

    $meshtasticDecode = new MeshtasticDecode;
    $mesh_packet = $meshtasticDecode->decodeMeshPacket($serviceEnvelope);

    expect($mesh_packet)
        ->toBeInstanceOf(MeshPacket::class);
});

test('it throws an exception if a ServiceEnvelope does not contain a MeshPacket when attempting to decode it', function (): void {
    $serviceEnvelope = new ServiceEnvelope;
    $serviceEnvelope->mergeFromJsonString('{"channelId":"LongFast","gatewayId":"!75f19170"}');

    $meshtasticDecode = new MeshtasticDecode;
    $meshtasticDecode->decodeMeshPacket($serviceEnvelope);
})->throws(ServiceEnvelopeDoesNotContainMeshPacketException::class);

test('it can decode a ServiceEnvelope out of the provided binary data', function (): void {
    $payload = 'CjINcJHxdRX/////GAgqFhSH7eAcNE+DfIvlohQgp3BM9T4aas01eIzjdj14iRBmSANYChIITG9uZ0Zhc3QaCSE3NWYxOTE3MA==';

    $meshtasticDecode = new MeshtasticDecode(
        encryption_key: 'AQ==',
    );

    $serviceEnvelope = $meshtasticDecode->decodeServiceEnvelope(base64_decode($payload));

    expect($serviceEnvelope)
        ->toBeInstanceOf(ServiceEnvelope::class);
});

test('it throws an exception if its unable to decode ServiceEnvelope out of the provided binary data', function (): void {
    $payload = 'CjINcJHxdRX/GAgqFhSH7eAcNE+DfIvlohQgp3BM9T4aas01eIzjdj14iRBmSANYChIITG9uZ0Zhc3QaCSE3NWYxOTE3MA==';

    $meshtasticDecode = new MeshtasticDecode(
        encryption_key: 'AQ==',
    );

    $meshtasticDecode->decodeServiceEnvelope(base64_decode($payload));
})->throws(ServiceEnvelopeDecodeFailedException::class);
