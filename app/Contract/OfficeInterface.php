<?php

declare(strict_types=1);

namespace App\Contract;

interface OfficeInterface
{

    public function uploadFile($file):array;

}