<?php 

    declare(strict_types=1);

    require_once(__DIR__ . "/../code/autoload.php");

	use megaraid\database\database;

	//initialize db connexion
	$db = new database();
	$db->initialize();

    use megaraid\core;
	
	header("Access-Control-Allow-Origin: " . core::getAccessControlAllowOrigin());
    header("Access-Control-Allow-Methods: *");
	header("Content-Type: application/json; charset=UTF-8");
	header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

	core::loadWrapper();
	core::loadSataReader();
    core::loadRoutes();

	try {
		
        core::parseRawData(true);
        core::loadRoutes();
	    core::processRequest();
	
	}
	catch(\Exception $ex) {
        core::returnJSONError($ex);
	}

?>