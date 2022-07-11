<?php

namespace App\Controller;

use App\Constants\ErrorCode;
use App\Service\QueueService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

#[Controller(prefix: "api/office")]
class OfficeController extends AbstractController
{
    private string $subDir = 'office';

    private int $maxSize = 5243000;

    #[Inject]
    protected QueueService $service;

    /**
     * word转pdf
     * @param FilesystemFactory $factory
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[PostMapping(path: "upload")]
    public function upload(FilesystemFactory $factory)
    {
        $file = $this->request->file('file');
        if (!in_array($file->getExtension(), ['docx', 'doc']) || 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' != $file->getMimeType()) {
            return $this->fail(ErrorCode::PLEASE_UPDATE_WORD);
        }
        if ($file->getSize() > $this->maxSize) {
            return $this->fail(ErrorCode::OVER_MAX_SIZE);
        }
        $tmpFile = $file->getRealPath();
        $md5 = md5_file($tmpFile);
        $resource = fopen($tmpFile, 'r+');
        $local = $factory->get('local');
        $relativePath = $this->subDir . DIRECTORY_SEPARATOR . $md5 . DIRECTORY_SEPARATOR . $file->getClientFilename();
        try {
            $local->writeStream($relativePath, $resource);
            fclose($resource);
        } catch (FilesystemException|UnableToWriteFile $exception) {
            $this->logger->error($exception->getMessage());
            return $this->fail(ErrorCode::UPLOAD_FAIL);
        }
        //异步处理
        $this->service->turnToPdfPush([
            'key' => $md5,
            'relativePath' => $relativePath,
            'uid' => $this->request->header('auth'),
        ]);
        return $this->success([
            'key' => $md5,
        ]);
    }
}