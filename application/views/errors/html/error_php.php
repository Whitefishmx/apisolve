<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$resp = [
    'error' => 500,
    'error_description' => 'An Error was encountered',
    'reason' => utf8_encode($message),
    'detail' => [
        'severity' => $severity,
        'filename' => $filepath,
        'line' => $line,
    ]
];
header('Content-type: application/json; charset=utf-8');
die(json_encode($resp));
