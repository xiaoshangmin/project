<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Constants\ErrorCode;
use App\Http\Request\Pdf\HtmlToPdfRequest;
use Gotenberg\Exceptions\GotenbergApiErroed;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: "pdfTool")]
class PdfToolController extends BaseController
{

    private string $apiUrl = "pdf:3000";

    private string $path = BASE_PATH . '/storage/';


    #[GetMapping(path: "htmlToPdf")]
    public function htmlToPdf(HtmlToPdfRequest $request): ResponseInterface
    {
        $validated = $request->validated();
        $request = Gotenberg::chromium($this->apiUrl)
//            ->printBackground()
//            ->preferCssPageSize()
            ->paperSize(23.4, 33.1)
            ->margins(1, 1, 1, 1)
            ->outputFilename("abcd")
            ->waitDelay('200ms')
            ->userAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36")
            ->url($validated["url"]);

        try {
            Gotenberg::save($request, $this->path);
        } catch (GotenbergApiErroed $e) {
            return $this->fail(ErrorCode::UNKNOWN, [$e->getGotenbergTrace(), $e->getMessage()]);
        }
        return $this->success([]);
    }

    /**
     * @throws \Gotenberg\Exceptions\NoOutputFileInResponse
     * @throws GotenbergApiErroed
     */
    #[GetMapping(path: "md")]
    public function md(): ResponseInterface
    {
        $request = Gotenberg::chromium($this->apiUrl)->outputFilename("mdmd")->markdown(
            Stream::path($this->path . "index.html"),
            Stream::path($this->path . "211029.md")
        );
        Gotenberg::save($request, $this->path);
        return $this->success();
    }

}