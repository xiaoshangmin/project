<?php
$room['selling_price'] = "--元/平方米(按建筑面积计)";
preg_match('/(\d.)+/',$room['selling_price'],$match);
print_r($match);