<?php
	
	namespace CodeIgniter\Commands\Utilities\Routes;
	
	use CodeIgniter\Router\RouteCollection;
	
	/**
	 * @var RouteCollection $routes
	 */
	$routes->setDefaultNamespace ( 'App\Controllers' );
	$routes->setDefaultController ( 'Home' );
	$routes->setDefaultMethod ( 'index' );
	$routes->setTranslateURIDashes ( FALSE );
	$routes->set404Override ();
	$routes->setAutoRoute ( FALSE );
	//========================|| Rutas ||========================
	//========================|| GET ||========================
	$routes->get ( 'token', 'Auth::login' );
	$routes->get ( '/', 'Home' );
	//========================|| POST ||========================
	$routes->post ( 'uploadCFDIPlus', to: 'Conciliaciones::uploadCFDIPlus' /**@uses \App\Controllers\Conciliaciones::uploadCFDIPlus* */ );
	$routes->post ( 'chosenConciliation', 'Conciliaciones::chosenConciliation' /**@uses \App\Controllers\Conciliaciones::chosenConciliation* */ );
	$routes->post ( 'chosenForDispersion', 'Dispersiones::chosenForDispersion' /**@uses \App\Controllers\Dispersiones::chosenForDispersion* */ );
	$routes->post ( 'conciliationPlus', 'Conciliaciones::getConciliationPlus' /**@uses \App\Controllers\Conciliaciones::getConciliationPlus* */ );
	$routes->post ( 'cobro', 'Fintech\STP::testCobro' /**@uses \App\Controllers\Fintech\STP::testCobro* */ );
	$routes->post ( 'consulta', 'Fintech\STP::testConsulta' /**@uses \App\Controllers\Fintech\STP::testConsulta* */ );
	$routes->post ( 'stpTransactions', 'Fintech\STP::webhook' /**@uses \App\Controllers\Fintech\STP::webhook* */ );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| DELETE ||========================
	//========================|| END ||========================
	
