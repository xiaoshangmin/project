<?php

namespace App\Controller;

use Gotenberg\Gotenberg;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: "pdfTool")]
class PdfToolController extends AbstractController
{

    private string $apiUrl = "pdf:3000";

    private string $path = BASE_PATH . '/storage/';

    #[GetMapping(path: "test")]
    public function test()
    {
        Gotenberg::save(
            Gotenberg::chromium($this->apiUrl)->url("https://hyperf.wiki/3.0/#/"),
            $this->path
        );
    }

}