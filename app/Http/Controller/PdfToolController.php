<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Constants\ErrorCode;
use App\Http\Request\Pdf\HtmlToPdfRequest;
use Gotenberg\Exceptions\GotenbergApiErroed;
use Gotenberg\Exceptions\NoOutputFileInResponse;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Hyperf\Contract\StdoutLoggerInterface;
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
        $useAgent = $this->request->getHeaderLine("User-Agent");
        $request = Gotenberg::chromium($this->apiUrl)
//            ->pdfFormat()
//            ->omitBackground()
            ->printBackground()
            ->preferCssPageSize()
            ->paperSize(23.4, 33.1)
            ->margins(1, 1, 1, 1)
            ->waitDelay('200ms')
            ->userAgent($useAgent)
            ->url($validated["url"]);

        try {
            $filename = Gotenberg::save($request, $this->path);
        } catch (GotenbergApiErroed $e) {
            return $this->fail(ErrorCode::UNKNOWN, [$e->getMessage()]);
        } catch (NoOutputFileInResponse $e) {
            return $this->fail(ErrorCode::NO_FILE_GOTENBERG);
        }
        return $this->response->download($this->path . $filename, $filename);
    }

    /**
     * @throws NoOutputFileInResponse
     * @throws GotenbergApiErroed
     */
    #[GetMapping(path: "md")]
    public function md(): ResponseInterface
    {
        $request = Gotenberg::chromium($this->apiUrl)->markdown(
            Stream::path($this->path . "index.html"),
            Stream::path($this->path . "211029.md")
        );
        Gotenberg::save($request, $this->path);
        return $this->success();
    }

    #[GetMapping(path: "fileToPdf")]
    public function fileToPdf(): ResponseInterface
    {
        $request = Gotenberg::libreOffice($this->apiUrl)
            ->merge()
            ->convert(
                Stream::path($this->path . "Xlog.pptx"),
                Stream::path($this->path . "jp.xls")
            );
        Gotenberg::save($request, $this->path);
        return $this->success();
    }

}