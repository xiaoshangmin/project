<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Service\FdcService;
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

    #[RequestMapping(path: "list")]
    public function list()
    {
        $list = $this->fdcService->getList([], ['*'], ['orderByRaw' => 'id desc']);
        return $this->success($list);
    }

    #[RequestMapping(path: "detail")]
    public function detail()
    {
        $fdcId = (int)$this->request->input('fdcId');// 19768;
        $fdcList = $this->roomService->getByFdcId($fdcId);
        $newList = [];

        foreach ($fdcList as $building) {
            //楼栋
            $newList[$building['project_id']][] = $building;
        }
        $newRoomList = [];
        foreach ($newList as $roomList) {
            $unitsList = [];
            //单元
            foreach ($roomList as $room) {
                $room['label'] = $room['room_num'];
                $room['image'] = "https://img.wowyou.cc/file/0bef92b75e849ce81fb7e.jpg";
                if (isset($unitsList[$room['units']])){
                    $unitsList[$room['units']]['items'][] = $room;
                }else {
                    $unitsList[$room['units']] = [
                        "label" => $room['units'],
                        "title" => $room['units'],
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
}