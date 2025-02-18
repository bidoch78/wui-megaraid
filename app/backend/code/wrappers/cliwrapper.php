<?php 

declare(strict_types=1);

namespace megaraid\wrappers;

use megaraid\wrappers\dataWrapper;

abstract class cliWrapper {

    public abstract function getVersion():dataWrapper;
    public abstract function getNbAdapter():dataWrapper;
    public abstract function getAdapterInfo(int $adapterId):dataWrapper;
    public abstract function getVirtualDrives(int $adapterId):dataWrapper;
    public abstract function getPhysicalDrives(int $adapterId):dataWrapper;
    public abstract function getAdapterConfig(int $adapterId):dataWrapper;
    public abstract function getBootDrive(int $adapterId):dataWrapper;
    public abstract function getPatrolInfo(int $adapterId):dataWrapper;
    public abstract function getTasksPhysicalDrives(int $adapterId, array $pdId):dataWrapper;
    
    public abstract function executeCommand(int $adapterId, string $cmd, array $options = null): dataWrapper;

    public abstract function getlsblk(): string;
    public abstract function getsmartctl(string $ln): string;

    public bool $activateTrace = false;
    public array $traces = [];

    public function addTrace(string $cmd, string $return):void {
        if (!$this->activateTrace) return;
        $this->traces[] = [ "cmd" => $cmd, "return" => $return];
    }

    public function getName() {
        $class = explode("\\", $this::class);
        return $class[count($class)-1];
    }

}

?>