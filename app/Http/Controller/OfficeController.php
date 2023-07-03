<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Constants\ErrorCode;
use App\Http\Service\OfficeService;
use App\Http\Service\QueueService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

#[Controller(prefix: "api/office")]
class OfficeController extends BaseController
{
    private int $maxSize = 10486000;

    #[Inject]
    protected QueueService $queueService;

    #[Inject]
    protected OfficeService $officeService;

    /**
     * word转pdf
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[PostMapping(path: "upload")]
    public function upload()
    {
        $file = $this->request->file('file');
        $type = $this->request->post('type');
        //wordToPdf
        if ($type == 'pdf' && (!in_array($file->getExtension(), ['docx', 'doc']) || 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' != $file->getMimeType())) {
            return $this->fail(ErrorCode::PLEASE_UPDATE_WORD);
        }
        //pdfToWord
        if ($type == 'word' && (!in_array($file->getExtension(), ['pdf']) || 'application/pdf' != $file->getMimeType())) {
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
        $data = array_merge($uploadRes, ['uid' => $this->request->header('auth')]);
        if ($type == 'pdf') {
            $this->queueService->wordToPdfPush($data);
        } else {
            $this->queueService->pdfToWordPush($data);
        }

        return $this->success($uploadRes);
    }
}