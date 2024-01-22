<?php

namespace App\Http\Service;

use Hyperf\DbConnection\Db;

class FdcService
{

    public function getList(){
        return Db::table('fdc')->paginate(10);
    }

}