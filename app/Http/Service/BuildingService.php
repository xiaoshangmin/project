<?php

namespace App\Http\Service;

use App\Model\Building;
use Hyperf\Di\Annotation\Inject;

class BuildingService
{

    #[Inject]
    protected Building $model;

    public function getByFdcId(int $fdcId): array
    {
        return $this->model::where('fdc_id', $fdcId)->get()->toArray();
    }

}