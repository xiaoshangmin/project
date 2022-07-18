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
        $info = $client->info();
        var_dump($info);
    }

}