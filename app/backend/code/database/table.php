<?php 

declare(strict_types=1);

namespace megaraid\database;

abstract class table {

	protected $_data = [];

	public function __construct(array $fieldnames) {
		if (count($this->_data)) return;
		$this->_data = $fieldnames;
	}

	public function __set(string $name, mixed $value): void {
		$this->_data[$name] = $value;
	}

	public function __get(string $name) {
		if (!array_key_exists($name, $this->_data)) throw new \Exception("field $name not found in class " . table::class);
		return $this->_data[$name];
	}

	public function getData(array $fields = null) {
		
		if (!$fields) return $this->_data;

		$data = [];
		foreach($fields as $field) $data[$field] = $this->$field;

		return $data;

	}

	public static function getUTCNow(): \DateTime {
		return new \DateTime("now", new \DateTimeZone("UTC"));
	}

	public static function getUTCDate(\DateTime $dt) {
		$dt = clone $dt;
		$dt->setTimezone(new \DateTimeZone("UTC"));
		return $dt;
	}

}

?>