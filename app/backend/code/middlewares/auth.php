<?php 

declare(strict_types=1);

namespace megaraid\middlewares;

use \megaraid\middlewares\middleware;
use \megaraid\exceptions\authTokenException;
use \megaraid\exceptions\tokenValidatorException;
use \megaraid\core;
use \megaraid\controllers\token;
use \megaraid\database\user as dbUser;
use \megaraid\controllers\user;

class auth extends middleware {

 	private ?dbUser $_user = null;
 	private ?token $_token = null;
	
 	public function getUser():null|dbUser { return $this->_user; }
 	public function getToken():null|token { return $this->_token; }
		
 	public function check($options = null):bool {

//		if (core::env("noauthentication","false") == "true") {
//			$this->_user = user::byId(1);
//			return true;
//		}

		$headers = getallheaders();

		if(!array_key_exists('Xsrf-Token', $headers) || empty(trim($headers['Xsrf-Token']))) throw new authTokenException();
		
		$xsrftoken = trim($headers['Xsrf-Token']);

		$accessTokenPayload = null;
		$refreshTokenPayload = null;

		if (isset($_COOKIE["access_token"])) {
		 	$accessTokenPayload = token::getPayload($_COOKIE["access_token"]);
			if ($accessTokenPayload && $accessTokenPayload["xcrf"] != $xsrftoken) $accessTokenPayload = null;
		}

		if ($accessTokenPayload) {
			$this->_user = user::byId($accessTokenPayload["sub"]);
			if ($this->_user) return ($this->_user->activated === 1);
		}

		if (isset($_COOKIE["refresh_token"])) {
			$refreshTokenPayload = token::getPayload($_COOKIE["refresh_token"]);
		   if ($refreshTokenPayload && $refreshTokenPayload["xcrf"] != $xsrftoken) $refreshTokenPayload = null;
	   	}

		if ($refreshTokenPayload) {
			$userId = 0;
			$data = token::generateAccessToken($refreshTokenPayload, $userId);
			if ($data) {
				$this->addPrependData($data);
				$this->_user = user::byId($userId);
				if ($this->_user) return ($this->_user->activated === 1);
			}
	   	}

 	 	throw new authTokenException();

 	}

}