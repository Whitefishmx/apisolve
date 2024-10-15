<?php
	namespace App\Controllers;
	use App\Controllers\BaseController;
	use CodeIgniter\HTTP\ResponseInterface;
	use Exception;
	use App\Models\ClientModel;
	class Client extends PagesStatusCode {
		public function index () {
			$model = new ClientModel();
			return $this->getResponse ( [
				'message' => 'Clients retrieved successfully',
				'clients' => $model->findAll (),
			] );
		}
		public function store () {
			$rules = [
				'name' => 'required',
				'email' => 'required|min_length[6]|max_length[50]|valid_email|is_unique[client.email]',
				'retainer_fee' => 'required|max_length[255]',
			];
			$input = $this->getRequestInput ( $this->request );
			if ( !$this->validateRequest ( $input, $rules ) ) {
				return $this->getResponse ( $this->validator->getErrors (), ResponseInterface::HTTP_BAD_REQUEST );
			}
			$clientEmail = $input[ 'email' ];
			$model = new ClientModel();
			$model->save ( $input );
			$client = $model->where ( 'email', $clientEmail )->first ();
			return $this->getResponse ( [
				'message' => 'Client added successfully',
				'client' => $client,
			] );
		}
		public function show ( $id ) {
			try {
				
				$model = new ClientModel();
				$client = $model->findClientById ( $id );
				if ( !$client[ 0 ] ) {
					return $this->serverError ( 'Error proceso incompleto', $client[ 1 ] );
				}
				return $this->getResponse ( [
					'message' => 'Client retrieved successfully',
					'client' => $client,
				] );
				
			} catch ( Exception $e ) {
				return $this->getResponse ( [
					'message' => 'Could not find client for specified ID',
				], ResponseInterface::HTTP_NOT_FOUND );
			}
		}
		public function update ( $id ) {
			try {
				
				$model = new ClientModel();
				$model->findClientById ( $id );
				if ( !$model[ 0 ] ) {
					return $this->serverError ( 'Error proceso incompleto', $model[ 1 ] );
				}
				$input = $this->getRequestInput ( $this->request );
				$model->update ( $id, $input );
				$client = $model->findClientById ( $id );
				if ( !$client[ 0 ] ) {
					return $this->serverError ( 'Error proceso incompleto', $client[ 1 ] );
				}
				return $this->getResponse ( [
					'message' => 'Client updated successfully',
					'client' => $client,
				] );
				
			} catch ( Exception $exception ) {
				
				return $this->getResponse ( [
					'message' => $exception->getMessage (),
				], ResponseInterface::HTTP_NOT_FOUND );
			}
		}
		public function destroy ( $id ) {
			try {
				
				$model = new ClientModel();
				$client = $model->findClientById ( $id );
				if ( !$client[ 0 ] ) {
					return $this->serverError ( 'Error proceso incompleto', $client[ 1 ] );
				}
				$model->delete ( $client );
				return $this->getResponse ( [
					'message' => 'Client deleted successfully',
				] );
				
			} catch ( Exception $exception ) {
				return $this->getResponse ( [
					'message' => $exception->getMessage (),
				], ResponseInterface::HTTP_NOT_FOUND );
			}
		}
		public function getClientByArgs(){
			$args = $this->getRequestInput ( $this->request )['argumentos'];
			$client = new ClientModel();
			$res = $client->getClientByArgs ($args);
			if ( !$res[ 0 ] ) {
				return $this->serverError ( 'Error proceso incompleto', $res[ 1 ] );
			}
			return $this->getResponse ( [
				$res
			], ResponseInterface::HTTP_NOT_FOUND );
		}
	}
