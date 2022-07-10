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

namespace App\Controller;

use App\Constants\ErrorCode;
use App\Service\QueueService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

#[Controller(prefix: "api/pdfToPic")]
class PdfToPicController extends AbstractController
{

    #[Inject]
    protected QueueService $service;

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
    public function upload(FilesystemFactory $factory)
    {
        $file = $this->request->file('file');
        $merge = $this->request->post('merge', false);
        if ('pdf' != $file->getExtension() || 'application/pdf' != $file->getMimeType()) {
            return $this->fail(ErrorCode::PLEASE_UPDATE_PDF);
        }
        if ($file->getSize() > 2097152) {
            return $this->fail(ErrorCode::OVER_MAX_SIZE);
        }
        $tmpFile = $file->getRealPath();
        $sha1 = sha1_file($tmpFile);
        $resource = fopen($tmpFile, 'r+');
        $local = $factory->get('local');
        $path = "pdf/{$sha1}/" . $file->getClientFilename();
        try {
            $local->writeStream($path, $resource);
            fclose($resource);
        } catch (FilesystemException|UnableToWriteFile $exception) {
            $this->logger->error($exception->getMessage());
            return $this->fail(ErrorCode::UPLOAD_PDF_FAIL);
        }
        //异步处理
        $this->service->push([
            'merge' => (bool)$merge,
            'format' => 'png',
            'key' => $sha1,
            'uid' => $this->request->header('auth'),
        ]);
        return $this->success([
            'key' => $sha1,
        ]);
    }

}
