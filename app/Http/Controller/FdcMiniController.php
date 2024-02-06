<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Service\FdcService;
use App\Http\Service\HouseDealService;
use App\Http\Service\ProjectDetailService;
use App\Http\Service\RoomService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller(prefix: "api/mini/fdc")]
class FdcMiniController extends BaseController
{

    #[Inject]
    protected FdcService $fdcService;

    #[Inject]
    protected RoomService $roomService;

    #[Inject]
    protected ProjectDetailService $projectDetailService;

    #[Inject]
    protected HouseDealService $houseDealService;

    #[RequestMapping(path: "list")]
    public function list()
    {
        $keyword = $this->request->post("keyword", "");
        $list = $this->fdcService->getList([['project_name', "like", "{$keyword}%"]], ['*'], ['orderByRaw' => 'id desc']);
        return $this->success($list);
    }

    /**
     * 房价列表
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[RequestMapping(path: "detail")]
    public function detail()
    {
        $fdcId = (int)$this->request->input('fdcId');// 19768;
        $roomList = $this->roomService->getByFdcId($fdcId);
        $newList = [];

        foreach ($roomList as $building) {
            //楼栋
            $newList[$building['project_id']][] = $building;
        }
        $newRoomList = [];
        foreach ($newList as $projectList) {
            $unitsList = [];
            //单元
            foreach ($projectList as $room) {
                $room['label'] = $room['room_num'];
                if (isset($unitsList[$room['units']])) {
                    $unitsList[$room['units']]['items'][] = $room;
                } else {
                    $unitsList[$room['units']] = [
                        "label" => $room['units'] ?: '未命名',
                        "title" => $room['units'] ?: '未命名',
                        "badgeProps" => [],
                        "items" => [$room],
                    ];
                }
            }
            $unitsList = array_values($unitsList);
            $newRoomList[] = $unitsList;
        }
        $projectDetailList = $this->projectDetailService->getByFdcId($fdcId);
        $buildingList = array_column($projectDetailList, 'building');
        $return = [
            "roomList" => $newRoomList,
            "building" => $buildingList,
        ];
        return $this->success($return);
    }

    #[RequestMapping(path: "getHouseDeal")]
    public function getHouseDeal()
    {
        $now = strtotime(date("Y-m-d", time() - 86400));
        $res = $this->houseDealService->getDealDetail($now);
        return json_encode($res);
    }
}