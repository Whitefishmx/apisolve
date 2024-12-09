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
	$routes->add ( 'metaExpressWH', 'SolveExpress::ExpressWH' /**@uses \App\Controllers\SolveExpress::ExpressWH * */ );
	$routes->add ( 'MagicWH', 'Fintech\MagicPay::MagicWH' /**@uses \App\Controllers\Fintech\MagicPay::MagicWH* */ );
	$routes->add ( 'stpAbonos', 'Fintech\STP::wbAbonos' /**@uses \App\Controllers\Fintech\STP::wbAbonos* */ );
	//========================|| CRON ||========================
	$routes->add ( 'cep', 'Transactions::downloadCep' /**@uses \App\Controllers\Transactions::downloadCep* */ );
	$routes->add ( 'updPayControl', 'SolveExpress::updateAdvancePayrollControl' /**@uses \App\Controllers\SolveExpress::updateAdvancePayrollControl* */ );
	//========================|| CRON ||========================
	$routes->add ( 'layoutDownloader/(:segment)', 'FileController::downloadLayout/$1' /**@uses \App\Controllers\FileController::downloadLayout* */ );
	$routes->add ( 'cepDownloader/(:segment)', 'FileController::downloadCEP/$1' /**@uses \App\Controllers\FileController::downloadCEP* */ );
	//========================|| GET ||========================
	$routes->add ( '/', 'Home' );
	$routes->add ( 'acuseReune', 'Home::acuseReune' );
	$routes->add ( 'token', 'Auth::login' /**@uses \App\Controllers\Auth::Login* */ );
	$routes->add ( 'getLawsText', 'Data::getLaws' /**@uses \App\Controllers\Data::getLaws* */ );
	$routes->add ( 'getCPInfo', 'Data::getCPInfo' /**@uses \App\Controllers\Data::getCPInfo* */ );
	$routes->add ( 'getRegimen', 'Data::getRegimen' /**@uses \App\Controllers\Data::getRegimen* */ );
	$routes->add ( 'getCfdiInfo', 'Data::getCfdiInfo' /**@uses \App\Controllers\Data::getCfdiInfo* */ );
	$routes->add ( 'getInvoiceDocuments', 'Documents::getCFDI' /**@uses \App\Controllers\Documents::getCFDI* */ );
	$routes->add ( 'getMagicBalance', 'Fintech\MagicPay::getBalance' /**@uses \App\Controllers\Fintech\MagicPay::getBalance* */ );
	$routes->add ( 'conciliation', 'Conciliaciones::getConciliation' /**@uses \App\Controllers\Conciliaciones::getConciliation* */ );
	$routes->add ( 'dispersionPlus', 'Dispersiones::getDispersionPlus' /**@uses \App\Controllers\Dispersiones::getDispersionPlus* */ );
	$routes->add ( 'conciliationPlus', 'Conciliaciones::getConciliationPlus' /**@uses \App\Controllers\Conciliaciones::getConciliationPlus* */ );
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
	$routes->add ( 'tokenAlive', 'Auth::tokenAlive' /**@uses \App\Controllers\Auth::tokenAlive  * */ );
	//========================|| Condusef ||========================
	$routes->add ( 'reuneQueja', 'Condusef::reuneQueja' /**@uses \App\Controllers\Condusef::reuneQueja * */ );
	$routes->add ( 'redecoQueja', 'Condusef::redecoQueja' /**@uses \App\Controllers\Condusef::redecoQueja * */ );
	$routes->add ( 'reuneAclaracion', 'Condusef::reuneAclaracion' /**@uses \App\Controllers\Condusef::reuneAclaracion * */ );
	$routes->add ( 'reuneReclamacion', 'Condusef::reuneReclamacion' /**@uses \App\Controllers\Condusef::reuneReclamacion * */ );
	//========================|| MagicPay ||========================
	$routes->add ( 'getMagicTransferByID', 'Fintech\MagicPay::getTransfersById' /**@uses \App\Controllers\Fintech\MagicPay::getTransfersById* */ );
	$routes->add ( 'getMagicTransfers', 'Fintech\MagicPay::getTransfers' /**@uses \App\Controllers\Fintech\MagicPay::getTransfers* */ );
	$routes->add ( 'magicTransfer', 'Fintech\MagicPay::transfer' /**@uses \App\Controllers\Fintech\MagicPay::transfer* */ );
	//========================|| Solve Express ||========================
	$routes->add ( 'sExpressPeriods', 'SolveExpress::getPeriods' /**@uses \App\Controllers\SolveExpress::getPeriods */ );
	$routes->add ( 'sExpressDashboard', 'SolveExpress::dashboard' /**@uses \App\Controllers\SolveExpress::dashboard */ );
	$routes->add ( 'sExpressProfile', 'SolveExpress::userProfile' /**@uses \App\Controllers\SolveExpress::userProfile */ );
	$routes->add ( 'sExpressVerifyCurp', 'SolveExpress::verifyCurp' /**@uses \App\Controllers\SolveExpress::verifyCurp */ );
	$routes->add ( 'sExpressEmployees', 'SolveExpress::getEmployees' /**@uses \App\Controllers\SolveExpress::getEmployees */ );
	$routes->add ( 'sExpressRequest', 'SolveExpress::requestAdvance' /**@uses \App\Controllers\SolveExpress::requestAdvance */ );
	$routes->add ( 'sExpressUploadNomina', 'SolveExpress::uploadNomina' /**@uses \App\Controllers\SolveExpress::uploadNomina */ );
	$routes->add ( 'sExpressReport', 'SolveExpress::payrollAdvanceReport' /**@uses \App\Controllers\SolveExpress::payrollAdvanceReport */ );
	$routes->add ( 'excelCompany', 'SolveExpress::excelFileReportCompany' /**@uses \App\Controllers\SolveExpress::excelFileReportCompany */ );
	$routes->add ( 'sExpressReportCompany', 'SolveExpress::payrollAdvanceReportC' /**@uses \App\Controllers\SolveExpress::payrollAdvanceReportC */ );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| Profile ||========================
	$routes->patch ( 'setUser', 'Users::setUser' /**@uses \App\Controllers\Users::setUser* */ );
	//========================|| DELETE ||========================
	$routes->add ( 'sExpressFireOne', 'SolveExpress::fireOne' /**@uses \App\Controllers\SolveExpress::fireOne */ );
	//========================|| END ||========================
	
