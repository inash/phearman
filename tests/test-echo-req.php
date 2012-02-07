<?php

/**
 * Send an ECHO_REQ to a Gearman server and output the response ECHO_RES
 * packet.
 *
 * @author Inash Zubair <inash@leptone.com>
 */

require_once '../phearman_init.php';

use Phearman\Client;

try {
    $client = new Client();
    $response = $client->echoRequest('Hello Gearman');
    print_r($response);
} catch (Exception $e) {
    printf(
        "Exception: %s\n     File: %s\n     Line: %s\n",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine());
}
