<?php

namespace App\Http\Service;

use App\Contract\SpiderInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Psr\Http\Message\StreamInterface;

abstract class Spider implements SpiderInterface
{
    #[Inject]
    public ClientFactory $clientFactory;

    #[Inject]
    public StdoutLoggerInterface $logger;

    //自动302重定向
    public function curl(string $url, array $header = [], array $body = [], array $formParams = []): ?StreamInterface
    {
        $headers = [
            'User-Agent' => self::UA
        ];
        if (!empty($header)) {
            $headers = array_merge($headers, $header);
        }
        $cookieJar = new CookieJar();
        try {
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'headers' => $headers,
                'allow_redirects' => ['cookies' => true],
                'cookies' => $cookieJar, // 使用cookie jar
            ]);
            if (!empty($formParams)) {
                $response = $client->post($url, [
//                    'decode_content' => 'gzip,deflate',
//                    'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                    'form_params' => $formParams
                    // 'cookies' => $cookieJar,
                ]);
            } else if (!empty($body)) {
                $response = $client->post($url, [
//                    'decode_content' => 'gzip,deflate',
                    'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                    // 'cookies' => $cookieJar,
                ]);
            } else {
                $response = $client->get($url);
            }

            return $response->getBody();
        } catch (RequestException $e) {
            $this->logger->info("spider curl RequestException=" . $e->getMessage());
            return null;
        } catch (GuzzleException $e) {
            $this->logger->info("spider curl GuzzleException=" . $e->getMessage());
            return null;
        }

    }

    public function video(string $title, string $cover, string $url): array
    {
        return ['type' => 'video', 'title' => $title, 'cover' => $cover, 'videoUrl' => $url];
    }

    public function images(array $pics, string $title): array
    {
        return ['type' => 'images', 'pics' => $pics, 'title' => $title,];
    }
}