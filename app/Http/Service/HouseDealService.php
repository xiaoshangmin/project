<?php

namespace App\Http\Service;

use App\Model\HouseDeal;
use App\Model\HouseDealDetail;
use Hyperf\Di\Annotation\Inject;

class HouseDealService
{

    #[Inject]
    private HouseDeal $houseDealModel;

    #[Inject]
    private HouseDealDetail $houseDealDetailModel;

    public function getDealDetail(int $date): array
    {
        $data = $this->houseDealModel::where('xml_date_day', '=', $date)
            ->select(['data', 'type', 'xml_date_day'])->get()->toArray();
        $detailList = $this->houseDealDetailModel::where('xml_date_day', '=', $date)
            ->select(['area', 'type', 'use', 'deal_num', 'deal_area', 'sellable', 'sellable_area'])->get()->toArray();
        if (empty($data)) {
            return [];
        }
        $newData = [];
        foreach ($data as $item) {
            $key = $item['type'] == 1 ? 'old' : 'new';
            $newData[$key] = [
                'data' => json_decode($item['data'], true),
                'date' => date('Y-m-d', $item['xml_date_day'])
            ];
        }
        $newDetailList = [];
        foreach ($detailList as $item) {
            $key = $item['type'] == 1 ? 'old' : 'new';
            if (isset($newDetailList[$key][$item['area']])) {
                $newDetailList[$key][$item['area']]['item'][] = $item;
            } else {
                $newDetailList[$key][$item['area']] = ['area' => $item['area'], 'item' => [$item]];
            }
        }
        $tjList = [];
        foreach ($newDetailList as $key => $value) {
            $temp = [];
            foreach ($value as $item) {
                if ('全市' == $item['area']) {
                    $temp = $item;
                } else {
                    $tjList[$key][] = $item;
                }
            }
            array_unshift($tjList[$key], $temp);
        }
        return ['pie' => $newData, 'tj' => $tjList];

    }

}