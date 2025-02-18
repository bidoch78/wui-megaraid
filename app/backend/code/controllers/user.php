<?php 

declare(strict_types=1);

namespace megaraid\controllers;

use \megaraid\controllers\controller;
#use \slei\controllers\token;
use \megaraid\database\user as dbUser;
use \megaraid\exceptions\megaraidException;

class user extends controller {

    public function login():array {

        if (!$this->request->getAnyParameter("login") || !$this->request->getAnyParameter("password")) throw new megaraidException("Incorrect login or password", megaraidException::LOGIN_FAILED);
		
        $login = $this->request->getAnyParameter("login");
        $password = $this->request->getAnyParameter("password");

        //db Identification
        $user = dbUser::getByLogin($login);
        if (!$user) throw new megaraidException("incorrect login or password", megaraidException::LOGIN_FAILED);
        
        //check password
        if (!$user->checkPassword($password))
            throw new megaraidException("incorrect login or password", megaraidException::LOGIN_FAILED);
        
        //if find generate refresh_token & generate access_token linked to refresh_token
        return token::generateTokens($user);
        
    }

    public function logout(): array {
        
    //     //Delete tokens
    //     $auth = $this->getAuth();
    //     //delete RefreshToken => database delete on cascade
    //     $auth->getToken()->deleteRefreshToken();

    //     return [];      

    }

    public static function byId(int $id): null|dbUser {
        return dbUser::getById($id);
    }

}

?>