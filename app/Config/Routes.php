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
	$routes->setAutoRoute ( TRUE );
	//========================|| Rutas ||========================
	//========================|| GET ||========================
	$routes->get ( 'token', 'Auth::login' );
	//========================|| POST ||========================

	$routes->post ( 'uploadCFDIPlus', to: 'Conciliaciones::uploadCFDIPlus' /**@uses \App\Controllers\Conciliaciones::uploadCFDIPlus**/ );
	$routes->post ( 'chosenConciliation', 'Conciliaciones::chosenConciliation'  /**@uses \App\Controllers\Conciliaciones::chosenConciliation**/ );
	$routes->post ( 'chosenForDispersion', 'Dispersiones::chosenForDispersion'  /**@uses \App\Controllers\Dispersiones::chosenForDispersion**/ );
	//========================|| PUT ||========================
	//========================|| PATCH ||========================
	//========================|| DELETE ||========================
	//========================|| END ||========================
	
