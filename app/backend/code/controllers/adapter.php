<?php 

declare(strict_types=1);

namespace megaraid\controllers;

use megaraid\controllers\controller;
use megaraid\core;
use megaraid\wrappers\dataWrapper;
use megaraid\controllers\virtualdrive;
use megaraid\controllers\physicaldrive;
use megaraid\wrappers\dataStructureWrapper;
use megaraid\wrappers\DATE_FORMAT;

class adapter extends controller {

    static $ROC_TEMP_WARNING_CELSIUS = 80;
    static $ROC_TEMP_CRITICAL_CELSIUS = 90;

    public function count(): array {

        $wrapperData = core::getWrapper()->getNbAdapter();
        $data = [ 'count' => $wrapperData->getCode(), 'code' => $wrapperData->getCode() ];

        return $data;

    }

    public function info(): array {

        $adapterId = $this->request->getAnyParameter("id");

        $wrapperData = core::getWrapper()->getAdapterInfo((int)$adapterId);
    
        $info = [ "adapter" => $this->getAdapterInfo((int)$adapterId, $wrapperData), "code" => $wrapperData->getCode() ];

        return $info;

    }

    public function getAdapterInfo(int $adapterId, dataWrapper $wd = null) {

        if (!$wd) $wd = core::getWrapper()->getAdapterInfo($adapterId);

        $structure = dataStructureWrapper::root();

        $structure->addSection("versions", "pi:Versions", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("mfg", "pi:Mfg. Data", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("version_flash", "pi:Image Versions in Flash:", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("pending_flash", "pi:Pending Images in Flash", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("pci", "pi:PCI Info", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("hw", "pi:HW Configuration", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("settings", "pi:Settings", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("capabilities", "pi:Capabilities", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("status", "pi:Status", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("limitations", "pi:Limitations", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("devices_present", "pi:Device Present", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("supported_adapter_operations", "pi:Supported Adapter Operations", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("supported_vd_operations", "pi:Supported VD Operations", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("supported_pd_operations", "pi:Supported PD Operations", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("error_counters", "pi:Error Counters", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("cluster_information", "pi:Cluster Information", [ "removesectiondata" => true, "noarray" => true ]);
        $structure->addSection("default_settings", "pi:Default Settings", [ "removesectiondata" => true, "noarray" => true ]);

        $dataBySection = $wd->splitByStructure($structure, array("pi:*======*"));

        foreach($dataBySection as &$adapter) {
           dataWrapper::translateProperties($adapter);
           foreach($structure as $section) {
               if (isset($adapter[$section->getSectionName()])) {
                   dataWrapper::translateProperties($adapter[$section->getSectionName()]);
               }
           }
           self::convertData($adapter);
        }
        unset($adapter);

        return $dataBySection;

    }

    public function getAdapterOverview() {

        $adapterId = $this->request->getAnyParameter("id");

        $wrapperData = core::getWrapper()->getAdapterInfo((int)$adapterId);
        $info = [ "adapter" => $this->getAdapterInfo((int)$adapterId, $wrapperData), "code" => $wrapperData->getCode() ];
        

        return $info;

    } 

    public static function convertData(array &$adapter) {

        dataWrapper::convertData($adapter);

        //date
        if (isset($adapter["mfg"]["mfg_date"])) $adapter["mfg"]["mfg_date"] = dataWrapper::formatDateTime($adapter["mfg"]["mfg_date"], DATE_FORMAT::DATE_MMDDYYYY);
        if (isset($adapter["settings"]["current_time"])) $adapter["settings"]["current_time"] = dataWrapper::formatDateTime($adapter["settings"]["current_time"], DATE_FORMAT::DATETIME_THTMTS_MSLASHD_YEAR);

        //Adapter ID
        if (isset($adapter["properties"])) {
            foreach($adapter["properties"] as $prop) {
                $info = explode("#", $prop);
                if (count($info) == 2 && is_numeric($info[1])) {
                    $adapter["adapter_id"] = intval($info[1]);
                    if (is_array($adapter["properties"]) && count($adapter["properties"]) == 1) {
                        unset($adapter["properties"]);
                    }
                }    
            }
        }

        if (isset($adapter["hw"]["roc_temperature"])) {
            $val = explode(" ", trim($adapter["hw"]["roc_temperature"]));
            if (stripos($adapter["hw"]["roc_temperature"], "celsius") !== false) {
                $val = $val[0] . "C()";
            }
            else {
                $val = $val[0] . "F()";
            }
            $adapter["hw"]["roc_temperature"] = dataWrapper::convertTemperature($val);
        }

        if (isset($adapter["hw"]["controller_temperature"])) {
            $val = explode(" ", trim($adapter["hw"]["controller_temperature"]));
            if (stripos($adapter["hw"]["controller_temperature"], "celsius") !== false) {
                $val = $val[0] . "C()";
            }
            else {
                $val = $val[0] . "F()";
            }
            $adapter["hw"]["controller_temperature"] = dataWrapper::convertTemperature($val);
        }

        if (isset($adapter["capabilities"])) {
            if (isset($adapter["capabilities"]["raid_level_supported"])) {
                $adapter["capabilities"]["raid_level_supported"] = explode(", ", $adapter["capabilities"]["raid_level_supported"]);
            }
            if (isset($adapter["capabilities"]["supported_drives"])) {
                $adapter["capabilities"]["supported_drives"] = explode(", ", $adapter["capabilities"]["supported_drives"]);
            }
        }

        if (isset($adapter["default_settings"]["power_saving_option"])) {
            if (isset($adapter["default_settings"]["power_saving_option"]["value"])) {
                if (!isset($adapter["default_settings"]["power_saving_option"]["info"])) $adapter["default_settings"]["power_saving_option"]["info"] = [];
                $adapter["default_settings"]["power_saving_option"]["info"][] = $adapter["default_settings"]["power_saving_option"]["value"];
            }
            unset($adapter["default_settings"]["power_saving_option"]["value"]);
        }

        if (isset($adapter["pci"]["port"])) {
            if (isset($adapter["pci"]["port"]["value"]) && isset($adapter["pci"]["port"]["info"]) && $adapter["pci"]["port"]["value"]) {
                $adapter["pci"]["port"][$adapter["pci"]["port"]["value"]] = $adapter["pci"]["port"]["info"];
                unset($adapter["pci"]["port"]["value"], $adapter["pci"]["port"]["info"]);
            }
        }

        if (isset($adapter["pending_flash"])) {
            if (isset($adapter["pending_flash"]["properties"]) && is_array($adapter["pending_flash"]["properties"]) && count($adapter["pending_flash"]["properties"]) == 1) {
                $adapter["pending_flash"] = $adapter["pending_flash"]["properties"][0];
            }
        }

        //Health CHECK
        $adapter["_data"] = [ "healthcheck" => "ok", "healtherror" => [] ];

        if (isset($adapter["hw"]) && strcasecmp($adapter["hw"]["temperature_sensor_for_roc"], "present") == 0) {
            $temperature = (int)$adapter["hw"]["roc_temperature"]["celsius"];
            if ($temperature >= adapter::$ROC_TEMP_CRITICAL_CELSIUS) {
                $adapter["_data"]["healthcheck"] = "ko";
                $adapter["_data"]["healtherror"][] = [ "key" => "roc_temperature", "is" => "alert" ];
                $adapter["_data"]["temp"] = "danger";
            }
            elseif ($temperature >= adapter::$ROC_TEMP_WARNING_CELSIUS) {
                if ($adapter["_data"]["healthcheck"] == "ok") $adapter["_data"]["healthcheck"] = "warning"; 
                $adapter["_data"]["healtherror"][] = [ "key" => "roc_temperature", "is" => "warning" ];
                $adapter["_data"]["temp"] = "warning";
            }            
        }

        

    }

    public function getBootDrive() {
        
        $adapterId = $this->request->getAnyParameter("id");

        $wd = core::getWrapper()->getBootDrive((int)$adapterId);

        $adapterData = [];
        foreach($wd->getData() as $info) {
            
            if (!is_array($info) || count($info) == 0) continue;

            $adapterDT = $info[0];
            if (stripos($adapterDT, "adapter") !== 0) continue;

            $adapterDT = explode(":", trim(substr($adapterDT, 7)));
            if (!is_numeric($adapterDT[0]) || count($adapterDT) < 2) continue;

            $adapterId = intval($adapterDT[0]);
            $adapterDT = explode("-", $adapterDT[1]);
            if (count($adapterDT) !== 3) continue;

            $virtualdriveId = $adapterDT[1];
            $targetId = trim(substr($adapterDT[2], 0, strlen($adapterDT[2])-2));

            $posP = strpos($virtualdriveId, "(");
            if ($posP !== false) {
                $virtualdriveId = trim(substr($virtualdriveId, 0, $posP));
                if (strlen($virtualdriveId) > 0 && $virtualdriveId[0] == "#") $virtualdriveId = substr($virtualdriveId, 1);
            }

            if (!is_numeric($virtualdriveId) || !is_numeric($targetId)) continue;

            $virtualdriveId = intval($virtualdriveId);
            $targetId = intval($targetId);

            $adapterData[] = [ "adapter_id" => $adapterId, "virtual_drive_id" => $virtualdriveId, "target_id" => $targetId ];
        }

        $info = [ "adapters" => $adapterData, "code" => $wd->getCode() ];
        return $info;

    }    

    public function retrieveBootDriveFromResponse(array $info, $adapterId) {

        $bootdriveVDid = null;
        foreach($info["adapters"] as $adpt) {
            if ($adpt["adapter_id"] == $adapterId) { $bootdriveVDid = $adpt["virtual_drive_id"]; break; }
        }
        return $bootdriveVDid;

    }

    public function getConfig() {

        $adapterId = $this->request->getAnyParameter("id");

        $wd = core::getWrapper()->getAdapterConfig((int)$adapterId);

        $structure = dataStructureWrapper::root();

        $structure->addSection("disk_group", ["pi:disk group:*", "pi:spanned disk group:*"])
            ->addSection("span", "pi:span:*")
                ->addSection("virtual_drive", "pi:virtual drive:*")
                    ->addSection("physical_drive", "pi:physical disk:*");
        
        $dataBySection = $wd->splitByStructure($structure, 
                                                 array("pi:physical disk information:*",   
                                                       "pi:virtual drive information:*"));

        $bootdrives = $this->getBootDrive();
                                                                
        foreach($dataBySection as &$adapter) {

            if (!isset($adapter["disk_group"])) $adapter["disk_group"] = [];

            $adapters = $adapter["disk_group"];
            $adapter = $adapter["properties"];
            dataWrapper::convertData($adapter);

            if (!isset($adapter["adapter"])) continue;
            
            $adapter["adapter_id"] = $adapter["adapter"];
            unset($adapter["adapter"]);            
            $adapter["disk_group"] = $adapters;

            foreach($adapter["disk_group"] as &$diskgroup) {

                $spans = $diskgroup["span"];
                $diskgroup = $diskgroup["properties"];
                dataWrapper::convertData($diskgroup);
                $diskgroup["span"] = $spans;

                foreach($diskgroup["span"] as &$span) {

                    $vds = $span["virtual_drive"];
                    $span = $span["properties"];
                    dataWrapper::convertData($span);
                    $span["virtual_drive"] = $vds;

                    foreach($span["virtual_drive"] as &$vd) {
                          
                        $pds = $vd["physical_drive"];
                        $vd = $vd["properties"];
                        virtualDrive::convertData($vd, [ "bootdrive" => $this->retrieveBootDriveFromResponse($bootdrives, $adapter["adapter_id"]) ] );
                        $vd["physical_drive"] = $pds;
                        foreach($vd["physical_drive"] as &$pd) {
                            $pd = $pd["properties"];
                            physicalDrive::convertData($pd);
                        }
                        unset($pd);

                    }
                    unset($vd);

                }
                unset($span);

            }
            unset($diskgroup);


        }
        unset($adapter);

        $info = [ "adapters" => $dataBySection, "code" => $wd->getCode() ];
        return $info;

    }

}

?>