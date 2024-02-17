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
        return $this->model->getPageList($where, $columns, $options);
    }

    public function getById($fdcId){
        return $this->model::find($fdcId);
    }

}