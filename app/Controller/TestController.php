<?php

namespace App\Controller;

use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: "api/test")]
class TestController extends AbstractController
{

    #[GetMapping(path: "es")]
    public function es(){
        $builder = $this->container->get(ClientBuilderFactory::class)->create();
        $client = $builder->setHosts(['http://elasticsearch:9200'])->build();
        $params = [
            'index' => 'info',
            'body'  => [
                'query' => [
                    'match' => [
                        'testField' => 'abc'
                    ]
                ]
            ]
        ];
        $response = $client->search($params);
        $params = [
            'index' => 'my_index',
            'id'    => 'my_id',
            'body'  => ['testField' => 'abc']
        ];
        $response = $client->index($params);
//        $params = [
//            'index' => 'my_index',
//            'body'  => [
//                'settings' => [
//                    'number_of_shards' => 2,
//                    'number_of_replicas' => 0
//                ]
//            ]
//        ];
//
//        $response = $client->indices()->create($params);
        return $response;
    }

}