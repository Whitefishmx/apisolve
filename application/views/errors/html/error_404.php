<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$resp = [
    'error' => 404,
    'error_description' => utf8_encode($heading),
    'reason' => utf8_encode(strip_tags($message)),
];
header('Content-type: application/json; charset=utf-8');
die(json_encode($resp));
