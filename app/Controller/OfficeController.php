<?php

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
use Mnvx\Lowrapper\Converter;
use Mnvx\Lowrapper\Format;
use Mnvx\Lowrapper\LowrapperParameters;

#[Controller(prefix: "api/office")]
class OfficeController extends AbstractController
{
    #[Inject]
    protected QueueService $service;

    #[GetMapping(path: "trans")]
    public function trans()
    {
        $converter = new Converter();
        $path = BASE_PATH . '/storage/pdf/2f477c579c248760b86fde7c11c73b9df00d046a/Excel.ppt';

        $parameters = (new LowrapperParameters())
            ->setInputFile($path)
            ->setOutputFormat(Format::GRAPHICS_PDF)
            ->setOutputFile('pdf.pdf');

        $converter->convert($parameters);
    }

    #[PostMapping(path: "upload")]
    public function upload(FilesystemFactory $factory)
    {
        $file = $this->request->file('file');
        if (!in_array($file->getExtension(), ['docx', 'doc']) || 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' != $file->getMimeType()) {
            return $this->fail(ErrorCode::PLEASE_UPDATE_WORD);
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
//        $this->service->officePush([
//            'format' => 'png',
//            'key' => $sha1,
//            'uid' => $this->request->header('auth'),
//        ]);
        return $this->success([
            'key' => $sha1,
        ]);
    }
}