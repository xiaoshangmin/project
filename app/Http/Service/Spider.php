<?php

namespace App\Http\Service;

use App\Contract\SpiderInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;

abstract class Spider implements SpiderInterface
{
    #[Inject]
    public ClientFactory $clientFactory;

    #[Inject]
    public StdoutLoggerInterface $logger;

    //自动302重定向
    public function curl(string $url, array $header = [], array $data = [])
    {
        $headers = [
            'User-Agent' => self::UA
        ];
        if (!empty($header)) {
            $headers = array_merge($headers, $header);
        }
        try {
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'headers' => $headers,
            ]);
            if (!empty($data)) {
                $response = $client->post($url, [
//                'decode_content' => 'gzip,deflate',
//                'allow_redirects' => true,
                    'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
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