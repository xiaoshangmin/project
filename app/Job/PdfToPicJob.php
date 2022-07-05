<?php
declare(strict_types=1);

namespace App\Job;

use App\Util\Common;
use Hyperf\AsyncQueue\Job;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Redis\Redis;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\ApplicationContext;
use League\Flysystem\StorageAttributes;
use ZipArchive;

class PdfToPicJob extends Job
{
    public $params;

    protected $maxAttempts = 2;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
//        ini_set("memory_limit", "1024M");

        $server = (ApplicationContext::getContainer())->get(ServerFactory::class)->getServer()->getServer();
        $cache = (ApplicationContext::getContainer())->get(Redis::class);
        $logger = make(StdoutLoggerInterface::class);
        $logger->info("start pdfTopic job" . json_encode($this->params, JSON_UNESCAPED_UNICODE));
        //true合并图片  false不合并
        $merge = $this->params['merge'] || false;
        $outFormat = $this->params['format'] ?: 'png';
        $factory = make(FilesystemFactory::class);
        $storage = BASE_PATH . '/storage/';
        $local = $factory->get('local');
        $fileList = $local->listContents('/pdf', true)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())->toArray();
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
                    $filename = basename($rs[0]);
                    $local->move("{$subDirectory}/{$filename}", "{$subDirectory}/{$filename}");
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
                $logger->info("turn finish {$filename}.zip");
            } else {
                array_map('unlink', glob("{$directory}/merge*.png"));
                $filename = basename($savePath);
                $logger->info("turn finish {$filename}");
            }
            $fd = $cache->get('websocket_1');
            $json = json_encode(['file' => $filename], JSON_UNESCAPED_UNICODE);
            $server->push(intval($fd), $json);
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        $logger->info("pdfTopic end");
    }

}