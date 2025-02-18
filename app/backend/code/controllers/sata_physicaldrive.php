<?php 

declare(strict_types=1);

namespace megaraid\controllers;

use megaraid\wrappers\dataWrapper;
use megaraid\controllers\controller;
use megaraid\core;

class sata_PhysicalDrive extends controller {

    public function isEnabled(): bool {
        return core::env("checksatadevices","false") == "true";
    }

    private function dataFromFile(): bool {
        return core::env("megaraid_wrapper_from","megaraid") !== "megaraid";
    }

    private function getDrives(): array {

        $devices = array();

        $response = core::getWrapper()->getlsblk();
        $response = json_decode($response, true);

        foreach($response["blockdevices"] as $disk) {
            
            //Exclude SCSI DISK
            if (!strcasecmp($disk["type"], "disk")) {

              $dsk = [];

              $dsk["serial_number"] = $disk["serial"];
              $dsk["model"] = $disk["model"];
              $dsk["device_key"] = $disk["serial"];
              $dsk["logicalname"] = $disk["path"];
              $dsk["wwn"] = $disk["wwn"];
              $dsk["firmware_state"] = "Online, Spun Up";
              $dsk["raw_size"] = [ "hr" => $disk["size"], "bytes" => 0 ];
              $dsk["drive_temperature"] = [ "celsius" => 0, "fahrenheit" => 0 ];
              $dsk["vendor"] = "N/A";
              $dsk["slot_key"] = $disk["path"];

              if ($dsk["wwn"] !== null && substr($dsk["wwn"], 0, 2) == "0x") $dsk["wwn"] = substr($dsk["wwn"], 2);

              //Default values
              $dsk["_data"] = array("state" => "online", "spun" => "on", "healthcheck" => "ok","healtherror" => [], "temp" => "ok");

              $devices[] = $dsk;

            }

        }

        $returnDevices = [];
        foreach($devices as &$disk) {

          $response = core::getWrapper()->getsmartctl($disk["logicalname"]);
          $response = json_decode($response, true);

          if (!isset($response["device"]["protocol"]) || $response["device"]["protocol"] != "ATA") continue;

          $disk["model"] = $response["model_name"];
          $disk["vendor"] = $response["model_family"];
          $disk["firmware"] = $response["firmware_version"];
          $disk["raw_size"] = array("hr" => dataWrapper::convertBytesToHumanReadable($response["user_capacity"]["bytes"]), "bytes" => $response["user_capacity"]["bytes"]);

          if (isset($response["wwn"]) && isset($response["wwn"]["naa"]) && isset($response["wwn"]["oui"]) && isset($response["wwn"]["id"])  ) {
            $disk["wwn"] = $response["wwn"]["naa"] . sprintf("%06x%09x", $response["wwn"]["oui"], $response["wwn"]["id"]);
          }

          //SMART CAPABILITIES
          $disk["_data"]["smart"] = array("available" => false, "enabled" => false);
          if (isset($response["smart_support"])) {
            $disk["_data"]["smart"] = $response["smart_support"];
          }

          if (isset($response["smart_status"]) && $response["smart_status"]["passed"] !== true) {
            $disk["_data"]["healthcheck"] = "ko";
            $disk["_data"]["healtherror"][] = [ "key" => "smart_status", "is" => "alert" ];            
          }

          if (isset($response["ata_smart_attributes"]) && isset($response["ata_smart_attributes"]["table"])) {
            foreach($response["ata_smart_attributes"]["table"] as $att) {
              if ($att["id"] == 194) {
                $temp = $att["raw"]["string"];
                $temp = explode(" ", $temp);
                if (is_numeric($temp[0])) {
                  $temp = $temp[0];
                  $disk["drive_temperature"] = dataWrapper::convertTemperature($temp, [ "iscelcius" => true ]);
                }
              }
            }
          }

          $returnDevices[] = $disk;

        }

        unset($disk);

     
        /*
        smartctl 7.3 2022-02-28 r5338 [x86_64-linux-6.8.12-2-pve] (local build)
Copyright (C) 2002-22, Bruce Allen, Christian Franke, www.smartmontools.org

=== START OF INFORMATION SECTION ===
Model Family:     Western Digital Blue
Device Model:     WDC WD2500AAKX-753CA1
Serial Number:    WD-WMAYW0460548
LU WWN Device Id: 5 0014ee 0ada5aad6
Firmware Version: 19.01H19
User Capacity:    250,059,350,016 bytes [250 GB]
Sector Size:      512 bytes logical/physical
Device is:        In smartctl database 7.3/5319
ATA Version is:   ATA8-ACS (minor revision not indicated)
SATA Version is:  SATA 3.0, 6.0 Gb/s (current: 6.0 Gb/s)
Local Time is:    Tue Oct  8 22:50:31 2024 CEST
SMART support is: Available - device has SMART capability.
SMART support is: Enabled


----
---


=== START OF INFORMATION SECTION ===
Vendor:               AVAGO
Product:              MR9361-8i
Revision:             4.68
Compliance:           SPC-3
User Capacity:        399,532,621,824 bytes [399 GB]
Logical block size:   512 bytes
Logical Unit id:      0x600605b00c8819b02d6c113816a11547
Serial number:        004715a11638116c2db019880cb00506
Device type:          disk
Local Time is:        Tue Oct  8 22:50:47 2024 CEST
SMART support is:     Unavailable - device lacks SMART capability.

        if SMART support is == "Available" then
            if 2eme SMART support != "Enabled" then
                //Activer SMART avec  smartctl --smart=on --offlineauto=on --saveauto=on /dev/sda
            end if
        end if

        if (smartctl) 
            override = $dsk["model"] = Device Model
            override = $dsk["firmware"] = Firmware Version
            $dsk["wnn] = LU WWN Device Id

        si tout va bien lancé

        smartctl --all /dev/sda

        si SMART overall-health self-assessment test result: PASSED alors tout est ok

        after => Vendor Specific SMART Attributes with Thresholds:
        194 Temperature_Celsius     0x0022   064   055   000    Old_age   Always       -       36 (Min/Max 15/45)

            "drive_temperature": {
                "celsius": 65,
                "fahrenheit": 149
            },

        si  Rotation Rate:    Solid State Device => SSD
            media_type = "Solid State Device"
        sinon
            media_type = "Hard Disk Device"

            voir resultat => https://debian-facile.org/doc:systeme:smartmontools


            Pour lancer des tests
            smartctl --test=long /dev/sda
            smartctl --test=short /dev/sda

            sudo smartctl -t <short|long|conveyance|select> /dev/sdc


            pour savoir si un test est en cours

            Self-test execution status:      ( 246) Self-test routine in progress...
                                        60% of test remaining.


        */
        




        return $returnDevices;

    }

    public function getPhysicalDrives(): array {

        $data = ["physicaldrives" => $this->getDrives() ];
        $data["code"] = 0;
        return $data;

    }

    // static $HDD_TEMP_WARNING_CELSIUS = 40;
    // static $HDD_TEMP_CRITICAL_CELSIUS = 45;
    // static $HDD_SAS_TEMP_WARNING_CELSIUS = 50;
    // static $HDD_SAS_TEMP_CRITICAL_CELSIUS = 55;
    // static $SSD_TEMP_WARNING_CELSIUS = 55;
    // static $SSD_TEMP_CRITICAL_CELSIUS = 65;

    // public function get() {
        
    //     $adapterId = $this->request->getAnyParameter("id");
    //     return $this->getAdapterPhysicalDrive((int)$adapterId);

    // }

    // public function executeCommand() {

    //     $adapterId = (int)$this->request->getAnyParameter("id");
    //     $physicalDriveId = $this->request->getAnyParameter("pid");
    //     $command = $this->request->getAnyParameter("command");

    //     $commandOptions = [];

    //     switch($command) {
    //         case "locate":
    //             $command = "PDLocate";
    //             $commandOptions[] = ($this->request->getAnyParameter("options") == "start") ? "-Start" : "-Stop";
    //             break;
    //         case "makegood":
    //             $command = "PDMakeGood";
    //             break;
    //         case "prprmv":
    //             if ($this->request->getAnyParameter("options") == "undo") $commandOptions[] = "-Undo";
    //             $command = "PDPrpRmv";
    //             break;
    //         case "markmissing":
    //             $command = "PDMarkMissing";
    //             break;
    //         default:
    //             throw new megaraidException("Unkown command `" + $command + "`");    
    //     }

    //     $commandOptions[] = "-PhysDrv[" . $physicalDriveId . "]";

    //     $wrapperData = core::getWrapper()->executeCommand($adapterId, $command, $commandOptions);

    //     $info = [ "return" => $wrapperData->getData(), "code" => $wrapperData->getCode() ];

    //     return $info;            

    // }

    // public function getRebuildProgression(array $dIds): array {

    //     if (!count($dIds)) return [];

    //     $wrapperData = core::getWrapper()->getTasksPhysicalDrives(-1, $dIds);

    //     $wData = $wrapperData->getData();

    //     $return = [];
    //     if (count($wData) == 1) {

    //         foreach($wData[0] as $info) {

    //             if (stripos($info, "rebuild") === 0) {
    //                 $infoDetail = explode(",", $info);
    //                 $enclosure = null;
    //                 $slot = null;
    //                 $completed = null;
    //                 if (count($infoDetail) == 2) {
    //                     $findEnclosure = stripos($infoDetail[0], "enclosure");
    //                     if ($findEnclosure !== false) $enclosure = trim(substr($infoDetail[0], $findEnclosure + 9));
    //                     $infoDetail[1] = explode(" ", $infoDetail[1]);
    //                     for($ip = 0; $ip < count($infoDetail[1]);$ip++) {
    //                         switch(strtolower($infoDetail[1][$ip])) {
    //                             case "slot":
    //                                 $ip++; $slot = $infoDetail[1][$ip];
    //                                 break;
    //                             case "completed": 
    //                                 $ip++; $completed = $infoDetail[1][$ip];
    //                                 break;
    //                         }
    //                     }
    //                 }
    //                 if ($enclosure !== null && $slot !== null) {
    //                     $dKey = $enclosure . ":" . $slot;
    //                     if (strlen($completed) > 0 && $completed[strlen($completed)-1] == "%") $completed = substr($completed, 0, strlen($completed) - 1);
    //                     if (!isset($return[$dKey])) $return[$dKey] = intval($completed);
    //                 }

    //             }

    //         }

    //     }

    //     return $return;
        
    // }

    // public function getAdapterPhysicalDrive(int $adapterId): array {

    //     $wrapperData = core::getWrapper()->getPhysicalDrives($adapterId);    

    //     $structure = dataStructureWrapper::root();
    //     $structure->addSection("physicaldrives", "pi:Enclosure Device ID:*");

    //     $dataBySection = $wrapperData->splitByStructure($structure, options: [
    //         'convert' => function(string $initial, mixed &$convertData) { //Need to keep inquiry_data data without applying trim
    //             if (is_array($convertData) && count($convertData) === 2 && strcasecmp($convertData[0], "inquiry_data") === 0) {
    //                 $info = explode(":", $initial);
    //                 $convertData[1] = substr($info[1], 1);
    //             }
    //         }
    //     ]);

    //     $physicalDriveIds = [];
    //     foreach($dataBySection as &$adapter) {

    //         if (isset($adapter["properties"]) && isset($adapter["properties"]["param"])) {
    //             if (key_exists("value", $adapter["properties"]["param"]) && !$adapter["properties"]["param"]["value"]) {
    //                 foreach($adapter["properties"]["param"]["info"] as $param) {
    //                     $adapterData = explode("#", $param);
    //                     if (count($adapterData) == 2 && is_numeric($adapterData[1])) {
    //                         $adapter["adapter_id"] = intval($adapterData[1]);              
    //                     }
    //                 }
    //             }
    //             unset($adapter["properties"]);            
    //         }

    //         foreach($adapter["physicaldrives"] as &$pd) {
    //             $pd = $pd["properties"];
    //             self::convertData($pd);
    //             $physicalDriveIds[] = $pd["device_key"]; 
    //         }
    //         unset($pd);

    //     }
    //     unset($adapter);
    
    //     $rebuildPrg = $this->getRebuildProgression($physicalDriveIds);
    //     foreach($rebuildPrg as $vKey => $prg) {

    //         $find = false;
    //         foreach($dataBySection as &$adapter) {
    //             foreach($adapter["physicaldrives"] as &$pd) {
    //                 if ($pd["device_key"] == $vKey) {
    //                     $pd["_data"]["rebuildprg"] = $prg;
    //                     $find = true;
    //                     break;
    //                 }
    //             }
    //             unset($pd);
    //             if ($find) break;
    //         }
    //         unset($adapter);

    //     }
        

    //     $info = [ "adapters" => $dataBySection, "code" => $wrapperData->getCode() ];
    //     return $info;    

    // }

    // public static function convertData(array &$pd) {

    //     dataWrapper::convertData($pd);
        
    //     if (isset($pd["slot_number"]) && is_array($pd["slot_number"])) {
    //         $slot_number = $pd["slot_number"]["value"];
    //         $driveInfo = explode(":", $pd["slot_number"]["info"][0]);
    //         $diskgroup = trim(explode(",", $driveInfo[2])[0]);
    //         $diskspan = trim(explode(",", $driveInfo[3])[0]);
    //         $diskarm = trim($driveInfo[4]);
    //         $pd["slot_number"] = $slot_number;
    //         $pd["diskgroup"] = is_numeric($diskgroup) ? intval($diskgroup): "##";
    //         $pd["diskspan"] = is_numeric($diskspan) ? intval($diskspan): "##";
    //         $pd["diskarm"] = is_numeric($diskarm) ? intval($diskarm): "##";
    //         $pd["drive_is_assigned"] = true;
    //     }
    //     else {
    //         $pd["drive_is_assigned"] = false;
    //     }
        
    //     $pd["device_key"] = $pd["enclosure_device_id"] . ":" . $pd["slot_number"];

    //     //Temp
    //     if (isset($pd["drive_temperature"])) {
    //         $pd["drive_temperature"] = dataWrapper::convertTemperature($pd["drive_temperature"]);
    //     }

    //     if (isset($pd["raw_size"]) && is_numeric($pd["sector_size"])) {
    //         $findsectors = strpos($pd["raw_size"], "[");
    //         if ($findsectors != false) {
    //             $sectors = strtolower(trim(substr($pd["raw_size"], $findsectors)));
    //             $sectors = substr($sectors, 1, strlen($sectors)-2);
    //             $sectors = explode(" ", $sectors);
    //             if (count($sectors) == 2) {
    //                 $sizeB = hexdec($sectors[0]) * $pd["sector_size"];
    //                 $pd["raw_size"] = [ "hr" => dataWrapper::convertBytesToHumanReadable($sizeB) , "bytes" => $sizeB ];
    //             }
    //         }
    //     }

    //     if (isset($pd["inquiry_data"])) {

    //         $rid = self::readInquiryData($pd["inquiry_data"]);

    //         $pd["serial_number"] = $rid["serial_number"];
    //         $pd["model"] = $rid["model"];
    //         $pd["brand"] = $rid["brand"];
    //         $pd["firmware"] = $rid["firmware"];

    //     }

    //     $pd["_data"] = [ "state" => "", "spun" => "", "healthcheck" => "ok", "healtherror" => [] ];
    //     if (isset($pd["firmware_state"])) {
    //         $fs = explode(",", $pd["firmware_state"]);
    //         $pd['_data']["state"] = strtolower(trim($fs[0]));
    //         $pd["_data"]["spun"] = stripos($pd["firmware_state"], "off") ? "off" : "on";
    //     }
        
    //     //Health CHECK
    //     if (isset($pd["media_error_count"]) && $pd["media_error_count"] > 0) {
    //         $pd["_data"]["healthcheck"] = "ko"; 
    //         $pd["_data"]["healtherror"][] = [ "key" => "media_error_count", "is" => "alert" ];
    //     }
    //     if (isset($pd["other_error_count"]) && $pd["other_error_count"] > 0) { 
    //         $pd["_data"]["healthcheck"] = "ko"; 
    //         $pd["_data"]["healtherror"][] = [ "key" => "media_error_count", "is" => "alert" ];
    //     }
    //     if (isset($pd["predictive_failure_count"]) && $pd["predictive_failure_count"] > 0) {
    //         $pd["_data"]["healthcheck"] = "ko"; 
    //         $pd["_data"]["healtherror"][] = [ "key" => "predictive_failure_count", "is" => "alert" ];
    //     }
    //     if (isset($pd["last_predictive_failure_event_seq_number"]) && $pd["last_predictive_failure_event_seq_number"] > 0) {
    //         $pd["_data"]["healthcheck"] = "ko"; 
    //         $pd["_data"]["healtherror"][] = [ "key" => "last_predictive_failure_event_seq_number", "is" => "alert" ];
    //     }
    //     if (isset($pd["drive_has_flagged_a_smart_alert"]) && $pd["drive_has_flagged_a_smart_alert"] === true) {
    //         $pd["_data"]["healthcheck"] = "ko"; 
    //         $pd["_data"]["healtherror"][] = [ "key" => "drive_has_flagged_a_smart_alert", "is" => "alert" ];
    //     }
    //     if (isset($pd["shield_counter"]) && $pd["shield_counter"] > 0) {
    //         $pd["_data"]["healthcheck"] = "ko"; 
    //         $pd["_data"]["healtherror"][] = [ "key" => "shield_counter", "is" => "alert" ];
    //     }        
        
    //     if (isset($pd["drive_temperature"]["celsius"])) {
    //         $pd["_data"]["temp"] = "ok";
    //         $temperature = (int)$pd["drive_temperature"]["celsius"];
    //         if ($temperature > 0) {
    //             if ($pd["media_type"] == "Solid State Device") {
    //                 //SSD
    //                 if ($temperature >= physicalDrive::$SSD_TEMP_CRITICAL_CELSIUS) {
    //                     $pd["_data"]["healthcheck"] = "ko"; 
    //                     $pd["_data"]["healtherror"][] = [ "key" => "drive_temperature", "is" => "alert" ];
    //                     $pd["_data"]["temp"] = "danger";
    //                 }
    //                 elseif ($temperature >= physicalDrive::$SSD_TEMP_WARNING_CELSIUS) {
    //                     if ($pd["_data"]["healthcheck"] == "ok") $pd["_data"]["healthcheck"] = "warning"; 
    //                     $pd["_data"]["healtherror"][] = [ "key" => "drive_temperature", "is" => "warning" ];
    //                     $pd["_data"]["temp"] = "warning";
    //                 }
    //             }
    //             else {
    //                 //HDD
    //                 $critial_temp = ($pd["pd_type"] == "SAS") ? physicalDrive::$HDD_SAS_TEMP_CRITICAL_CELSIUS : physicalDrive::$HDD_TEMP_CRITICAL_CELSIUS;
    //                 $warning_temp = ($pd["pd_type"] == "SAS") ? physicalDrive::$HDD_SAS_TEMP_WARNING_CELSIUS : physicalDrive::$HDD_TEMP_WARNING_CELSIUS;
    //                 if ($temperature >= $critial_temp) {
    //                     $pd["_data"]["healthcheck"] = "ko"; 
    //                     $pd["_data"]["healtherror"][] = [ "key" => "drive_temperature", "is" => "alert" ];
    //                     $pd["_data"]["temp"] = "danger";
    //                 }        
    //                 elseif ($temperature >= $warning_temp) {
    //                     if ($pd["_data"]["healthcheck"] == "ok") $pd["_data"]["healthcheck"] = "warning"; 
    //                     $pd["_data"]["healtherror"][] = [ "key" => "drive_temperature", "is" => "warning" ];                        
    //                     $pd["_data"]["temp"] = "warning";
    //                 }
    //             }
    //         }
    //     }

    // }   

    // public static function readInquiryData(string $inquiry_data): array {

    //     $firmware = "";
    //     $model = "";
    //     $sn = "";
    //     $brand = "NA";

    //     $getValues = function(string $i): array {
    //         $values = array();
    //         foreach(explode(" ", $i) as $value) {
    //             $value = trim($value);
    //             if ($value) $values[] = $value;
    //         }
    //         return $values;
    //     };

    //     if (stripos($inquiry_data, "seagate") === 0) {
    //         $values = $getValues($inquiry_data);
    //         if (count($values) > 1) {
    //             $model = substr($values[1], 0, 16);
    //             $firmware = substr($values[1], 16,4);
    //             $sn = substr($values[1], 20);
    //         }
    //         $brand = "SEAGATE";
    //     }
    //     elseif (stripos($inquiry_data, "pliant") === 0) {
    //         $values = $getValues($inquiry_data);
    //         if (count($values) > 3) {
    //             $model = $values[1];
    //             $sn = $values[2];
    //             $firmware = $values[3];
    //         }
    //         $brand = "PLIANT";
    //     }
    //     elseif (stripos($inquiry_data, "toshiba") === 0) {
    //         $values = $getValues($inquiry_data);
    //         if (count($values) > 2) {
    //             $model = $values[1];
    //             $firmware = substr($values[2], 0, 4);
    //             $sn = substr($values[2], 4);
    //             //$sn = $values[2];
    //             //$firmware = $values[3];
    //         }
    //         $brand = "TOSHIBA";
    //     }        
    //     else {
    //         //[SN:20char][MODEL:40][FW:8]
    //         $sn = trim(substr($inquiry_data,0,20));
    //         $model = trim(substr($inquiry_data,20,40));
    //         $firmware = trim(substr($inquiry_data,60));
    //         switch (strtolower(substr($model, 0, 2))) {
    //             case "st": $brand = "SEAGATE"; break;
    //             case "ct": $brand = "CRUCIAL"; break;
    //             case "wd": $brand = "WESTERN DIGITAL"; break;
    //             case "mk": $brand = "HP"; break;
    //             default:
    //                 if (stripos($model, "hitachi") !== false) {
    //                     $brand = "HITACHI";
    //                 }
    //                 if (stripos($model, "sandisk") !== false) {
    //                     $brand = "SANDISK";
    //                 }
    //         }
    //     }        

    //     return [ "serial_number" => $sn, "model" => $model, "firmware" => $firmware, 'brand' => $brand ];

    // }

}

?>