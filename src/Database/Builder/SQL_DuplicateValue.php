<?php

    namespace AmaranthNetwork\Database\Builder;

    class SQL_DuplicateValue{
        /** @var string */
        private $Name;
        private $CustomContent;
        /**
         * @var null|SQL_FUNCTION
         */
        private $SqlFunction = null;

        /**
         * SQL_Value constructor.
         *
         * @param string $name
         */
        public function __construct($name = '', $CustomContent = null, $_sqlFunc = null) {
            $this->Name = trim($name);
            $this->CustomContent = trim($CustomContent);
            $this->SqlFunction = $_sqlFunc;
        }

        /** @return string */
        public function Output()
        {
            $out[] = "{$this->getName()} = ";
            if($this->SqlFunction != null){
                if($this->SqlFunction->is(SQL_FUNCTION::DATE_NOW)){
                    $func = 'Get' . SQL_FUNCTION::get($this->SqlFunction)->getName();
                    $out[] = $this->SqlFunction->$func();
                }
            }elseif($this->CustomContent != null){
                $out[] = $this->getHash();
            }else{
                $out[] = "VALUES(`{$this->getName()}`)";

            }

            return implode(" ",$out);
        }

        public function getHash(){
            return ":_".hash("crc32","{$this->Name}::ONDUPDATE");
        }

        /*** @return string */
        public function getName()  { return $this->Name; }
    }
