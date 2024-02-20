<?php

use App\Util\Sign;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;


/**
 * 小程序接口验证参数
 * @param mixed $var 签名验证的参数
 * @param string $signType 默认为md5
 * @return array
 */
if (!function_exists('miniSignCheck')) {
    function miniSignCheck(array $params = [], string $signType = 'md5'): bool
    {
        if (empty($params['sign'])) {
            return false;
        } else {
            $secret = 'a87ff679a2f3e71d9181a67b7542122c';
            $sign = Sign::createSignature($params, $secret, $signType);//参数验签
            if ($params['sign'] == $sign) {
                return true;
            } else {
                return false;
            }
        }
    }
}


if (!function_exists('getContent')) {

    function getContent(string $url, array $headers = [], bool $decoded = True)
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