<?php

namespace App\Controller;

use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use League\Flysystem\StorageAttributes;

#[Controller(prefix: "api/test")]
class TestController extends AbstractController
{

    #[GetMapping(path: "test")]
    public function test(FilesystemFactory $factory)
    {
        $local = $factory->get('local');
        $list = $local->listContents('/office', true)->filter(fn(StorageAttributes $attributes) => $attributes->isFile())->toArray();
        try {
            foreach ($list as $item) {
                if (time() - $item->lastModified() > 4 * 3600) {
                    $dir = dirname($item->path());
                    $local->deleteDirectory($dir);
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    #[GetMapping(path: "es")]
    public function es()
    {
        $builder = $this->container->get(ClientBuilderFactory::class)->create();
        $client = $builder->setHosts(['http://elasticsearch:9200'])->build();
        $params = [
            'index' => 'info',
            'body' => [
                'query' => [
                    'match' => [
                        'testField' => 'abc'
                    ]
                ]
            ]
        ];
//        $response = $client->search($params);
        $params = [
            'index' => 'info-2022.08.06',
            'id' => 'oms',
            'body'  => [
                'host' => 'oms-api',
                'http_user_agent' => 'MacOs',
            ]
        ];
//        $response = $client->index($params);
        $params = [
            'index' => 'info-*',
            "size" => 50,
            'body' => [
                'query' => [
                    'match' => [
                        'domain' => 'xsm'
                    ]
                ]
            ]
        ];

        $response = $client->search($params);
        return $response;
    }

}