<?php

namespace App\Controller;

use App\Request\HtmlToPdfRequest;
use Gotenberg\Exceptions\GotenbergApiErroed;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: "pdfTool")]
class PdfToolController extends AbstractController
{

    private string $apiUrl = "pdf:3000";

    private string $path = BASE_PATH . '/storage/';


    #[GetMapping(path: "htmlToPdf")]
    public function htmlToPdf(HtmlToPdfRequest $request): \Psr\Http\Message\ResponseInterface
    {
        $request = Gotenberg::chromium($this->apiUrl)
//            ->printBackground()
//            ->preferCssPageSize()
            ->paperSize(23.4, 33.1)
//            ->margins(1,1,1,1)
            ->outputFilename("abcd")
//            ->failOnConsoleExceptions()
            ->waitDelay('2s')
            ->userAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36")
            ->url($request->input("url"));
        try {

            Gotenberg::save(
                $request,
                $this->path
            );
        } catch (GotenbergApiErroed $e) {
            return $this->fail(22, [$e->getGotenbergTrace(), $e->getMessage()]);

        }
        return $this->success([]);
    }

    /**
     * @throws \Gotenberg\Exceptions\NoOutputFileInResponse
     * @throws GotenbergApiErroed
     */
    #[GetMapping(path: "md")]
    public function md(): \Psr\Http\Message\ResponseInterface
    {
        $request = Gotenberg::chromium($this->apiUrl)->outputFilename("mdmd")->markdown(
            Stream::path($this->path . "index.html"),
            Stream::path($this->path . "211029.md")
        );
        Gotenberg::save($request, $this->path);
        return $this->success();
    }

}