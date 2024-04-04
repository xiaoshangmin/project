<?php

declare(strict_types=1);

namespace App\Http\Controller;

use Gotenberg\Exceptions\GotenbergApiErrored;
use Gotenberg\Exceptions\NoOutputFileInResponse;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use GuzzleHttp\Client;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use thiagoalessio\TesseractOCR\TesseractOCR;
use mishagp\OCRmyPDF\OCRmyPDF;

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

    #[GetMapping(path: "ocr")]
    public function ocr()
    {
        $storage = BASE_PATH . '/storage/pdf/mypdf.pdf';
        return OCRmyPDF::make($storage)->run();
    }


    /**
     * @throws NoOutputFileInResponse
     * @throws GotenbergApiErrored
     */
    #[GetMapping(path: "pdf")]
    public function pdf()
    {
        $apiUrl = 'http://gotenberg:3000';
//        $filename = Gotenberg::save(
//            Gotenberg::chromium("http://gotenberg:3000")->pdf()->outputFilename("mypdf")->url('https://baidu.com'),
//            BASE_PATH . '/storage/pdf'
//        );
//        $request = Gotenberg::libreOffice("http://gotenberg:3000")
//            ->convert(Stream::path(BASE_PATH . '/storage/pdf/2023.docx'), Stream::path(BASE_PATH . '/storage/pdf/test.xlsx'));
//


        $request = Gotenberg::pdfEngines($apiUrl)
            ->pdfua()
            ->convert(
                'PDF/A-1a',
                Stream::path(BASE_PATH . '/storage/pdf/mypdf.pdf')
            );
        Gotenberg::send($request);
//            ->merge(
//                Stream::path(BASE_PATH . '/storage/pdf/mypdf.pdf'),
//                Stream::path(BASE_PATH . '/storage/pdf/mypdf.pdf')
//            );
//        Gotenberg::save($request, BASE_PATH . '/storage');
    }

}