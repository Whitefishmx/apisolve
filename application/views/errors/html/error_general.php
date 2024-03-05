<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$resp = [
    'error' => $status_code,
    'error_description' => utf8_encode($heading),
    'reason' => utf8_encode(strip_tags($message)),
];
header('Content-type: application/json; charset=utf-8');
echo json_encode($resp);