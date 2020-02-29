<?php
/**
 *
 * User: silva
 * Date: 2019-01-14
 * Time: 20:50
 */

use CODOF\Util;

/**
 * @param int $length
 * @param string $abc
 * @return string
 */
function generateRandomString($length = 10, $abc = "ABCDEFGHIJKLMNOPQRSTUVWXYZ")
{
    return substr(str_shuffle($abc), 0, $length);
}

function getNewFreiChatKey()
{

    $data = array(
        'domain' => $_SERVER['HTTP_HOST'],
        'platform' => 'Codoforum'
    );

    $payload = json_encode($data);

    // Prepare new cURL resource
    if (MODE === \Constants::MODE_PRODUCTION) {
        $freiChatBaseURL = "https://nodelb.freichat.com/api";
    } else {
        $freiChatBaseURL = "http://localhost:8080/api";
    }

    $ch = curl_init("$freiChatBaseURL/v1/keys/free/register");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Set HTTP Header for POST request
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload))
    );

    // Submit the POST request
    $key = curl_exec($ch);

    return $key;
}


$exists = \CODOF\Util::optionExists('FREICHAT_APP_KEY');
if (!$exists || Util::get_opt("FREICHAT_APP_KEY") === "") {
    $key = getNewFreiChatKey();
    \CODOF\Util::set_opt('FREICHAT_APP_KEY', $key);
}


