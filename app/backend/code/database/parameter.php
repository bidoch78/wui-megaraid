<?php 

declare(strict_types=1);

namespace megaraid\database;

use megaraid\database\database;

class parameter  {
	
 	public static function update(string $key, ?string $value):void {

		$sth = database::getPDO()->prepare("INSERT OR REPLACE INTO parameters (name, value) 
										VALUES (:name, :value)");

		$sth->execute(['name' => $key, 'value' => $value]);

	}

 	public static function get(array|string $key):null|string {


 	}   

}


?>