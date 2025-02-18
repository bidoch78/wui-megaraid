<?php

declare(strict_types=1);

namespace megaraid\wrappers;

use megaraid\wrappers\dataStructureWrapper;

enum DATE_FORMAT: int {
    case DATE_MMDDYYYY = 1; // "05/24/16"
    case DATETIME_THTMTS_MSLASHD_YEAR = 2; // "16:21:11 1/10, 2024"
    case DATETIME_DDMMYYYY_THTMTS = 3; //02/06/2024, 21:00:00
    case DATE_DDDMMSEPYYY = 4; //Dec 16, 2013
}

class dataWrapper {

    const TYPE_NUMBER = 1;
    const TYPE_STRING = 2;

    private ?string $_dataString = null;
    private ?array $_data = null;
    private int $_code = -1;

    public function __construct(string $data, string $splitOn = null, bool $removeEqual = false) {

        //Clean data
        $this->_data = [];
        $this->_data[] = [];

        $splitFct = $splitOn ? self::getPatternFunction($splitOn) : null;

        $dataIndex = 0;
        $arrayData = explode("\n", $data);
        foreach($arrayData as $dt) {

            $dt = trim($dt); $len = strlen($dt);

            //skip if blank
            if (!$len) continue;
            //skip if ========
            if ($removeEqual && substr_count($dt, "=") == $len) continue;
            //Skipt exit code
            $exitCode = dataWrapper::searchInString($dt, "Exit Code", self::TYPE_NUMBER);
            if ($exitCode !== null) { $this->_code = $exitCode; continue; }

            if ($splitFct) {
                if ($splitFct($dt)) {
                    //split the data
                    if (count($this->_data[$dataIndex])) { $this->_data[] = []; $dataIndex++; }
                }
                $this->_data[$dataIndex][] = $dt;
            }
            else {
                $this->_data[$dataIndex][] = $dt;
            }

        }

    }

    public function getData(): null|array { return $this->_data; }

    public function getCode(): int { return $this->_code; }

    public static function searchInString(string $data, string $key, $type = self::TYPE_STRING, $default = null):mixed {

        $value = null;

        $find = stripos($data, $key);
        if ($find !== false) {
            $txt = substr($data, $find+strlen($key));
            $find = stripos($txt, ":");
            if ($find !== false) {
                $value = trim(substr($txt, $find+1));
                //remove dot at the end if present
                if (strlen($value) && $value[strlen($value)-1] == ".") $value = trim(substr($value, 0, strlen($value)- 1));
            }
        }

        if ($value === null) $value = $default;

        if ($value && $type == self::TYPE_NUMBER) {
            if (stripos($value, "0x") === 0) {
                $value = hexdec($value);
            }
            else if (is_numeric($value)) {
                $value = floatval($value);
            }
        }

        return $value;

    }

    public static function convertNameToKey(string $value): string {
        $value = strtolower($value);
        $value = str_replace([".", "'"], "", $value);
        $value = str_replace(" ", "_", $value);
        return $value;
    }

    public function splitByStructure(dataStructureWrapper $structure, array $exclude = [], string $split = ":", array $options = null) : array {

        $returnData = [];

        foreach($exclude as &$pattern) {
            $fct = $this->getPatternFunction($pattern);
            if (!$fct) throw new Exception("splitBySection pattern unknown");
            $pattern = $fct;
        }
        unset($pattern);
        
        foreach($this->_data as $array) {
            
            $structure->reset();

            foreach($array as $dt) {

                $zap = false;
                foreach($exclude as $pattern) { if ($pattern($dt)) { $zap = true; break; } }
                if (!$zap) $structure->analyzeData($dt);

            }

            $returnData[] = $structure->getArray($options);

        }

        return $returnData;

    }

    // /*
    //     [ name:"section1", data:[ [ 'key', 'value' ], string ]  ]

    // */

    public static function parseDataBySection(array $data, string $delimiter = "================", string $split = ":", array $options = null): array {

        $sections = [];

        $checkSpecial = function($key):bool {

            if (stripos($key, "time") !== false) return true;
            if (stripos($key, "preboot_cli_version") !== false) return true;
            
            return false;
        };

        //Start by the end
        $count = count($data);
        $currentSection = [];
        $findDelimiter = false;
        for($i = $count-1; $i >= 0; $i--) {

            $line = trim($data[$i]);
            if (!$line) continue;

            if (strcasecmp($line, $delimiter) == 0) {
                $findDelimiter = true;
                continue;
            }
            
            if ($findDelimiter) {
                $line = self::convertNameToKey($line);
                array_unshift($sections, [ "name" => $line, "data" => $currentSection]);
                $currentSection = []; //Reset
                $findDelimiter = false;
                continue;
            }

            //Split info
            $info = explode($split, $line);

            if (count($info) == 2) {
                $info[0] = self::convertNameToKey(trim($info[0]));
                $info[1] = trim($info[1]);
            }
            else if (count($info) > 2) {
                //Perhaps date time information
                $dataKey = self::convertNameToKey(trim($info[0]));
                //very basic just check if time is present in the key ;-)
                //var_dump($dataKey . " " . $checkSpecial($dataKey));
                if ($checkSpecial($dataKey)) {
                    array_shift($info);
                    $info = [ $dataKey, trim(implode(":", $info)) ];
                }
                else {
                    $info = $line;    
                }
            }
            else {
                $info = $line;
            }

            if ($options && isset($options["convert"]) && is_callable($options["convert"])) {
                $options["convert"]($line, $info);
            }

            if ($info[0] == "exit_code") continue;
            
            array_unshift($currentSection, $info);

        }

        if (count($currentSection)) array_unshift($sections, [ "name" => null, "data" => $currentSection]);

        return $sections;

    }
    
    public static function translateProperties(array &$data) : void {
        
        if (isset($data["properties"])) {

            if (count($data["properties"]) == 1 && isset($data["properties"]["param"]) && key_exists("value", $data["properties"]["param"]) && $data["properties"]["param"]["value"] === null) {
                $data["properties"] = $data["properties"]["param"]["info"];
                return;
            }

            foreach($data["properties"] as $propName => $propValue) {

                if (is_array($propValue) && count($propValue) == 2 && key_exists("value", $propValue) && key_exists("info", $propValue)) {
                    if (!$propValue["value"]) {
                        $propValue = $propValue["info"];
                    }
                    else {

                    }
                }

                if (isset($data[$propName])) throw new \Exception("key `$propName` already exists in array");
                $data[$propName] = $propValue;

            }

            unset($data["properties"]);

        }

    }

    public static function translateSectionDataToAssocArray(array $pdata): array {

        $returnData = [];

        /*

            [ "product_name, "AEZREZRZERZEREZ" ]
            product_name => "AEZREZRZERZEREZ"

            [ "port", "Address]
            "0  3423243
            "1  123213

            Translate to
            "port" => [ "value" => "Address", "detail" => [ "0  3423243", "1  123213" ] ]

            Attention section "pending_images_in_flash"

        */

        $unknownSectionCpt = 0;
        foreach($pdata as $section) {

            $sectionData = [];
            $lastParam = null;
            foreach($section["data"] as $dt) {
                              
                if (is_array($dt) && count($dt) == 2) {
                    $sectionData[$dt[0]] = $dt[1];
                    $lastParam = $dt[0];
                }
                else {

                    if (count($sectionData) == 0) {
                        $sectionData["param"] = [ "value" => null, "info" => [ $dt ]];                        //$refSectionData = &$sectionData["param"];
                        $lastParam = "param";
                    }
                    else {
                        if (!isset($sectionData[$lastParam]["info"])) $sectionData[$lastParam] = [ "value" => $sectionData[$lastParam], "info" => [] ];
                        $sectionData[$lastParam]["info"][] = $dt;
                    }

                }

            }
            $sectionName = $section["name"] ? $section["name"] : ("blanksection" . ++$unknownSectionCpt);
            $returnData[$sectionName] = $sectionData;

        }

        return $returnData;

    }

    public static function getPatternFunction(string|array $splitOn): mixed {

        $splitFct = null;

        $splitPattern = null;
        $splitSection = null;
        if (is_array($splitOn)) {
            $splitPattern = $splitOn[0];
            $splitSection = $splitOn[1];
        }
        else {
            $splitPattern = $splitOn;
        }

        $pos = strpos($splitPattern, ":");
        switch(substr($splitPattern, 0, $pos)) {
            //shell patern
            case "p":
                $pattern = substr($splitPattern, $pos + 1);
                $splitFct = function($data) use ($pattern, $splitSection) {
                    if (fnmatch($pattern, $data)) {
                        return $splitSection ? $splitSection : true;
                    }
                    return false;
                };
                break;
            //shell patern case insensitive
            case "pi":
                $pattern = strtolower(substr($splitPattern, $pos + 1));
                $splitFct = function($data) use ($pattern, $splitSection) {
                    if (fnmatch($pattern, strtolower($data))) {
                        return $splitSection ? $splitSection : true;
                    }
                    return false;
                };
                break;
        }

        return $splitFct;

    }

    public static function convertData(array &$data) {

        foreach($data as $key => &$dt) {
            if (is_array($dt)) {
                self::convertData($dt);
                continue;
            }            
            else if (is_string($dt) && ctype_digit($dt)) {
                $dt = intval($dt);
            }
            else if (is_string($dt)) {
                if (strcasecmp($dt, "yes") == 0) {
                    $dt = true;
                }
                else if (strcasecmp($dt, "no") == 0) {
                    $dt = false;
                }
            }
        }
        unset($dt);

    }

    public static function convertTemperature($tvalue, $options = null) {

        $temp = [ "celsius" => $tvalue, "fahrenheit" => $tvalue ];

        if (isset($options["iscelcius"]) && $options["iscelcius"] === true) {
            if (is_numeric($tvalue)) {
                $tvalue = intval($tvalue);
                $temp["celsius"] = $tvalue;
                $temp["fahrenheit"] = round(($tvalue/5*9)+32, 1);
            }
            return $temp;
        }

        $t = str_replace(" ", "", $tvalue);
        $posParenthesis = strpos($t, "(");

        if ($posParenthesis !== false) {
            $tmp = substr($t,0, $posParenthesis);
            $tmpValue = substr($tmp,0, strlen($tmp)-1);
            if (is_numeric($tmpValue)) {
                $tmpValue = intval($tmpValue);
                $tmpMetric = strtolower($tmp[strlen($tmp)-1]);
                //convert
                switch ($tmpMetric) {
                    case "c":
                        $temp["celsius"] = $tmpValue;
                        $temp["fahrenheit"] = round(($tmpValue/5*9)+32, 1);
                        break;
                    case "f":
                        $temp["fahrenheit"] = $tmpValue;
                        $temp["celsius"] = round(($tmpValue-32)*5/9,1);
                        break;
                }
            }
        }

        return $temp;

    }

    public static function convertBytesToHumanReadable(int $bytes, int $precision = 3) {

        $mega = 1024*1024;
        $giga = $mega*1024;
        $tera = $giga*1024;

        if ($bytes < $mega) {
            return round($bytes / 1024, $precision) . " KB";
        }
        elseif ($bytes < $giga) {
            return round($bytes / $mega, $precision) . " MB";
        }
        elseif ($bytes < $tera) {
            return round($bytes / $giga, $precision) . " GB";
        }

        return round($bytes / $tera, $precision) . " TB";

    }

    public static function formatDateTime(string $value, DATE_FORMAT $format): false|\DateTime {

        $fmtString = "";
        $resetTime = false;

        switch($format) {
            case DATE_FORMAT::DATE_MMDDYYYY: $fmtString = "m/d/y"; $resetTime = true; break; // "05/24/16"
            case DATE_FORMAT::DATETIME_THTMTS_MSLASHD_YEAR: $fmtString = "H:i:s m/d, Y"; $resetTime = false; break; // "16:21:11 1/10, 2024"
            case DATE_FORMAT::DATETIME_DDMMYYYY_THTMTS: $fmtString = "m/d/Y, H:i:s"; break; // "02/06/2024, 21:00:00"
            case DATE_FORMAT::DATE_DDDMMSEPYYY: $fmtString = "M d, Y"; $resetTime = true; break;
            default:
                throw new \Exception("date format unknown");
        }

        $date = \DateTime::createFromFormat($fmtString, $value);
        if ($date && $resetTime) $date->setTime(0,0,0);
        return $date;

    }

}

?>