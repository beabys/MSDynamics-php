<?php

namespace MSDynamics\Client;

use Exception;

/**
 * Class Curl
 * @package MSDynamics\Client
 */
class Curl
{

    /**
     * @param $postUrl
     * @param $hostname
     * @param $soapUrl
     * @param $content
     * @return mixed
     * @throws Exception
     */
    public function doCurl($postUrl, $hostname, $soapUrl, $content)
    {
        $headers = array(
            "POST ". $postUrl ." HTTP/1.1",
            "Host: " . $hostname,
            'Connection: Keep-Alive',
            "Content-type: application/soap+xml; charset=UTF-8",
            "Content-length: ".strlen($content),
        );

        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_URL, $soapUrl);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cURL, CURLOPT_TIMEOUT, 60);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($cURL, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($cURL, CURLOPT_POST, 1);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $content);

        try {
            $response = curl_exec($cURL);
            if($errno = curl_errno($cURL)) {
                $error_message = curl_strerror($errno);
                throw new Exception("cURL error ({$errno}):\n {$error_message}");
            }
            curl_close($cURL);
        } catch (Exception $e) {
            throw new Exception($e);
        }
        return $response;
    }

    /**
     * @param $fileName
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    public function setXML($fileName, array $params)
    {
        $file = __DIR__ . "/Templates/xml/" . $fileName . ".xml";
        if (!file_exists($file)) {
            throw new Exception('invalid XML source');
        }
        $xml = file_get_contents($file);
        foreach ($params as $key => $value) {
            $xml = str_replace('%' . strtoupper($key) . '%', $value, $xml);
        }
        return $xml;
    }


    /**
     * @return string
     */
    public function getUUID()
    {
        mt_srand ( ( double ) microtime () * 10000 ); // optional for php 4.2.0 and up.
        $charid = strtoupper ( md5 ( uniqid ( rand (), true ) ) );
        $hyphen = chr ( 45 ); // "-"
        $uuid = chr ( 123 ) . // "{"
            substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 4 ) . $hyphen . substr ( $charid, 20, 12 ) . chr ( 125 ); // "}"
        return $uuid;
    }
}
