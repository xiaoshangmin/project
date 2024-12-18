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
use function Hyperf\Support\make;
use ZipArchive;

class PdfToPicJob extends Job
{
    public $params;

    protected int $maxAttempts = 2;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
//        ini_set("memory_limit", "1024M");
        $server = (ApplicationContext::getContainer())->get(ServerFactory::class)->getServer()->getServer();
        $cache = make(Redis::class);
        $logger = make(StdoutLoggerInterface::class);
        $factory = make(FilesystemFactory::class);
        $local = $factory->get('local');
        $logger->info("start pdfTopic job" . json_encode($this->params, JSON_UNESCAPED_UNICODE));
        //true合并图片  false不合并
        $merge = $this->params['merge'] || false;
        $outFormat = $this->params['format'] ?: 'png';
        $storage = BASE_PATH . '/storage/';
        $relativePath = dirname($this->params['relativePath']);
        $fileList = $local->listContents($relativePath, true)
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
                    $local->move("{$relativePath}/{$filename}", "{$relativePath}/{$filename}");
                }
            }
            if (!empty($compressList)) {
                $zip = new ZipArchive();
                $savePath = $directory . "/{$filename}.zip";
                $zip->open($savePath, ZipArchive::CREATE);
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
            $fd = $cache->get($this->params['uid']);
            $result = [
                'result' => 1,
                'category' => 'zip',
                'filesize' => filesize($savePath),
                'download' => env('APP_HOST') . $relativePath . DIRECTORY_SEPARATOR . basename($savePath),
                'filename' => basename($savePath),
            ];
            $json = json_encode($result, JSON_UNESCAPED_UNICODE);
            $server->push(intval($fd), $json);
            $logger->info("pdfTopic finish:" . $json);
        } catch (\Exception $e) {
            $logger->error("Exception message:" . $e->getMessage());
        }
        $logger->info("pdfTopic end");
    }

}