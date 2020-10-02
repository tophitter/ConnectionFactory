<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_Value
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_Value{
        /** @var string */
        private $Name;
        /**
         * @var null|SQL_FUNCTION
         */
        private $SqlFunction = null;

        /**
         * SQL_Value constructor.
         *
         * @param string $name
         */
        public function __construct($name = '', $_sqlFunc = null) {
            $this->Name = trim($name);
            $this->SqlFunction = $_sqlFunc;
        }

        /** @return string */
        public function Output() {
            if($this->SqlFunction != null){
                if($this->SqlFunction->is(SQL_FUNCTION::DATE_NOW)){
                    $func = 'Get' . SQL_FUNCTION::get($this->SqlFunction)->getName();
                    return $this->SqlFunction->$func();
                }

            }

            return $this->getName();
        }

        /*** @return string */
        public function getName()  { return $this->Name; }
    }
