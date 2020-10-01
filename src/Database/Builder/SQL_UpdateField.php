<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_UpdateField
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_UpdateField{
        /** @var string */
        private $Name;
        /** @var string */
        private $Value;

        /**
         * SQL_UpdateField constructor.
         *
         * @param string $name
         * @param string $value
         *
         */
        public function __construct($name, $value) {
            $this->Name = trim($name);
            $this->Value = trim($value);
        }

        /**
         * @return string
         */
        public function Output(){
            $data = array();
            $data[] = trim($this->getName());
            $data[] = "=";
            $data[] = $this->Value;

            return implode(' ', array_filter($data, 'strlen'));
        }

        /*** @return string */
        public function getName() { return $this->Name; }
        public function getValue() { return $this->Value; }

    }