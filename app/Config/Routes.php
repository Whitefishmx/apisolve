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
	$routes->add ( 'MagicWH', 'Fintech\MagicPay::MagicWH' /**@uses \App\Controllers\Fintech\MagicPay::MagicWH* */ );
	//========================|| GET ||========================
	$routes->add ( 'token', 'Auth::login' /**@uses \App\Controllers\Auth::Login* */ );
	$routes->add ( '/', 'Home' );
	$routes->add ( 'acuseReune', 'Home::acuseReune' );
	$routes->add ( 'dispersionPlus', 'Dispersiones::getDispersionPlus' /**@uses \App\Controllers\Dispersiones::getDispersionPlus* */ );
	$routes->add ( 'conciliationPlus', 'Conciliaciones::getConciliationPlus' /**@uses \App\Controllers\Conciliaciones::getConciliationPlus* */ );
	$routes->add ( 'getInvoiceDocuments', 'Documents::getCFDI' /**@uses \app\Controllers\Documents::getCFDI* */ );
	$routes->add ( 'getCfdiInfo', 'Data::getCfdiInfo' /**@uses \App\Controllers\Data::getCfdiInfo* */ );
	$routes->add ( 'getCPInfo', 'Data::getCPInfo' /**@uses \app\Controllers\Data::getCPInfo* */ );
	$routes->add ( 'getRegimen', 'Data::getRegimen' /**@uses \app\Controllers\Data::getRegimen* */ );
	$routes->add ( 'conciliation', 'Conciliaciones::getConciliation' /**@uses \App\Controllers\Conciliaciones::getConciliation* */ );
	//=========================================================|| POST ||=========================================================
	//========================|| Conciliaciones ||========================
	$routes->add ( 'chosenConciliation', 'Conciliaciones::chosenConciliation' /**@uses \App\Controllers\Conciliaciones::chosenConciliation* */ );
	$routes->add ( 'chosenForDispersion', 'Dispersiones::chosenForDispersion' /**@uses \App\Controllers\Dispersiones::chosenForDispersion* */ );
	$routes->add ( 'uploadCFDIPlus', 'Conciliaciones::uploadCFDIPlus' /**@uses \App\Controllers\Conciliaciones::uploadCFDIPlus* */ );
	//========================|| Dispersion ||========================
	$routes->add ( 'consulta', 'Fintech\STP::testConsulta' /**@uses \App\Controllers\Fintech\STP::testConsulta* */ );
	$routes->add ( 'stpDispersion', 'Fintech\STP::testCobro' /**@uses \App\Controllers\Fintech\STP::testCobro* */ );
	//========================|| Session ||========================
	$routes->add ( 'toSignIn', 'Auth::signIn' /**@uses \App\Controllers\Auth::signIn * */ );
	//========================|| Condusef ||========================
	$routes->add ( 'reuneQueja', 'Condusef::reuneQueja' /**@uses \App\Controllers\Condusef::reuneQueja * */ );
	$routes->add ( 'reuneReclamacion', 'Condusef::reuneReclamacion' /**@uses \App\Controllers\Condusef::reuneReclamacion * */ );
	$routes->add ( 'reuneAclaracion', 'Condusef::reuneAclaracion' /**@uses \App\Controllers\Condusef::reuneAclaracion * */ );
	$routes->add ( 'redecoQueja', 'Condusef::redecoQueja' /**@uses \App\Controllers\Condusef::redecoQueja *
	 */ );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| DELETE ||========================
	//========================|| END ||========================
	
