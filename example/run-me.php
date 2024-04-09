<?php

include(__DIR__ . '/../vendor/autoload.php');

use FriendlyDev\MeshtasticDecode\Exceptions\JsonDataProvidedException;
use FriendlyDev\MeshtasticDecode\MeshtasticDecode;

function horizontalLineBreak(): void
{
    print "\n\n\n";
    print str_repeat('= ', 50);
    print "\n\n\n";
}

function printException(Throwable $e): void
{
    print "    - " . get_class($e) . "\n";
    print "    - " . $e->getMessage() . "\n\n\n";
}

function outputJsonResults(string $json): void
{
    print json_encode(
        value: json_decode($json),
        flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK,
    );

    horizontalLineBreak();
}

$serviceEnvelopes = [
    'TEXT_MESSAGE_APP' => 'CjENZTEcwRX/////IgkIARIFSGVsbG81oE1Edz3PgRBmRQAAGEFIBmC8//////////8BEghMb25nRmFzdBoJIWUyZTQ5Mzkw',
    'TELEMETRY_APP' => 'CjENwPd1+hX/////IhcIQxITDUDLD2YSDAhcFfp+gkAlws+zPjXunXhqPUDLD2ZIA1gBEghMb25nRmFzdBoJIWZhNzVmN2Mw',
    'NODEINFO_APP' => 'CkYNwPd1+hX/////IiwIBBIoCgkhZmE3NWY3YzASCUtsYWFzd2FhbBoES0xXMSIG9BL6dffAKCw4AzUyn3hqPSeCEGZIA1gKEghMb25nRmFzdBoJIWZhNzVmN2Mw',
    'MAP_REPORT_APP' => 'CkINKOBW2hX/////IjYISRIyCgZFSTRJREISA1RvbSArKg0yLjMuMC41ZjQ3Y2ExMANAAU3TAssfVR4hSfxYHmAgaAESCExvbmdGYXN0GgkhZGE1NmUwMjg=',
    'STORE_FORWARD_APP' => 'CicNqCce9xX/////IgsIQRIHCAIiAwisAjUM0iIvPQ+DEGZIA1gBaAESCExvbmdGYXN0GgkhZjcxZTI3YTg=',
    'POSITION_APP' => 'Cj4NfFZW2hX/////IhYIAxISDbgvVx0VP1kltyU9gxBmuAEgNZKiw3I9mIMQZkUAAHDASAFgkP//////////ARIITG9uZ0Zhc3QaCSFkYTYzOTAyNA==',
    'NEIGHBORINFO_APP' => 'CmQNvGtU2hX/////IjoIRxI2CLzX0dINELzX0dINGIQHIgsIsIKXlw4VAADIQCILCNCgjpcOFQAAuEAiCwispr3TDxUAAGDBNXbWlQY9+YMQZkUAAPhASANg7f//////////AXgDEghMb25nRmFzdBoJIWUyZTM5MDUw',
    'ROUTING_APP' => 'CjENoENX2hUS3tfhIgsIBRICGAg1v1ghezW/L+l+PSmEEGZFAAAwQWDW//////////8BEghMb25nRmFzdBoJIWUyZTQ5Mzkw',
    'RANGE_TEST_APP' => 'CjMNTPSIkxX/////IgsIQhIHc2VxIDI1NzWXORBcPaiEEGZFAABEwUgBYIr//////////wESCExvbmdGYXN0GgkhZGE1NTExODg=',
    'TRACEROUTE_APP' => 'CiYNVPFW2hUsImLaIgwIRhIGCgSQk+TiGAE1ByEdkj2ThRBmSAZQARIITG9uZ0Zhc3QaCSFlMmU0OTM5MA==',
    'POSITION_APP' => 'CjINcJHxdRX/////GAgqFhSH7eAcNE+DfIvlohQgp3BM9T4aas01eIzjdj14iRBmSANYChIITG9uZ0Zhc3QaCSE3NWYxOTE3MA==',
    'JSON_DATA' => base64_encode('{"channel":0,"from":4201166456,"hops_away":0,"id":935928891,"payload":{"hardware":44,"id":"!fa68b678","longname":"P2000 Portal \u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000","shortname":"P200"},"rssi":-85,"sender":"!e2e52244","snr":6.5,"timestamp":1712653096,"to":4294967295,"type":"nodeinfo"}'),
];

$meshtastic = new MeshtasticDecode();
$meshtastic->enableDebug();

horizontalLineBreak();

foreach ($serviceEnvelopes as $packetName => $binaryData) {
    $errored = false;

    print "Decoding {$packetName}\n";

    try {
        $serviceEnvelope = $meshtastic->decodeServiceEnvelope(base64_decode($binaryData));
    } catch (JsonDataProvidedException $e) {
        printException($e);
        outputJsonResults(base64_decode($binaryData));

        continue;
    } catch (Throwable $e) {
        printException($e);
        $errored = true;
    }

    try {
        $meshPacket = $meshtastic->decodeMeshPacket($serviceEnvelope);
    } catch (Throwable $e) {
        printException($e);
        $errored = true;
    }

    // Decrypt the packet
    if (! $errored) {
        try {
            $meshPacket = $meshtastic->decryptMeshPacketPayload($meshPacket);
        } catch (Throwable $e) {
            printException($e);
            $errored = true;
        }
    }

    // Push the decrypted data back into the ServiceEnvelope
    if (! $errored) {
        try {
            $serviceEnvelope->setPacket($meshPacket);
        } catch (Throwable $e) {
            printException($e);
        }
    }

    outputJsonResults($serviceEnvelope->serializeToJsonString());
}
