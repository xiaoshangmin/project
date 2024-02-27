<?php

namespace App\Task;


use App\Http\Service\FdcTaskService;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

#[Crontab(name: "Foo", rule: "0 */2 * * *", callback: "execute", memo: "深圳房地产定时任务")]
class FdcTask
{
    #[Inject]
    private StdoutLoggerInterface $logger;

    #[Inject]
    private FdcTaskService $fdcTaskService;

    public function execute()
    {
        $appEnv = env('APP_ENV', 'dev');
        if ($appEnv != 'production'){
            return;
        }
        $this->fdcTaskService->getHouseDeal();
        $this->logger->info("getHouseDeal done");
        $this->fdcTaskService->getHouseDealDetail();
        $this->logger->info("getHouseDealDetail done");
    }
}