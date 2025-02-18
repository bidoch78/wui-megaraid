<?php 

declare(strict_types=1);

namespace megaraid\database;

use megaraid\database\database;
use megaraid\database\table;

class user extends table {

	public function __construct() {
		parent::__construct([ "id" => null, "login" => null, "password" => null, "name" => null, "activated" => null, "create_ad" => null, "udpated_at" => null ]);
	}	

  	public static function create(string $login, string $pwd, string $name):void {

        $date_utc = table::getUTCNow();

	    $sth = database::getPDO()->prepare("INSERT INTO users (login, password, name, create_at, update_at) 
										VALUES (:login, :password, :name, :date, :date)");

	 	$sth->execute(['login' => $login, 'password' => password_hash($pwd, PASSWORD_BCRYPT, ["cost" => 10 ]), "name" => $name, "date" => $date_utc->getTimestamp()]);

	}

	public function checkPassword($key) {
		return password_verify($key, $this->password);
	}

 	public static function getById(int $key):null|user {

        $sth = database::getPDO()->prepare("SELECT * FROM users WHERE id = :id");
        $sth->execute(['id' => $key]);

        $users = $sth->fetchAll(\PDO::FETCH_CLASS, "megaraid\database\user");
        return (count($users)) ? $users[0] : null;

 	}

 	public static function getByLogin(string $key):null|user {

        $sth = database::getPDO()->prepare("SELECT * FROM users WHERE login like :login");
        $sth->execute(['login' => $key]);

        $users = $sth->fetchAll(\PDO::FETCH_CLASS, "megaraid\database\user");
        return (count($users)) ? $users[0] : null;

 	}       

}


?>