<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Prices extends CI_Controller
{
    public function index(string $article = null) {
        $this->load->model('response');
        $error = 400;
        $resp = [
            'error' => 'invalid_request',
            'error_description' => 'Se esperaban artículos, listas de precio y '
                . 'ambiente de trabajo.',
            'reason' => '',
        ];
        $env = 'SANDBOX';
        if (Request::getStaticMethod() == 'POST') { #TODO: FALTA VALIDAR QUE HAYA UN TOKEN VALIDO
            $data['articles'] = [];
            $data['pricelists'] = [];
            if ($article !== null && is_numeric($article)) {
                $data['articles'][] = $article;
            }
            if ($input = Request::getBody()) {
                $env = $input['environment'] ?? 'SANDBOX';
                if (isset($input['articles']) && is_array($input['articles'])) {
                    $data['articles'] = $input['articles'];
                }
                if (isset($input['pricelists']) && is_array($input['pricelists'])) {
                    $data['pricelists'] = $input['pricelists'];
                }
            }
            if (count($data['articles']) > 0) {
                $this->load->model('sap');
                $resp = $this->sap->getPriceList($data, $env, $error);
            }
        }
        else {
            $error = 405;
        }
        return $this->response->sendResponse($resp, $error);
    }
}
