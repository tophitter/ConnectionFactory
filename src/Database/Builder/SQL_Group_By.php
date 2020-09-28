<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_Group_By
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_Group_By{
        /** @var string */
        private $Table;
        /** @var string */
        private $Field;

        /**
         * SQL_Group_By constructor.
         *
         * @param string $table
         * @param string $field
         */
        public function __construct($table = '', $field = '') {
            $this->Table = $table;
            $this->Field = $field;
        }

        /** @return string */
        public function Output() {
            $data = array();
            $data[] = (!empty(trim($this->Table)) ? trim($this->Table). '.' : '') . $this->Field;
            return implode(' ',$data);
        }
    }
