<?php

declare(strict_types=1);

namespace App\Http\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping; 
use thiagoalessio\TesseractOCR\TesseractOCR;

#[Controller(prefix: "api/ocr")]
class OpenAiController extends BaseController
{

    #[GetMapping(path: "chat")]
    public function chat(){ 
        $storage = BASE_PATH . '/storage/pdf/text.png';
        return (new TesseractOCR($storage))
            ->run();
    }

}