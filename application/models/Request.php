<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Request
{
    public static function getStaticMethod() {
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_SPECIAL_CHARS);
        $method = preg_replace('/[^A-Z]/', '', $method);
        $method = in_array($method, ['GET', 'POST','PATCH','PUT','DELETE']) ? $method : false;

        return $method;
    }

    public static function getStaticIPAddress(string &$denied = null) {
        $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        $ip = preg_replace('/[^0-9\.]/', '', $ip);
        $denied = $ip;
        $ip = in_array($ip, [
            '127.0.0.1',
            '10.57.1.128',
            '10.57.1.179',
            '198.251.66.141',
        ]) ? $ip : false;

        return $ip;
    }

    public static function getHost() {
        $host = filter_input(INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_SPECIAL_CHARS);
        $host = preg_replace('/[^a-z\.\-]/', '', $host);
        return $host;
    }

    public static function getContentType() {
        $type = filter_input(INPUT_SERVER, 'CONTENT_TYPE', FILTER_SANITIZE_SPECIAL_CHARS);
        $type = preg_replace('/[^a-z\/\-;=8]/', '', strtolower(trim($type)));
        $allowed = ['application/json;charset=utf-8', 'application/json', 'multipart/form-data'];
        $type = in_array($type, $allowed) ? $type : false;

        return $type;
    }

    public static function getBody() {
        $input = file_get_contents("php://input");
        $input = preg_replace('/[^\x{0021}-\x{005F}\x{0061}-\x{00FC} ]/u', '', $input);
        return json_decode($input, true);
    }

    public static function getAuthorization() {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $matches = null;
                $authorization = filter_var($headers['Authorization'], FILTER_UNSAFE_RAW);
                $authorization = trim($authorization);
                $result = preg_match('/Bearer\s([a-zA-Z0-9\.\-\/\+=]+)/', $authorization, $matches);
                if ($result > 0 && count($matches) > 0) {
                    return $matches[1];
                }
            }
        }
        return false;
    }
}
