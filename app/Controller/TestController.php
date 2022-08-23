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

    #[GetMapping(path: "tt")]
    public function tt(){
        return __METHOD__;
    }

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
        $cert = BASE_PATH . '/cacert.pem';
        $client = $builder->setHosts(['https://xlog.mailigf.com'])->setBasicAuthentication('xlog', 'xlogMLGF2020')
            ->setSSLVerification($cert)->build();
        $params = [
            'index' => 'info',
            'from' => 0,
            'size' => 30,
        ];
//        $response = $client->search($params);

        return $client->info();
    }

}