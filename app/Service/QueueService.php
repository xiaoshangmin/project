<?php
declare(strict_types=1);

namespace App\Service;

use App\Job\PdfToPicJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;

class QueueService
{

    protected DriverInterface $driver;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->driver = $driverFactory->get('default');
    }

    /**
     * 生产消息
     * @param $params
     * @param int $delay
     * @return bool
     */
    public function push($params, int $delay = 0): bool
    {
        return $this->driver->push(new PdfToPicJob($params), $delay);
    }

}