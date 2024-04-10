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
	$routes->post ( 'chosenConciliation', 'Conciliaciones::chosenConciliation' );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| DELETE ||========================
	//========================|| END ||========================
	
