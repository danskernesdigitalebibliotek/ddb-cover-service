<?php

$query = '{"query":{"bool":{"should":[{"bool":{"must":[{"terms":{"isIdentifier":["870970-basis:29506914","870970-basis:29506906","870970-basis:22506306"]}},{"term":{"isType":{"value":"pid","boost":1}}}]}}]}}}';


$ch = curl_init($_GET['url'] . '/app_dev/search/_search');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($query))
);

$result = curl_exec($ch);

echo $result;