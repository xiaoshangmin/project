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
use App\Util\Common;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToWriteFile;
use ZipArchive;

#[Controller(prefix: "api")]
class IndexController extends AbstractController
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
            'merge' => false,
            'format' => 'png',
        ]);
        return $this->success([
            'key' => $sha1
        ]);
    }

    /**
     * pdf转图片
     * @param FilesystemFactory $factory
     * @return \Psr\Http\Message\ResponseInterface
     * @throws FilesystemException
     */
    #[GetMapping(path: "pdf2pic")]
    public function pdf2pic(FilesystemFactory $factory)
    {
        ini_set("memory_limit", "1024M");
        //true合并图片  false不合并
        $merge = $this->request->query('merge', false);
        $storage = BASE_PATH . '/storage/';
        $local = $factory->get('local');
        $fileList = $local->listContents('/pdf', true)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())->toArray();
        $outFormat = 'png';
        try {
            $directory = $storage;
            $filename = $savePath = '';
            $compressList = [];
            foreach ($fileList as $item) {
                $file = $item->path();
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ('pdf' != $ext) {
                    continue;
                }
                $filename = basename($file, '.pdf') ?: date('YmdHis');
                $absolutePath = $storage . $file;
                $directory = dirname($absolutePath);
                $subDirectory = dirname($file);
                $pdf2img = new \Spatie\PdfToImage\Pdf($absolutePath);
                $pdf2img->setOutputFormat($outFormat);
                $preName = "merge-";
                $rs = $pdf2img->saveAllPagesAsImages($directory, $preName);
                $savePath = "{$directory}/{$filename}.{$outFormat}";
                if (count($rs) > 1) {
                    if ($merge) {
                        Common::CompositeImage($rs, $savePath);
                    } else {
                        $compressList = $rs;
                    }
                } else {
                    $source = basename($rs[0]);
                    $dest = basename($savePath);
                    $local->move("{$subDirectory}/{$source}", "{$subDirectory}/{$dest}");
                }
            }
            if (!empty($compressList)) {
                $zip = new ZipArchive();
                $file = $directory . "/{$filename}.zip";
                $zip->open($file, ZipArchive::CREATE);
                foreach ($compressList as $img) {
                    $zip->addFile($img, basename($img));
                }
                $zip->close();
                array_map('unlink', glob("{$directory}/merge*.png"));
                return $this->response->download($file, "{$filename}.zip");
            }
            array_map('unlink', glob("{$directory}/merge*.png"));
            $dest = basename($savePath);
            return $this->response->download($savePath, $dest);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->fail();
        }

    }

    #[GetMapping(path: "dl")]
    public function dl()
    {
        $path = BASE_PATH . '/storage/pdf/041baf26182bd5079200c3c3afa4145e34eaf624/百果园新零售全渠道运营解决方案-2021041402.zip';
        return $this->response->download($path, '百果园新零售全渠道运营解决方案-2021041402.zip');
    }
}
