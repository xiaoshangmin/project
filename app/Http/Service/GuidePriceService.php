<?php

namespace App\Http\Service;

use App\Model\GuidePrice;
use Hyperf\Di\Annotation\Inject;

class GuidePriceService
{
    #[Inject]
    private GuidePrice $guidePriceModel;

    public function getList(array $where, array $columns = ['*'], array $options = []): array
    {
        return $this->guidePriceModel->getPageList($where, $columns, $options);
    }

}