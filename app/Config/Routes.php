<?php
	namespace Config;
	use CodeIgniter\Router\RouteCollection;
	
	/**
	 * @var RouteCollection $routes
	 */
	$routes->setDefaultNamespace('App\Controllers');
	$routes->setDefaultController('Home');
	$routes->setDefaultMethod('index');
	$routes->setTranslateURIDashes(false);
	$routes->set404Override();
	$routes->setAutoRoute(true);
	//========================|| Rutas ||========================
	$routes->get('/', 'Home::index');
	$routes->get('client', 'Client::index');
	$routes->post('client', 'Client::store');
	$routes->get('client/(:num)', 'Client::show/$1');
	$routes->post('client/(:num)', 'Client::update/$1');
	$routes->delete('client/(:num)', 'Client::destroy/$1');
	
	
	$routes->get ('token', 'Auth::login');
	
	
	
	
	if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
		require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
	}
