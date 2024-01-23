<?php

namespace App\Http\Service;

use App\Model\Fdc;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class FdcService
{

    #[Inject]
    protected Fdc $model;

    public function getList(array $where, array $columns = ['*'], array $options = []): array
    {
        return $this->model->getPageList($where, $columns, $options);
    }

}