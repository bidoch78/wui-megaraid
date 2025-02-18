<?php 

declare(strict_types=1);

namespace megaraid\database;

use megaraid\database\parameter;
use megaraid\database\user;

class database {

	private static string $_dbname = "settings.sqlite3";
	private static int $_version = 1;

	private $_dbversion = 0;
	private ?\PDO $_db = null;

	private static ?database $_singleton = null;

	public static function get(): null|database {
		return self::$_singleton;
	}

	public static function getPDO(): null|\PDO {
		return self::$_singleton ? self::$_singleton->_db : null;
	}	

 	public function __construct() {
 		$this->_db = $this->openDatabase();
    }

	public function initialize() {
		self::$_singleton = $this;
		$this->doMigration();
	}

 	public function openDatabase() {

		$db = new \PDO("sqlite:" . __DIR__ . "/../../config/" . self::$_dbname);
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$db->exec("PRAGMA journal_mode = WAL; PRAGMA synchronous = normal; PRAGMA journal_size_limit = 6144000");

		return $db;

	}

 	private function doMigration() {

		//Create parameters table if not exists
	 	$this->_db->exec("CREATE TABLE IF NOT EXISTS parameters (
	 					name VARCHAR(255) PRIMARY KEY,
	 					value VARCHAR(255))");

		$sth = $this->_db->prepare("SELECT value FROM parameters WHERE name = 'version'");
	 	$sth->execute();
	 	$currentVersion = $sth->fetchAll(\PDO::FETCH_COLUMN);
		$currentVersion = isset($currentVersion[0]) ? $currentVersion[0] : 0;
	 	$this->_dbversion = ($currentVersion) ? intval($currentVersion) : 0;

	 	if ($this->_dbversion == self::$_version) return;
		
		try {

			$this->_db->beginTransaction();
			$this->migration_0();
			$this->migration_1();
			parameter::update("version", (string)self::$_version);
			$this->_db->commit();
		
		}
		catch(\Exception $ex) {
			$this->_db->rollBack();
		}

	}

	/////////////////////////////////////
	// MIGRATION
	/////////////////////////////////////

	private function migration_0() {

		if ($this->_dbversion !== 0) return;

		//Creation table drives
		$this->_db->exec("CREATE TABLE IF NOT EXISTS historicaldatadrives (
			update_at INTEGER NOT NULL,
			wwn VARCHAR(255) NOT NULL,
			temp_celcius REAL NOT NULL,
			PRIMARY KEY (update_at, wwn))");

		//Creation table user
		$this->_db->exec("CREATE TABLE IF NOT EXISTS users (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			login VARCHAR(255) NOT NULL,
			password VARCHAR(255) NOT NULL,
			name VARCHAR(255) NOT NULL,
			activated INTEGER DEFAULT 1,
			create_at INTEGER NOT NULL,
			update_at INTEGER NOT NULL,
			UNIQUE (login))");

		user::create("admin", "admin", "admin");

		//Creation table token
		$this->_db->exec("CREATE TABLE IF NOT EXISTS tokens (
			token VARCHAR(65) PRIMARY KEY,
			create_at INTEGER NOT NULL,
			expiration INTEGER NOT NULL,
			user_id INTEGER DEFAULT -1,
			link_to VARCHAR(65),
			data TEXT)");

	}

	private function migration_1() {

		if ($this->_dbversion !== 1) return;

		// ....

	}

}

?>