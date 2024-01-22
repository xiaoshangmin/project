<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Service\FdcService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: "api/mini/fdc")]
class FdcMiniController extends BaseController
{

    #[Inject]
    protected FdcService $fdcService;

    #[GetMapping(path: "list")]
    public function list()
    {
        $list = $this->fdcService->getList();
        return $this->success($list->items());
    }
}