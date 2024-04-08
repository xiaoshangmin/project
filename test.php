<?php
$cookie = ["did=web_8f2bd121e4744bad86ba21ef4381a75d; path=\/; expires=Wed, 24 Mar 2027 10:21:21 GMT; domain=chenzhongtech.com","didv=1712571681000; path=\/; expires=Wed, 24 Mar 2027 10:21:21 GMT; domain=chenzhongtech.com"];
$c = "";
foreach ($cookie as $item) {
    $i = explode(";",$item)[0];
    $c.=";".$i;
}
echo $c;