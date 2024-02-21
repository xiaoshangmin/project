<?php

namespace App\Http\Service;

use App\Model\Fdc;
use Hyperf\Di\Annotation\Inject;

class FdcService
{

    #[Inject]
    protected Fdc $model;

    public function getList(array $where, array $columns = ['*'], array $options = []): array
    {
        $list = $this->model->getPageList($where, $columns, $options);
        foreach ($list['data'] as &$item) {
            $item['room_type'] = explode('、', $item['room_type']);
        }
        return $list;
    }

    public function getById(int $fdcId): array
    {
        $info = $this->model::findOrFail($fdcId)->toArray();
        if (!empty($info['coordinatex']) && !empty($info['coordinatey'])) {
            $location = projTransform($info['coordinatex'], $info['coordinatey']);
            $info['coordinatex'] = $location[0];
            $info['coordinatey'] = $location[1];
        }
        return $info;
    }

}