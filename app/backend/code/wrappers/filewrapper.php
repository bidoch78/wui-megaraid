<?php

declare(strict_types=1);

namespace megaraid\wrappers;

use megaraid\wrappers\cliwrapper;
use megaraid\wrappers\dataWrapper;

class filewrapper extends cliwrapper {

    public function getlsblk(): string {

        $response = "{}";
        $file = __DIR__ . "/../../files/sata_lsblk.txt";
        if (is_file($file)) $response = file_get_contents($file);
        $this->addTrace($file, $response);
        return $response;

    }

    public function getsmartctl(string $ln): string {

        $response = "{}";
        $ln = explode("/", $ln);
        $ln = $ln[count($ln)-1];
        $file = __DIR__ . "/../../files/sata_smartctl_$ln.txt";
        if (is_file($file)) $response = file_get_contents($file);
        $this->addTrace($file, $response);
        return $response;

    }

    public function getVersion():dataWrapper {
        $file = __DIR__ . "/../../files/version.txt";
        $this->addTrace($file, file_get_contents($file));
        return new dataWrapper(file_get_contents($file));
    }

    public function getNbAdapter():dataWrapper {
        $file = __DIR__ . "/../../files/countcontroller.txt";
        $this->addTrace($file, file_get_contents($file));
        return new dataWrapper(file_get_contents($file));
    }

    public function getAdapterInfo(int $adapterId):dataWrapper {
        $id = ($adapterId == -1) ? "all" : $adapterId;
        $content = "Exit Code: 0x00";
        $file = __DIR__ . "/../../files/controllerinfo$id.txt";
        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content, "pi:adapter #*");
    }

    public function getVirtualDrives(int $adapterId):dataWrapper {
        $id = ($adapterId == -1) ? "all" : $adapterId;
        $content = "Exit Code: 0x00";
        $file = __DIR__ . "/../../files/virtualdrive$id.txt";
        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content, "pi:adapter*-- virtual drive*");
    }

    public function getPhysicalDrives(int $adapterId):dataWrapper {
        $id = ($adapterId == -1) ? "all" : $adapterId;
        $content = "Exit Code: 0x00";
        $file = __DIR__ . "/../../files/physicaldrive$id.txt";
        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content, "pi:adapter #*");
    } 
    
    public function getAdapterConfig(int $adapterId):dataWrapper {
        $id = ($adapterId == -1) ? "all" : $adapterId;
        $content = "Exit Code: 0x00";
        $file = __DIR__ . "/../../files/config$id.txt";
        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content, "pi:Adapter:*", true);
    }

    public function getBootDrive(int $adapterId):dataWrapper {
        $id = ($adapterId == -1) ? "all" : $adapterId;
        $content = "Exit Code: 0x00";
        $file = __DIR__ . "/../../files/bootdrive$id.txt";
        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content, "pi:Adapter*", true);
    }    

    public function getPatrolInfo(int $adapterId):dataWrapper {
        $id = ($adapterId == -1) ? "all" : $adapterId;
        $content = "Exit Code: 0x00";
        $file = __DIR__ . "/../../files/patrol$id.txt";
        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content, "pi:Adapter*", true);
    }

    public function getTasksPhysicalDrives(int $adapterId, array $pdId):dataWrapper {
        $content = "Exit Code: 0x00";
        $file = __DIR__ . "/../../files/command-pdrbld-showprog.txt";
        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content);
    }

    public function executeCommand(int $adapterId, string $cmd, array $options = null): dataWrapper {

        $id = ($adapterId == -1) ? "all" : $adapterId;
        $content = "Internal error\nCommand unknown $cmd\nExit Code: 0x01";
        
        $file = __DIR__ . "/../../files/command-" . $cmd;

        if ($options) {
            foreach($options as $opt) $file .= $opt;
        }

        $file = strtolower($file) . ".txt";

        if (is_file($file)) $content = file_get_contents($file);
        $this->addTrace($file, $content);
        return new dataWrapper($content, "pi:Adapter:*", true);        

    }

}

?>