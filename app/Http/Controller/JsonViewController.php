<?php

declare(strict_types=1);

namespace App\Http\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Symfony\Component\Yaml\Yaml;

#[Controller(prefix: "api/json")]
class JsonViewController extends BaseController
{
    #[PostMapping(path: "toyaml")]
    public function json2yaml()
    {
        $code = $this->request->post('code');
        return $this->success(Yaml::dump($code));
    }
}