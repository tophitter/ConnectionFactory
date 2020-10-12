<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_Value
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_Value{
        /** @var string */
        private $Name;
        private $RowIndex = -1;
        /**
         * @var null|SQL_FUNCTION
         */
        private $SqlFunction = null;

        /**
         * SQL_Value constructor.
         *
         * @param string $name
         */
        public function __construct($name = '', $_sqlFunc = null, $row_index = -1) {
            $this->Name = trim($name);
            $this->SqlFunction = $_sqlFunc;
            $this->RowIndex = $row_index;
        }

        /** @return string */
        public function Output() {
            if($this->SqlFunction != null){
                if($this->SqlFunction->is(SQL_FUNCTION::DATE_NOW)){
                    $func = 'Get' . SQL_FUNCTION::get($this->SqlFunction)->getName();
                    return $this->SqlFunction->$func();
                }

            }

            return $this->RowIndex >= 0 ? trim($this->Name)."_{$this->RowIndex}" : $this->getName();
        }

        /*** @return string */
        public function getName()  { return $this->Name; }
    }
