<?php 

declare(strict_types=1);

namespace megaraid\controllers;

use megaraid\controllers\controller;
use megaraid\core;
use megaraid\wrappers\dataWrapper;
use megaraid\wrappers\dataStructureWrapper;

class patrol extends controller {

    public function info() {

        $adapterId = $this->request->getAnyParameter("id");

        $wd = core::getWrapper()->getPatrolInfo((int)$adapterId);

        $structure = dataStructureWrapper::root();
        $structure->addSection("patrol", "pi:Adapter *: Patrol Read Information:");

        $dataBySection = $wd->splitByStructure($structure);
        $adapterData = [];

        foreach($dataBySection as $adapter) {

            if (!isset($adapter["patrol"])) continue;

            $adapter = $adapter["patrol"][0]["properties"];

            $adapterId = null;
            if (isset($adapter["param"])) {
                if (key_exists("value", $adapter["param"]) && !$adapter["param"]["value"]) {
                    foreach($adapter["param"]["info"] as $param) {
                        $adapterInfo = explode(":", $param);
                        $adapterInfo = explode(" ", trim($adapterInfo[0]));
                        if (count($adapterInfo) == 2 && is_numeric($adapterInfo[1])) {
                            $adapterId = intval($adapterInfo[1]);              
                        }
                    }
                }
            }

            if ($adapterId === null) continue;

            unset($adapter["param"]);

            $adapter["adapter_id"] = $adapterId;

            self::convertData($adapter);

            $adapterData[] = $adapter;

        }
        
        $info = [ "adapters" => $adapterData, "code" => $wd->getCode() ];
        return $info;

    }

    public static function convertData(array &$p) {

        dataWrapper::convertData($p);

    }

}

?>