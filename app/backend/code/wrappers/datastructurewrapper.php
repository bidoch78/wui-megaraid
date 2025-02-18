<?php

declare(strict_types=1);

namespace megaraid\wrappers;

use megaraid\wrappers\dataWrapper;

class dataStructureWrapper implements \IteratorAggregate {

    private ?array $_options = null;
    private ?dataStructureWrapper $_parent = null;
    private ?string $_sectionName = null;
    private ?array $_subsections = null;
    private mixed $_patternFct = null;

    private ?dataNode $_data = null;

    public function __construct(string $sectionName = null, string|array $pattern = null, array $options = null) {
        $this->_sectionName = ($sectionName) ? $sectionName : "";
        $this->_options = $options;
        if ($pattern) $this->_patternFct = dataWrapper::getPatternFunction($pattern);
    }

    public function getOption(string $name): mixed {
        return ($this->_options && isset($this->_options[$name])) ? $this->_options[$name] : null;
    }

    public function getSectionName(): string { return $this->_sectionName; }

    public function addSection(string $sectionName, string|array $pattern = null, array $options = null):dataStructureWrapper {
        if (!$this->_subsections) $this->_subsections = [];
        $ss = new self($sectionName, $pattern, $options);
        $ss->_parent = $this;
        $this->_subsections[$sectionName] = $ss;
        return $ss;
    }
    
    public function getSection(string $sectionName): null|dataStructureWrapper {
        if (!$this->_subsections || !isset($this->_subsections[$sectionName])) return null;
        return $this->_subsections[$sectionName];
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->_subsections);
    }

    public function getPath(): array {

        $path = [];

        $current = $this;
        while($current) {
            array_unshift($path, $current->_sectionName);
            $current = $current->_parent;
        }

        return $path;

    }

    public function findSection(string $data): null|dataStructurewrapper {

        if ($this->_patternFct && ($this->_patternFct)($data)) return $this;

        if ($this->_subsections) {

            foreach($this->_subsections as $ss) {
                $findOn = $ss->findSection($data);
                if ($findOn) return $findOn;
            }

        }

        return null;

    }

    /// DATA ///

    public ?array $_currentPath = null;

    public function reset() {
        $this->_data = null;
        $this->_currentPath = null;
    }

    public function analyzeData(string $data): void {

        if (!$this->_data) $this->_data = new dataNode();

        $findOn = $this->findSection($data);
        $addData = true;
        if ($findOn) {
            $this->_currentPath = $findOn->getPath();
            $this->_data->addNewNodeWithPath($this->_currentPath);
            $addData = $findOn->getOption("removesectiondata") !== true;
        }

        if ($addData) $this->_data->getNodeByPath($this->_currentPath)->addData($data);

    }

    public function getArray(array $options = null):array {
        return ($this->_data) ? $this->_data->getArray($this, $options) : [];

    }

    public static function root(): dataStructureWrapper {
        $dt = new self();
        $dt->_data = new dataNode();
        return new self();
    }

}

class dataNode {
        
    private ?dataNode $_parent;
    private ?array $_children = null;
    private string $_section = "";

    private ?array $_data = null;

    public function __construct(dataNode $p = null, string $section = "") {
        $this->_parent = $p;
        $this->_section = $section;
    }

    public function addData(string $data) {
        //var_dump("add " . $data . " to " . $this->_section);
        if (!$this->_data) $this->_data = [];
        $this->_data[] = $data;
    }

    public function getArray(dataStructureWrapper $structure, array $options = null): array {

        $array = [];

        if ($this->_data) {
            $prop = dataWrapper::translateSectionDataToAssocArray(dataWrapper::parseDataBySection($this->_data, options: $options));
            if (isset($prop["blanksection1"])) { $prop = array_merge($prop, $prop["blanksection1"]); unset($prop["blanksection1"]); }

            $array["properties"] = $prop;

        }
        
        if ($this->_children) {

            foreach($this->_children as $section => $sectionArray) {

                $structureSection = $structure->getSection($section) ?? $structure;
                if (!isset($array[$section])) {
                    if ($structureSection->getOption("noarray") === true)
                        $array[$section] = null;
                    else
                        $array[$section] = [];
                }
                foreach($sectionArray as $sectionData) {
                    if ($structureSection->getOption("noarray") === true)
                        $array[$section] = $sectionData->getArray($structureSection, $options);
                    else
                        $array[$section][] = $sectionData->getArray($structureSection, $options);
                }

            }

        }

        return $array;

    }

    public function addNewNodeWithPath(array $path = null): void {
        
        if (!$path || count($path) == 0) return;

        if ($path[0] !== $this->_section) throw new \Exception("unknown path (" . $path[0] . " != " . $this->_section . ")");

        if (count($path) == 1) return;

        if (!$this->_children) $this->_children = [];

        $nextPath = $path[1];

        if (!isset($this->_children[$nextPath])) $this->_children[$nextPath] = [];

        $newPath = $path; array_shift($newPath);
        if (count($newPath) == 1) $this->_children[$nextPath][] = new dataNode($this, $nextPath);

        $this->_children[$nextPath][count($this->_children[$nextPath])-1]->addNewNodeWithPath((array)$newPath);

    }

    public function getNodeByPath(array $path = null): dataNode {

        if (!$path || count($path) == 0) return $this;

        if ($path[0] !== $this->_section) throw new \Exception("unknown path (" . $path[0] . " != " . $this->_section . ")");
        
        if (count($path) == 1) return $this;
        
        $nextPath = $path[1];
        
        if (!isset($this->_children[$nextPath])) throw new \Exception("section " . $nextPath . " not found (" . $this->_section . ")");
        
        $newPath = $path; array_shift($newPath);
        return $this->_children[$nextPath][count($this->_children[$nextPath])-1]->getNodeByPath((array)$newPath);

    } 

}

?>