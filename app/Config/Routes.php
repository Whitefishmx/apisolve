<?php
	
	namespace Config;
	
	use CodeIgniter\Router\RouteCollection;
	
	/**
	 * @var RouteCollection $routes
	 */
	$routes->setDefaultNamespace ( 'App\Controllers' );
	$routes->setDefaultController ( 'Home' );
	$routes->setDefaultMethod ( 'index' );
	$routes->setTranslateURIDashes ( FALSE );
	$routes->set404Override ();
	$routes->setAutoRoute ( TRUE );
	//========================|| Rutas ||========================
	//========================|| GET ||========================
	$routes->get ( 'token', 'Auth::login' );
	//========================|| POST ||========================
	$routes->post ( 'uploadCFDIPlus', 'Conciliaciones::uploadCFDIPlus' );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| DELETE ||========================
	//========================|| END ||========================
	$routes->get ( '/', 'Home::index' );
	$routes->get ( 'client', 'Client::index' );
	$routes->post ( 'client', 'Client::store' );
	$routes->get ( 'client/(:num)', 'Client::show/$1' );
	$routes->post ( 'client/(:num)', 'Client::update/$1' );
	$routes->delete ( 'client/(:num)', 'Client::destroy/$1' );
	
	
