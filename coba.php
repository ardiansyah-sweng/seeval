<?php

/*** Results on my machine ***
php 7.2
array_filter: 2.5147440433502
foreach: 0.13733291625977
for i: 0.24090600013733

php 7.4
array_filter: 0.057109117507935
foreach: 0.021071910858154
for i: 0.027867078781128
 **/

ini_set('memory_limit', '500M');
$data = range(0, 1000000);

// ARRAY FILTER
$start = microtime(true);
$newData = array_filter($data, function ($item) {
    return $item % 2;
});
$end = microtime(true);

echo "array_filter: ";
echo $end - $start . PHP_EOL;

// FOREACH
$start = microtime(true);
$newData = array();
foreach ($data as $item) {
    if ($item % 2) {
        $newData[] = $item;
    }
}
$end = microtime(true);

echo "foreach: ";
echo $end - $start . PHP_EOL;

// FOR
$start = microtime(true);
$newData = array();
$numItems = count($data);
for ($i = 0; $i < $numItems; $i++) {
    if ($data[$i] % 2) {
        $newData[] = $data[$i];
    }
}
$end = microtime(true);

echo "for i: ";
echo $end - $start . PHP_EOL;
