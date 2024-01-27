<?php

namespace App\Http\Service;

use App\Model\ProjectDetail;
use Hyperf\Di\Annotation\Inject;

class ProjectDetailService
{

    #[Inject]
    protected ProjectDetail $model;

    public function getByFdcId(int $fdcId): array
    {
        return $this->model::where('fdc_id', $fdcId)->get()->toArray();
    }

}