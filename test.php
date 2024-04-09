<?php
$retailPrice = 70;
$order_item['dcount'] = 1;
$item_price = 0.5;
echo bcsub(bcmul($retailPrice,$order_item['dcount']),$item_price,2);