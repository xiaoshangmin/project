<?php

namespace App\Aspect;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect()]
class TestAspect extends AbstractAspect
{
    #[Inject()]
    private StdoutLoggerInterface $logger;

    //要切入的类或者trait,可多个  也可具体到类方法
    public array $classes = [
        "App\Controller\TestController::tt"
    ];

    //要切入的注解
    public array $annotations = [

    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $this->logger->info(__METHOD__ . __FUNCTION__ . "-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa");
        $result = $proceedingJoinPoint->process();
        return $result;
    }
}