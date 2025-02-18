<?php 

declare(strict_types=1);

namespace megaraid;

class route {

    private ?array $_routes = null;

    public function addRoute(string $def, string $call, array $middlewares = null, array $method = null): void {

        if (!$this->_routes) $this->_routes = array();
            
        $call = explode("@", $call);
            
        $defInfo = array();
        foreach(explode("/", $def) as $defI) {
                
            $defI = trim($defI);
            if (!$defI) throw new Exception("error(1) on route `" . $def . "`");
                
            $defItem = array("initial" => $defI, "name" => $defI, "is_param" => false);
                
            if ($defI[0] == "{" && $defI[strlen($defI)-1] == "}") {
                $defItem["name"] = substr($defI, 1, strlen($defI) - 2);
                $defItem["is_param"] = true;
            }
                
            if (!$defItem["name"]) throw new Exception("error(2) on route `" . $def . "`");
                
            $defInfo[] = $defItem;
                
        }
            
        if ($method) {
            for($i = 0; $i < count($method); $i++) $method[$i] = strtoupper($method[$i]);
        }

        $this->_routes[] = array("def" => $defInfo, "class" => $call[0], "method" => $call[1], "middlewares" => $middlewares, "methods" => $method);
        
    }

    public function getRouteData(array $urlpath, array $urlparams = array()):array|null {
		
		$routeData = array("url" => $urlpath, "data" => null, "parameters" => array());
		
		foreach($this->_routes as $route) {
			
		 	if (count($urlpath) != count($route["def"])) continue;
		
		    $params = $urlparams;
			
		    $ok = true;
		    for($i = 0; $i < count($urlpath); $i++) {
		        if ($route["def"][$i]["is_param"]) {
		            $params[$route["def"][$i]["name"]] = $urlpath[$i];
		        }
		        else {
		            if (strcasecmp($urlpath[$i], $route["def"][$i]["name"]) != 0) $ok = false;
		        }
		    }
			
		 	if ($ok) {
		 		$routeData["data"] = $route;
		 		$routeData["parameters"] = $params;
		 		return $routeData;
		 	}
			
		}
		
		return null;
		
	}

    /******
     * SINGLETON
     */

    private static ?route $_route = null;

    public static function add(string $def, string $call, array $middlewares = null, array $method = null): void {
        self::get()->addRoute($def, $call, $middlewares, $method);
    }

    public static function get():route {
        if (!self::$_route) self::$_route = new route();
        return self::$_route;
    }

    public static function routeData(array $urlpath, array $urlparams = array()):array|null {
        return self::get()->getRouteData($urlpath, $urlparams);
    }

}

?>