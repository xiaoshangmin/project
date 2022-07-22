<?php

namespace App\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::TARGET_METHOD)]
class Test extends AbstractAnnotation
{

}