<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Constants\ErrorCode;
use GuzzleHttp\Exception\GuzzleException; 
use GuzzleHttp\Exception\RequestException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: "api/web")]
class WebController extends BaseController
{

    #[Inject]
    public ClientFactory $clientFactory;


    #[PostMapping(path: "og")]
    public function queryOgInfo(): array
    {
        $url = $this->request->post("url", "");
        try {
            $client = $this->clientFactory->create([
                'timeout' => 10,
                'verify' => false,
                'allow_redirects' => true
            ]);
            $uri = 'https://metascraper.pages.dev?url=' .  $url;
            $response = $client->request(
                'GET',
                $uri
            )->getBody()->getContents();
            $response = json_decode($response, true); 
            return $response;
        } catch (RequestException $e) {
            $this->logger->info("queryOgInfo curl RequestException=" . $e->getMessage());
            return null;
        } catch (GuzzleException $e) {
            $this->logger->info("queryOgInfo curl GuzzleException=" . $e->getMessage());
            return null;
        }
    }
}
