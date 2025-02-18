<?php

    require_once(__DIR__ . "/../vendor/autoload.php");

    spl_autoload_register(function (string $class) {

        $path = explode("\\", $class);
        $findRootSrc = false;
        foreach(explode("\\", $class) as $index => $part) {

            if ($part == "megaraid") {
                $path[$index] = __DIR__;
                $findRootSrc = true;
            }
            else {
                $path[$index] = strtolower($part);
            }

        }

        $classPath = implode("/", $path);

        if (!$findRootSrc) $classPath = __DIR__ . "/" . $classPath;

        $classPath = str_replace("\\", "/", $classPath . ".php");

        require_once($classPath);

    });

?>