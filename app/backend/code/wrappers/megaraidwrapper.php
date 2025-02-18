<?php

declare(strict_types=1);

namespace megaraid\wrappers;

use megaraid\wrappers\cliwrapper;
use megaraid\wrappers\dataWrapper;

class megaraidwrapper extends cliwrapper {

    public function getlsblk(): string {
        $cmd = "lsblk -J -l -O";
        $response = shell_exec($cmd);  
        $this->addTrace($cmd, $response);
        return $response;
    }

    public function getsmartctl(string $ln): string {
        $cmd = "smartctl --all -j " . $ln;
        $response = shell_exec($cmd);
        $this->addTrace($cmd, $response);
        return $response;
    }

    private function callCommand($param): string {
        $cmd = "/usr/sbin/megacli " . $param;
        $response = shell_exec($cmd);
        $this->addTrace($cmd, $response);
        return $response;
    }

    public function getVersion():dataWrapper {
        $response = $this->callCommand("-v");
        return new dataWrapper($response ? $response : "");
    }

    public function getNbAdapter():dataWrapper {
        $response = $this->callCommand("-AdpCount");
        return new dataWrapper($response ? $response : "");
    }

    public function getAdapterInfo(int $adapterId):dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        $response = $this->callCommand("-AdpAllInfo -a" . $adapter);
        return new dataWrapper($response ? $response : "", "pi:adapter #*");
    }

    public function getVirtualDrives(int $adapterId):dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        $response = $this->callCommand("-LDInfo -Lall -a" . $adapter);
        return new dataWrapper($response ? $response : "", "pi:adapter*-- virtual drive*");
    }

    public function getPhysicalDrives(int $adapterId):dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        $response = $this->callCommand("-PDList -a" . $adapter);
        return new dataWrapper($response ? $response : "", "pi:adapter #*");
    } 
    
    public function getAdapterConfig(int $adapterId):dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        $response = $this->callCommand("-CfgDsply -a" . $adapter);
        return new dataWrapper($response ? $response : "", "pi:Adapter:*", true);
    }

    public function getBootDrive(int $adapterId):dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        $response = $this->callCommand("-AdpBootDrive -Get -a" . $adapter);
        return new dataWrapper($response ? $response : "", "pi:Adapter*", true);
    }    

    public function getPatrolInfo(int $adapterId):dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        $response = $this->callCommand("-AdpPR -Info -a" . $adapter);
        return new dataWrapper($response);
    } 

    public function getTasksPhysicalDrives(int $adapterId, array $pdId):dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        $response = $this->callCommand("-PDRbld -ShowProg -PhysDrv [" . implode(",", $pdId) . "] -a" . $adapter);
        return new dataWrapper($response);        
    }    

    public function executeCommand(int $adapterId, string $cmd, array $options = null): dataWrapper {
        $adapter = ($adapterId < 0) ? "ALL" : $adapterId;
        
        $command = "-" . $cmd;

        if ($options) {
            foreach($options as $opt) $command .= " " . $opt;
        }

        $command .= " -a" . $adapter;
        
        $response = $this->callCommand($command);
        return new dataWrapper($response ? $response : "");   
    }

}

?>