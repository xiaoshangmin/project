<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Constants\ErrorCode;
use App\Http\Request\Pdf\FileToPdfRequest;
use App\Http\Request\Pdf\UrlToPdfRequest;
use App\Http\Service\PdfToolService;
use App\Http\Service\QueueService;
use Gotenberg\Exceptions\GotenbergApiErroed;
use Gotenberg\Exceptions\NoOutputFileInResponse;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: "api/pdfTool")]
class PdfToolController extends BaseController
{

    private string $apiUrl = "pdf:3000";

    private string $path = BASE_PATH . '/storage/';

    private int $maxSize = 10486000;

    #[Inject]
    private QueueService $queueService;

    #[Inject]
    private PdfToolService $pdfToolService;


    #[PostMapping(path: "urlToPdf")]
    public function urlToPdf(UrlToPdfRequest $request): ResponseInterface
    {
        $validated = $request->validated();
        $useAgent = $this->request->getHeaderLine("User-Agent");
        $request = Gotenberg::chromium($this->apiUrl)
//            ->pdfFormat()
//            ->omitBackground()
            ->printBackground()
            ->preferCssPageSize()
            ->paperSize(8.27, 11.7)
//            ->margins(1, 1, 1, 1)
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

    /**
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[PostMapping(path: "fileToPdf")]
    public function upload(FileToPdfRequest $request)
    {
        $file = $request->file('file');

        if ($file->getSize() > $this->maxSize) {
            return $this->fail(ErrorCode::OVER_MAX_SIZE);
        }
        try {
            $uploadRes = $this->pdfToolService->uploadFile($file);
            $pdf = Gotenberg::libreOffice($this->apiUrl)
                ->convert(
                    Stream::path($this->path . $uploadRes['relativePath']),
                );
            $filename = Gotenberg::save($pdf, $this->path);
        } catch (FilesystemException|UnableToWriteFile $exception) {
            $this->logger->error($exception->getMessage());
            return $this->fail(ErrorCode::UPLOAD_FAIL);
        } catch (GotenbergApiErroed $e) {
            return $this->fail(ErrorCode::UNKNOWN, [$e->getMessage()]);
        }

        return $this->response->download($this->path . $filename, $filename);
    }

}