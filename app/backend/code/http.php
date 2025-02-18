<?php 

declare(strict_types=1);

namespace megaraid;

use megaraid\core;

class http {
	
 	static function getURLData():array {
		
 		$curURL = explode('/', $_SERVER["SCRIPT_NAME"]);
 		$initURL = explode('/', $_SERVER["REQUEST_URI"]);
		
 		$urlPath = array();
 		for($i = 0; $i < count($curURL); $i++) {
 			if ($curURL[$i] != $initURL[$i]) {
 				$urlPath = array_slice($initURL, $i);
 				break;
 			}
 		}
		
 		$urlParams = array();
 		if (count($urlPath) > 0) {
 			$findURLParam = explode("?", $urlPath[count($urlPath)-1]);
 			$urlPath[count($urlPath)-1] = $findURLParam[0];
 			array_shift($findURLParam);
 			$findURLParam = implode("?", $findURLParam);
 			foreach(explode("&", $findURLParam) as $param) {
 				$iparam = explode("=", $param);
 				$urlParams[$iparam[0]] = (count($iparam) > 1) ? $iparam[1] : "";
 			}
 		}
		
 		return array(
 			'rewrite_url' => $_SERVER["SCRIPT_NAME"],
 			'url' => $_SERVER["REQUEST_URI"],
             'urlParams' => $urlParams,
             'urlPath' => $urlPath
 		);
		
 	}

	static function isHTTPS(): bool {

		if (core::env("force_https", "false") === "true") return true;

		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');

	}

}

?>