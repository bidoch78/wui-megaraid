<?php 

declare(strict_types=1);

namespace megaraid;

use megaraid\middlewares\middleware;

class request {
	
    private ?array $_urlData = null;
    private ?array $_routeData = null;
	private ?array $_request = null;
	private ?array $_middlewares = null;
    private ?array $_headers = null;

	public function __construct(request $request = null) {
	 	if ($request) {   
	 		$this->_request = $request->_request;
            $this->_urlData = $request->_urlData;
            $this->_routeData = $request->_routeData;
            $this->_middlewares = $request->_middlewares;
            $this->_headers = $request->_headers;
	 	}
	}
	
    public function loadURLData():void {
        $this->_headers = getallheaders();
        $this->_urlData = http::getURLData();
        $this->_routeData = route::routeData($this->_urlData["urlPath"], $this->_urlData["urlParams"]);
    }

    public function getURLData():null|array { return $this->_urlData; }
    public function getRouteData():null|array { return $this->_routeData; }

    public function addMiddleware(middleware $middleware):void {
        if (!$this->_middlewares) $this->_middlewares = [];
        $this->_middlewares[] = $middleware;
    }

    public function getMiddleware(string $name):null|middleware {
        if (!$this->_middlewares) return null;
        foreach($this->_middlewares as $midw) {
            if ($midw->getName() == $name) return $midw;
        }
        return null;
    }

    public function getMiddlewares(): array { return $this->_middlewares; }

	public function getRequestParameter($name) {
	 	return isset($this->_routeData["parameters"][$name]) ? $this->_routeData["parameters"][$name] : null;
	}
	
	public function getRequestParameters() {
	 	return $this->_routeData["parameters"];
	}
	
	public function getAnyParameter($name) {
		
	 	$dataroute = $this->_routeData["data"];
	 	$datamethods = $dataroute["methods"];

	 	$checkPOST = false;
	 	$checkGET = false;
	 	if (!$datamethods) {
	 		$checkPOST = true; $checkGET = true;
	 	}
	 	else {
	 		foreach($datamethods as $method) {
	 			if ($method == "POST") $checkPOST = true;
	 			elseif ($method == "GET") $checkGET = true;
	 		}
	 	}

	 	if ($checkPOST && isset($_POST[$name])) return $_POST[$name];
	 	if ($checkGET && isset($this->_routeData["parameters"][$name])) return $this->_routeData["parameters"][$name];
		
	 	return null;
		
	}
	
	public function getPostValues() {
	 	return $_POST;
	}
	
	public function getPostValue($name) {
	 	return isset($_POST[$name]) ? $_POST[$name] : null;
	}

}

?>