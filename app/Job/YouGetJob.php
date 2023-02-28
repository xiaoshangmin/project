<?php
declare(strict_types=1);

namespace App\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Redis\Redis;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\ApplicationContext;

class YouGetJob extends Job
{

    public $params;

    protected int $maxAttempts = 2;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $server = (ApplicationContext::getContainer())->get(ServerFactory::class)->getServer()->getServer();
        $cache = make(Redis::class);
        $logger = make(StdoutLoggerInterface::class);
        $output = trim(shell_exec("you-get --json 'https://www.bilibili.com/video/BV1YA411k7BQ/?spm_id_from=333.1007.tianma.1-1-1.click'"));
        $logger->info("start you-get job " . $output);
        $fd = $cache->get($this->params['uid']);
        $server->push(intval($fd), $output);
    }
}