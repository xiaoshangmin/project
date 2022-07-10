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

class OfficeJob extends Job
{
    public $params;

    protected $maxAttempts = 2;

    /**
     * Defailt options for libreoffice
     * @var array
     */
    protected $defaultOptions = [
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
        $cache = (ApplicationContext::getContainer())->get(Redis::class);
        $logger = make(StdoutLoggerInterface::class);
        $inputFile = $this->params['file'];
        $documentType = 'pdf';


        $options = array_merge($this->defaultOptions, [
            $documentType ? '--' . $documentType : '',
            '--convert-to pdf',
            '"' . $inputFile . '"',
        ]);
        $command = 'libreoffice ' . implode(' ', $options);

        $process = $this->createProcess($command);

        if ($this->timeout) {
            $process->setTimeout($this->timeout);
        }

        $this->logger->info(sprintf('Start: %s', $command));

        $self = $this;
        $resultCode = $process->run(function ($type, $buffer) use ($self) {
            if (Process::ERR === $type) {
                $self->logger->warning($buffer);
            } else {
                $self->logger->info($buffer);
            }
        });

        $result = $this->createOutput($inputFile . '.' . $parameters->getOutputFormat(), $parameters->getOutputFile());
        $this->deleteInput($parameters, $inputFile);

        if ($resultCode != 0) {
            $this->logger->error(sprintf('Failed with result code %d: %s', $resultCode, $command));
            throw new LowrapperException('Error on converting data with LibreOffice: ' . $resultCode, $resultCode);
        } else {
            $this->logger->info(sprintf('Finished: %s', $command));
        }

        return $result;

    }

}