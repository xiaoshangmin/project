<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Service\FdcTaskService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller(prefix: "api/fdc")]
class FdcController extends BaseController
{

    #[Inject]
    private FdcTaskService $fdcTaskService;

    #[GetMapping(path: "tt")]
    public function tt()
    {
        return __METHOD__;
    }

    /**
     * 抓取项目列表
     * @return string
     */
    #[GetMapping(path: "syncList")]
    public function syncList()
    {
        return $this->fdcTaskService->syncList();
    }

    /**
     * 抓取项目详情和楼栋列表
     * @return string
     */
    #[GetMapping(path: "getProject")]
    public function getProject()
    {
       return $this->fdcTaskService->getProject();
    }


    /**
     * 抓取楼栋单元列表
     * @return string
     */
    #[GetMapping(path: "getUnits")]
    public function getUnits()
    {
        return $this->fdcTaskService->getUnits();
    }

    /**
     * 抓取房间列表
     * @return string
     */
    #[GetMapping(path: "getRoom")]
    public function getRoom()
    {
        return $this->fdcTaskService->getRoom();
    }


    /**
     * 抓取房间详情列表
     * @return string
     */
    #[GetMapping(path: "getRoomDetail")]
    public function getRoomDetail()
    {
        return $this->fdcTaskService->getRoomDetail();
    }

    /**
     * 抓取指导价
     * @return string
     */
    #[GetMapping(path: "getGuidePrice")]
    public function getGuidePrice()
    {
       return $this->fdcTaskService->getGuidePrice();
    }

    /**
     * 抓取成交量统计
     * @return string
     */
    #[GetMapping(path: "getHouseDeal")]
    public function getHouseDeal()
    {
        return $this->fdcTaskService->getHouseDeal();
    }

    /**
     * 抓取成交量详细
     * @return string
     */
    #[GetMapping(path: "getHouseDealDetail")]
    public function getHouseDealDetail()
    {
        return $this->fdcTaskService->getHouseDealDetail();
    }
}