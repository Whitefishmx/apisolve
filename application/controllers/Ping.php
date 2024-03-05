<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ping extends CI_Controller
{
    public function index() {
        $this->load->model('response');
        $input = Request::getBody();
        return $this->response->sendResponse($input, 0);
    }
}
