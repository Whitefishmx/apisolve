<?php
	
	namespace CodeIgniter\Commands\Utilities\Routes;
	
	use CodeIgniter\Router\RouteCollection;
	
	/**
	 * @var RouteCollection $routes
	 */
	$routes->setDefaultNamespace ( 'App\Controllers' );
	$routes->setTranslateURIDashes ( FALSE );
	$routes->setDefaultController ( 'Home' );
	$routes->setDefaultMethod ( 'index' );
	$routes->setAutoRoute ( FALSE );
	//========================|| Rutas ||========================
	//========================|| Webhook ||========================
	$routes->add ( 'stpTransactions', 'Fintech\STP::wbDispersion' /**@uses \App\Controllers\Fintech\STP::wbDispersion* */ );
	$routes->add ( 'stpAbonos', 'Fintech\STP::wbAbonos' /**@uses \App\Controllers\Fintech\STP::wbAbonos* */ );
	//========================|| GET ||========================
	$routes->add ( 'token', 'Auth::login' );
	$routes->add ( '/', 'Home' );
	//========================|| POST ||========================
	$routes->add ( 'uploadCFDIPlus', to: 'Conciliaciones::uploadCFDIPlus' /**@uses \App\Controllers\Conciliaciones::uploadCFDIPlus* */ );
	$routes->add ( 'chosenConciliation', 'Conciliaciones::chosenConciliation' /**@uses \App\Controllers\Conciliaciones::chosenConciliation* */ );
	$routes->add ( 'chosenForDispersion', 'Dispersiones::chosenForDispersion' /**@uses \App\Controllers\Dispersiones::chosenForDispersion* */ );
	$routes->add ( 'conciliationPlus', 'Conciliaciones::getConciliationPlus' /**@uses \App\Controllers\Conciliaciones::getConciliationPlus* */ );
	$routes->add ( 'cobro', 'Fintech\STP::testCobro' /**@uses \App\Controllers\Fintech\STP::testCobro* */ );
	$routes->add ( 'consulta', 'Fintech\STP::testConsulta' /**@uses \App\Controllers\Fintech\STP::testConsulta* */ );
	$routes->add ( 'stpTransactions', 'Fintech\STP::webhook' /**@uses \App\Controllers\Fintech\STP::webhook* */ );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| DELETE ||========================
	//========================|| END ||========================
	
