<?php 

declare(strict_types=1);

namespace megaraid\controllers;

use megaraid\controllers\controller;
use megaraid\core;
use megaraid\wrappers\dataWrapper;
use megaraid\wrappers\dataStructureWrapper;

class virtualDrive extends controller {

    public function get() {

        $adapterId = $this->request->getAnyParameter("id");
        return $this->getAdapterVirtualDrive((int)$adapterId);

    }

    public static function getVirtualDriveID(array $info) {

        $data = [ "virtual_drive_id" => "##", "target_id" => "##" ];

        foreach($info as $dt) {
            $dInfo = (dataWrapper::searchInString($dt, "virtual drive"));
            if ($dInfo) {
                $firstParenthesis = strpos($dInfo, "(");
                $lastParentthesis = strpos($dInfo, ")", $firstParenthesis);
                if ($firstParenthesis > 0 && $lastParentthesis > $firstParenthesis) {
                    $virtualDriveId = substr($dInfo, 0, $firstParenthesis);
                    $targetString = substr($dInfo, $firstParenthesis + 1, $lastParentthesis-$firstParenthesis-1);
                    $targetString = explode(":", $targetString);
                    if (count($targetString) == 2) {
                        $virtualDriveId = trim($virtualDriveId);
                        $targetString = trim($targetString[1]);
                        if (is_numeric($virtualDriveId) && is_numeric($targetString)) {
                            $data["virtual_drive_id"] = intval($virtualDriveId);
                            $data["target_id"] = intval($targetString);
                        }
                    }
                }
            }
        }

        return $data;

    }

    public function getAdapterVirtualDrive(int $adapterId): array {

        $wrapperData = core::getWrapper()->getVirtualDrives($adapterId);    

        $structure = dataStructureWrapper::root();
        $structure->addSection("virtualdrives", "pi:Virtual Drive:*");

        $dataBySection = $wrapperData->splitByStructure($structure);

        $adapterObj = new adapter($this->request); 
        $bootdrives = $adapterObj->getBootDrive();

        foreach($dataBySection as &$adapter) {

            $adapterData = explode("_", array_keys($adapter["properties"])[0]);
            $adapter["adapter_id"] = $adapterData[1];
            unset($adapter["properties"]);

            foreach($adapter["virtualdrives"] as &$vd) {
                $vd = $vd["properties"];
                self::convertData($vd, [ "bootdrive" => $adapterObj->retrieveBootDriveFromResponse($bootdrives, $adapter["adapter_id"]) ]);
            }
            unset($vd);

        }
        unset($adapter);

        $info = [ "adapters" => $dataBySection, "code" => $wrapperData->getCode() ];

        return $info; 

    }

    public static function convertData(array &$vd, array $options = null) {

        $primary = null; $secondary = null; $levelqualifier = null;
        foreach(explode(",", $vd["raid_level"]) as $info) {
            $spInfo = explode("-", $info);
            if (count($spInfo) == 2) {
                $spInfo[0] = strtolower(trim($spInfo[0]));
                if (strpos($spInfo[0], "primary") !== false) {
                    $primary = trim($spInfo[1]);
                }
                elseif (strpos($spInfo[0], "secondary") !== false) {
                    $secondary = trim($spInfo[1]);
                }
                elseif (strpos($spInfo[0], "raid level qualifier") !== false) {
                    $levelqualifier = trim($spInfo[1]);
                }
            }
        }

        $vd["raid"] = $primary . ($vd["span_depth"] == "1" ? "" : "0");

        if (isset($vd["param"])) {
            if (key_exists("value", $vd["param"]) && !$vd["param"]["value"]) {
                $vdID = self::getVirtualDriveID($vd["param"]["info"]);
                $vd["virtual_drive_id"] = $vdID["virtual_drive_id"];
                $vd["target_id"] = $vdID["target_id"];
            }
        }
        unset($vd["param"]);

        $vd["_data"] = [ 
            "boot" => false,
            "state_optimal" => (strcasecmp("Optimal", $vd["state"]) == 0) ? true : false
        ];

        if ($options && isset($options["bootdrive"])) {
            if ($vd["virtual_drive_id"] == $options["bootdrive"]) $vd["_data"]["boot"] = true;
        }

        if (isset($vd["background_initialization"])) $vd["_data"]["bginitprg"] = self::getTaskProgression($vd["background_initialization"]);
        if (isset($vd["foreground_initialization"])) $vd["_data"]["fginitprg"] = self::getTaskProgression($vd["foreground_initialization"]);
        if (isset($vd["check_consistency"])) $vd["_data"]["ccprg"] = self::getTaskProgression($vd["check_consistency"]);

        dataWrapper::convertData($vd);

    }

    private static function getTaskProgression(string $str): int {

        $prg = 0;

        $str = explode(",", $str);
        $str = $str[0];

        $str = explode(" ", $str);
        if (count($str) == 2) {
            $str = $str[1];
            if ($str[strlen($str)-1] == "%") {
                $prg = intval(substr($str, 0, strlen($str)-1));
            }
        }

        return $prg;

    }

}

?>