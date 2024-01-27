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
        return $this->model->where('fdc_id', $fdcId)->get()->toArray();
    }

}