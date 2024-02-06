<?php

namespace App\Http\Service;

use App\Model\Room;
use Hyperf\Di\Annotation\Inject;

class RoomService
{

    #[Inject]
    protected Room $model;

    public function getByFdcId(int $fdcId): array
    {
        $list = $this->model->where('fdc_id', $fdcId)
            ->select(['room_num', 'room_type', 'units', 'selling_price', 'floor_space', 'room_space', 'final_floor_space', 'final_room_space', 'status'])->get()->toArray();
        return $this->buildRoomData($list);
    }

    private function buildRoomData(array $roomList)
    {
        $newList = [];
        foreach ($roomList as $room) {
            if (!empty($room['selling_price'])) {
                preg_match('/([\d.])+/', $room['selling_price'], $match);
                $room['selling_price'] = $match[0] ? mb_convert_encoding($match[0], 'UTF-8', 'GBK') : '--';
            }
            if (!empty($room['floor_space'])) {
                preg_match('/([\d.])+/', $room['floor_space'], $match);
                $room['floor_space'] = $match[0] ? mb_convert_encoding($match[0], 'UTF-8', 'GBK') : '--';
            }
            if (!empty($room['room_space'])) {
                preg_match('/([\d.])+/', $room['room_space'], $match);
                $room['room_space'] = $match[0] ? mb_convert_encoding($match[0], 'UTF-8', 'GBK') : '--';
            }
            if (!empty($room['final_floor_space'])) {
                preg_match('/([\d.])+/', $room['final_floor_space'], $match);
                $room['final_floor_space'] = $match[0] ? mb_convert_encoding($match[0], 'UTF-8', 'GBK') : '--';
            }
            if (!empty($room['final_room_space'])) {
                preg_match('/([\d.])+/', $room['final_room_space'], $match);
                $room['final_room_space'] = $match[0] ? mb_convert_encoding($match[0], 'UTF-8', 'GBK') : '--';
            }
            $room['rate'] = '--';
            $room['total_price'] = '--';
            if ($room['floor_space'] != '--' && $room['room_space'] != '--') {
                $room['rate'] = (bcdiv($room['room_space'], $room['floor_space'], 4) * 100) . "%";
            }
            //房屋总价
            if ($room['floor_space'] != '--' && $room['selling_price'] != '--') {
                $totalPrice = bcmul($room['selling_price'], $room['floor_space']);
                $room['total_price'] = bcdiv($totalPrice, 10000, 2);
                //套内单价
                if ($room['room_space'] != '--'){
                    $room['room_price'] = bcdiv($totalPrice, $room['room_space'], 2);
                }
            }

            $newList[] = $room;
        }
        return $newList;
    }

}