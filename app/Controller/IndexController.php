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

use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use App\Util\Common;

#[Controller()]
class IndexController extends AbstractController
{
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    #[GetMapping(path: "analysis")]
    public function analysis(FilesystemFactory $factory)
    {
        $local = $factory->get('local');
        $local->read();

    }

    #[PostMapping(path: "upload")]
    public function upload(FilesystemFactory $factory)
    {
        $file = $this->request->file('file');
        $resource = fopen($file->getRealPath(), 'r+');
        $local = $factory->get('local');
        try {
            $local->writeStream($file->getClientFilename(), $resource);
            fclose($resource);
        } catch (FilesystemException|UnableToWriteFile $exception) {
            echo $exception;
        }
    }

    #[GetMapping(path: "pdf2pic")]
    public function pdf2pic(FilesystemFactory $factory)
    {
        $prefix = BASE_PATH . '/storage/';
        $pdfPath = $prefix . "pdf";
        $local = $factory->get('local');
        $fileList = $local->listContents('/pdf', true)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())->toArray();
        $outFormat = 'png';
        try {
            foreach ($fileList as $checkNo => $item) {
                $filename = basename($item->path(), '.pdf') ?: date('YmdHis');
                $file = $prefix . $item->path();
                $pdf2img = new \Spatie\PdfToImage\Pdf($file);
                $pdf2img->setOutputFormat($outFormat);
                $preName = "merge-";
                $rs = $pdf2img->saveAllPagesAsImages($pdfPath, $preName);
                $savePath = "{$pdfPath}/{$filename}.{$outFormat}";
                if (count($rs) > 1) {
                    Common::CompositeImage($rs, $savePath);
                } else {
                    $source = basename($rs[0]);
                    $dest = basename($savePath);
                    $local->move("pdf/{$source}", "pdf/{$dest}");
                }
            }

//            array_map('unlink', glob("{$prefix}*.pdf"));
            array_map('unlink', glob("{$pdfPath}/merge*.png"));
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

    }
}
