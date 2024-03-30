<?php
declare(strict_types=1);

namespace App\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Redis\Redis;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\ApplicationContext;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Process\Process;
use function Hyperf\Support\env;

class OfficeJob extends Job
{
    public $params;

    protected int $maxAttempts = 2;

    /**
     * @var array|string[]
     */
    protected array $defaultOptions = [
        '--headless',
        '--invisible',
        '--nocrashreport',
        '--nodefault',
        '--nofirststartwizard',
        '--nologo',
        '--norestore',
    ];

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $server = (ApplicationContext::getContainer())->get(ServerFactory::class)->getServer()->getServer();
        $cache = make(Redis::class);
        $logger = make(StdoutLoggerInterface::class);
        $factory = make(FilesystemFactory::class);
        $storage = BASE_PATH . '/storage/';
        $relativePath = dirname($this->params['relativePath']);// "/office/{$this->params['key']}";
        $convertToType = $this->params['convertToType'] ?: 'pdf';

        $local = $factory->get('local');
        $fileList = $local->listContents($relativePath, true)
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())->toArray();

        try {
            $outputFile = '';
            foreach ($fileList as $item) {
                $file = $item->path();
                $ext = pathinfo($file, PATHINFO_EXTENSION);
//                if ('doc' != $ext && 'docx' != $ext) {
//                    continue;
//                }
                //上传的文件绝对路径
                $inputFile = $storage . $file;
                //filters https://wiki.openoffice.org/wiki/Framework/Article/Filter/FilterList_OOo_3_0
                //https://help.libreoffice.org/7.4/zh-CN/text/shared/guide/convertfilters.html?&DbPAR=SHARED&System=WIN
                $options = array_merge($this->defaultOptions, ["--convert-to {$convertToType}", $inputFile, "--outdir " . dirname($inputFile)]);
                if ($convertToType == 'docx') {
                    $options = array_merge($options, ['--infilter="writer_pdf_import"']);
                }
                //libreoffice --headless --invisible --nocrashreport --nodefault --nofirststartwizard --nologo --norestore --convert-to pdf path-to-file --infilter="writer_pdf_import"
                $command = 'libreoffice ' . implode(' ', $options);

                $process = $this->createProcess($command);

                $logger->info(sprintf('Start: %s', $command));

                $resultCode = $process->run(function ($type, $buffer) use ($logger) {
                    if (Process::ERR === $type) {
                        $logger->warning($buffer);
                    } else {
                        $logger->info($buffer);
                    }
                });

                if ($resultCode != 0) {
                    $logger->error(sprintf('Failed with result code %d: %s', $resultCode, $command));
                } else {
                    $outputFileName = basename($inputFile, $ext);
                    $outputFile = dirname($inputFile) . DIRECTORY_SEPARATOR . "{$outputFileName}{$convertToType}";
                    $logger->info(sprintf('Finished: %s', $command));
                }
            }
            $fd = $cache->get($this->params['uid']);
            $result = [
                'result' => 1,
                'category' => 'pdf',
                'download' => env('APP_HOST') . $relativePath . DIRECTORY_SEPARATOR . basename($outputFile),
                'filesize' => filesize($outputFile),
                'filename' => basename($outputFile),
            ];
            $json = json_encode($result, JSON_UNESCAPED_UNICODE);
            $server->push(intval($fd), $json);
            return $result;
        } catch (\Exception $e) {
            $logger->error("Exception message:" . $e->getMessage());
        }
    }


    /**
     * @param string $command
     * @return Process
     */
    protected function createProcess(string $command): Process
    {
        return Process::fromShellCommandline($command, sys_get_temp_dir());
    }


}