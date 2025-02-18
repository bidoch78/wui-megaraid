<?php 

declare(strict_types=1);

namespace megaraid\middlewares;

use megaraid\request;

abstract class middleware {
	
	protected request $request;
	protected ?array $prepdata = null;

	public function __construct(request $request) {
		$this->request = $request;
	}

	public function getName():string {
		$classN = $this::class;
		$classN = explode("\\", $classN);
		return $classN[count($classN)-1];
	}

	public function addPrependData(array $data): void {
		if (!$this->prepdata) $this->prepdata = [];
		$this->prepdata = array_merge($this->prepdata, $data);
	}

	public function prependData(array $data): array {
		if (!$this->prepdata) return $data;
		return array_merge($data, $this->prepdata);
	}

	abstract public function check($options = null):bool;
	
}

?>