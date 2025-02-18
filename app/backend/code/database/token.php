<?php 

declare(strict_types=1);

namespace megaraid\database;

use megaraid\database\database;
use megaraid\database\table;

class token extends table {

    public function __construct() {
        parent::__construct([ "token" => null, "create_at" => null, "expiration" => null, "user_id" => null, "link_to" => null, "data" => null ]);
        self::removeExpiredToken();       
    }

    public function delete(): void {
     
        $sth = database::getPDO()->prepare("DELETE FROM tokens WHERE token = :token");
        $sth->execute(["token" => $this->token]);
        
    }

    public static function removeExpiredToken(): void {

        $dt = table::getUTCNow();
        $sth = database::getPDO()->exec("DELETE FROM tokens WHERE expiration < " . $dt->getTimestamp());

    } 

    public static function generateToken(): string {
        return hash('sha256', uniqid() . rand());
    }    

    public static function create(string $id, \DateTime $create_at, \DateTime $exp, int $user, string $data = null, string $linkTo = null): void {

        $sth = database::getPDO()->prepare("INSERT INTO tokens (token, create_at, expiration, user_id, link_to, data) 
                                            VALUES (:token, :create_at, :expiration, :user_id, :link_to, :data)");

        $sth->execute(['token' => $id, 'create_at' => $create_at->getTimestamp(), "expiration" => $exp->getTimestamp(), "user_id" => $user, "link_to" => $linkTo, "data" => $data ]);

    }

    public static function getByToken(string $token):null|token {

        $sth = database::getPDO()->prepare("SELECT * FROM tokens WHERE token = :token");
        $sth->execute(['token' => $token]);

        $item = $sth->fetchAll(\PDO::FETCH_CLASS, "megaraid\database\\token");
        return (count($item)) ? $item[0] : null;

 	}

}


?>