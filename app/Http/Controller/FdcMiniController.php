<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Service\BuildingService;
use App\Http\Service\FdcService;
use App\Http\Service\GuidePriceService;
use App\Http\Service\HouseDealService;
use App\Http\Service\RoomService;
use App\Middleware\Auth\MiniAuthMiddleware;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;

#[Controller(prefix: "api/mini/fdc")]
#[Middleware(MiniAuthMiddleware::class)]
class FdcMiniController extends BaseController
{

    #[Inject]
    protected FdcService $fdcService;

    #[Inject]
    protected GuidePriceService $guidePriceService;

    #[Inject]
    protected RoomService $roomService;

    #[Inject]
    protected BuildingService $buildingService;

    #[Inject]
    protected HouseDealService $houseDealService;

    #[PostMapping(path: "list")]
    public function list()
    {
        $keyword = $this->request->post("keyword", "");
        $area = $this->request->post("area", "");
        $priceOrder = $this->request->post("priceOrder", "");
        $where = [];
        if ($area != "" && $area != "all") {
            $where[] = ['area', '=', trim($area)];
        }
        if (!empty($keyword)) {
            $where[] = ['project_name', "like", "{$keyword}%"];
        }
        $order = 'id desc';
        if (!empty($priceOrder) && $priceOrder!='default'){
            $order = "average_price {$priceOrder}";
        }
        $list = $this->fdcService->getList($where,
            ['id', 'ent', 'room_type', 'average_price', 'ys_total_room', 'approve_time', 'project_name'],
            ['orderByRaw' => $order]
        );
        return $this->success(['data' => $list['data'], 'lastPage' => $list['last_page']]);
    }

    /**
     * 房间列表
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[PostMapping(path: "detail")]
    public function detail()
    {
        $fdcId = (int)$this->request->input('fdcId');
        $roomList = $this->roomService->getByFdcId($fdcId);
        $newList = [];

        foreach ($roomList as $building) {
            //楼栋
            $newList[$building['building_id']][] = $building;
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
        $projectDetailList = $this->buildingService->getByFdcId($fdcId);
        $buildingList = array_column($projectDetailList, 'building');

        $fdcInfo = $this->fdcService->getById($fdcId);

        $return = [
            "roomList" => $newRoomList,
            "building" => $buildingList,
            'fdcInfo' => $fdcInfo,
        ];
        return $this->success($return);
    }

    #[GetMapping(path: "getHouseDeal")]
    public function getHouseDeal()
    {
        $now = strtotime(date("Y-m-d", time() - 86400));
        $res = $this->houseDealService->getDealDetail($now);
//        return json_encode($res);
        return $this->success($res);
    }

    #[PostMapping(path: "guideList")]
    public function getGuideList()
    {
        $keyword = $this->request->post("keyword", "");
        $area = $this->request->post("area", "");
        $priceOrder = $this->request->post("priceOrder", "");
        $where = [];
        if ($area != "" && $area != "all") {
            $where[] = ['area', '=', trim($area)];
        }
        if (!empty($keyword)) {
            $where[] = ['name', "like", "{$keyword}%"];
        }
        $order = 'id desc';
        if (!empty($priceOrder) && $priceOrder!='default'){
            $order = "price {$priceOrder}";
        }
        $list = $this->guidePriceService->getList($where,
            ['id', 'area', 'street', 'name', 'price'],
            ['orderByRaw' => $order]
        );
        return $this->success(['data' => $list['data'], 'lastPage' => $list['last_page']]);
    }


}