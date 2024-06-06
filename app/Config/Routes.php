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
	$routes->add ( 'token', 'Auth::login' /**@uses \App\Controllers\Auth::Login* */ );
	$routes->add ( '/', 'Home' );
	$routes->add ( 'dispersionPlus', 'Dispersiones::getDispersionPlus' /**@uses \App\Controllers\Dispersiones::getDispersionPlus* */ );
	$routes->add ( 'conciliationPlus', 'Conciliaciones::getConciliationPlus' /**@uses \App\Controllers\Conciliaciones::getConciliationPlus* */ );
	$routes->add ( 'getCfdiInfo', 'Data::getCfdiInfo' /**@uses \App\Controllers\Data::getCfdiInfo* */ );
	$routes->add ( 'getCPInfo', 'Data::getCPInfo' /**@uses \app\Controllers\Data::getCPInfo* */ );
	$routes->add ( 'getRegimen', 'Data::getRegimen' /**@uses \app\Controllers\Data::getRegimen* */ );
	$routes->add ( 'getInvoiceDocuments', 'Documents::getCFDI' /**@uses \app\Controllers\Documents::getCFDI* */ );
	//========================|| POST ||========================
	$routes->add ( 'uploadCFDIPlus', 'Conciliaciones::uploadCFDIPlus' /**@uses \App\Controllers\Conciliaciones::uploadCFDIPlus* */ );
	$routes->add ( 'chosenConciliation', 'Conciliaciones::chosenConciliation' /**@uses \App\Controllers\Conciliaciones::chosenConciliation* */ );
	$routes->add ( 'chosenForDispersion', 'Dispersiones::chosenForDispersion' /**@uses \App\Controllers\Dispersiones::chosenForDispersion* */ );
	$routes->add ( 'cobro', 'Fintech\STP::testCobro' /**@uses \App\Controllers\Fintech\STP::testCobro* */ );
	$routes->add ( 'consulta', 'Fintech\STP::testConsulta' /**@uses \App\Controllers\Fintech\STP::testConsulta* */ );
	$routes->add ( 'playDispercion', 'Dispersiones::playDispersiones' /**@uses \App\Controllers\Dispersiones::playDispersiones* */ );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| DELETE ||========================
	//========================|| END ||========================
	
