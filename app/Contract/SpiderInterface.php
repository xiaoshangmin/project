<?php

namespace App\Contract;

interface SpiderInterface
{

    const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36';

    public function analysis(string $url);

}