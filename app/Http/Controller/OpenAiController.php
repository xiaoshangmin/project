<?php

declare(strict_types=1);

namespace App\Http\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;


#[Controller(prefix: "api/ocr")]
class OpenAiController extends BaseController
{

    #[GetMapping(path: "chat")]
    public function chat()
    {
        $storage = BASE_PATH . '/storage/pdf/123.jpg';
        return (new TesseractOCR($storage))
            ->run();
    }

    #[GetMapping(path: "pdf")]
    public function pdf()
    {
//        $filename = Gotenberg::save(
//            Gotenberg::chromium("http://gotenberg:3000")->pdf()->outputFilename("mypdf")->url('https://baidu.com'),
//            BASE_PATH . '/storage/pdf'
//        );
        $request = Gotenberg::libreOffice("http://gotenberg:3000")
            ->convert(Stream::path(BASE_PATH . '/storage/pdf/2023.docx'));
        Gotenberg::save($request, BASE_PATH . '/storage');
    }

}