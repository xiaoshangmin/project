<?php

namespace App\Controller;

use App\Constants\ErrorCode;
use App\Service\OfficeService;
use App\Service\QueueService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

#[Controller(prefix: "api/office")]
class OfficeController extends AbstractController
{
    private int $maxSize = 5243000;

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
        if (!in_array($file->getExtension(), ['docx', 'doc']) || 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' != $file->getMimeType()) {
            return $this->fail(ErrorCode::PLEASE_UPDATE_WORD);
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
        $this->queueService->turnToPdfPush($data);
        return $this->success($uploadRes);
    }
}