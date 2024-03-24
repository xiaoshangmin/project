<?php
$stmt = "select * from emails";
$requestData['requests'][] = ['type' => 'execute', 'stmt' => ['sql' => $stmt]];
$requestData['requests'][] = ['type' => "close"];
echo json_encode($requestData);