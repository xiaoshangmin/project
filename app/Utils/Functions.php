<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;


if (!function_exists('getContent')) {

    function getContent($url, $headers = [], $decoded = True)
    {
        /**Gets the content of a URL via sending a HTTP GET request.
         *
         * Args:
         * url: A URL.
         * headers: Request headers used by the client.
         * decoded: Whether decode the response body using UTF-8 or the charset specified in Content-Type.
         *
         * Returns:
         * The content as a string.
         **/
        $client = new Client([
            'timeout' => 5,
            'verify' => false
        ]);
        $options = [
            'headers' => $headers,
            'decode_content' => 'gzip,deflate',
            'allow_redirects' => true,
        ];
        if (!empty($cookies)) {
            $cookies = CookieJar::fromArray($cookies, $url);
            $options['cookies'] = $cookies;
        }
        $response = $client->get($url, $options);
        return $response->getBody()->getContents();
    }
}