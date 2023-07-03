<?php
declare(strict_types=1);

namespace App\Http\Service;

use App\Job\OfficeJob;
use App\Job\PdfToPicJob;
use App\Job\YouGetJob;
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
    public function pdfToPngPush($params, int $delay = 0): bool
    {
        $params['format'] = 'png';
        return $this->driver->push(new PdfToPicJob($params), $delay);
    }

    /**
     * 生产消息
     * @param $params
     * @param int $delay
     * @return bool
     */
    public function pdfToWordPush($params, int $delay = 0): bool
    {
        $params['convertToType'] = 'docx';
        return $this->driver->push(new OfficeJob($params), $delay);
    }

    /**
     * @param $params
     * @param int $delay
     * @return bool
     */
    public function wordToPdfPush($params, int $delay = 0): bool
    {
        $params['convertToType'] = 'pdf';
        return $this->driver->push(new OfficeJob($params), $delay);
    }

    public function youGetPush($params, int $delay = 0): bool
    {
        return $this->driver->push(new YouGetJob($params), $delay);
    }


    public function gotenbergUrlToPdf(array $params, int $delay = 0): bool
    {
        return $this->driver->push($params, $delay);
    }

}