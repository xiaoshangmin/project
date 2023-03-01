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
        $cmd = sprintf("you-get --json '%s'", $this->params['url']);
        $output = trim(shell_exec($cmd));
        $logger->info("start you-get job " . $output);
        $fd = $cache->get($this->params['uid']);
        $server->push(intval($fd), $output);
    }
}