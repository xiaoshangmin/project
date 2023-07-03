<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Http\Controller;

use App\Constants\ErrorCode;
use App\Http\Service\OfficeService;
use App\Http\Service\QueueService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

#[Controller(prefix: "api/pdfToPic")]
class PdfToPicController extends BaseController
{

    private int $maxSize = 5243000;

    #[Inject]
    protected QueueService $service;

    #[Inject]
    protected OfficeService $officeService;

    #[GetMapping(path: "index")]
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();
        return $this->success([
            'method' => $method,
            'message' => "Hello {$user}.",
        ]);
    }

    #[PostMapping(path: "upload")]
    public function upload()
    {
        $file = $this->request->file('file');
        $merge = $this->request->post('merge', false);
        if ('pdf' != $file->getExtension() || 'application/pdf' != $file->getMimeType()) {
            return $this->fail(ErrorCode::PLEASE_UPDATE_PDF);
        }
        if ($file->getSize() > $this->maxSize) {
            return $this->fail(ErrorCode::OVER_MAX_SIZE);
        }

        try {
            $uploadRes = $this->officeService->uploadFile($file);
        } catch (FilesystemException|UnableToWriteFile $exception) {
            $this->logger->error($exception->getMessage());
            return $this->fail(ErrorCode::UPLOAD_FAIL);
        }
        //异步处理
        $data = array_merge($uploadRes, ['uid' => $this->request->header('auth'), 'merge' => (bool)$merge,]);
        $this->service->pdfToPngPush($data);
        return $this->success($uploadRes);
    }

}
