<?php

$query = '{"query":{"bool":{"should":[{"term":{"isIdentifier":{"value":"870970-basis:47234727","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:28240562","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:28999410","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:51376404","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:52060125","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:50823539","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:29668485","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:54989393","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:28240627","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:50712192","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:29590311","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:51342836","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:29630461","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:53999204","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:27005306","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:52626951","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:51323599","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:50828905","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:52126851","boost":1}}},{"term":{"isIdentifier":{"value":"870970-basis:52028973","boost":1}}}]}},"size":20}';


$ch = curl_init($_GET['url']);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($query))
);

$result = curl_exec($ch);

echo $result;