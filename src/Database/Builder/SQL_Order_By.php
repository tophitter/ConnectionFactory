<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_Order_By
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_Order_By{
        /** @var string */
        private $Table    = '';
        /** @var string */
        private $Field    = '';
        /** @var SQL_SORT_TYPE|string */
        private $SortType = SQL_SORT_TYPE::ASC;

        /**
         * SQL_Order_By constructor.
         *
         * @param string $table
         * @param string $field
         * @param string $sort
         *
         * @throws \ReflectionException
         */
        public function __construct($table = '', $field = '', $sort = SQL_SORT_TYPE::ASC) {
            $this->Table = $table;
            $this->Field = $field;
            if($sort !== null){
                $this->SortType = ($sort instanceof SQL_SORT_TYPE ? $sort : SQL_SORT_TYPE::get(strtoupper(trim($sort))));
            }
        }

        /**
         * @return string
         * @throws \ReflectionException
         */
        public function Output() {
            $data = array();
            $data[] = (!empty(trim($this->Table)) ? trim($this->Table). '.' : '') . $this->Field;
            $data[] = SQL_SORT_TYPE::get(strtoupper(trim($this->SortType)))->getName();

            return implode(' ',$data);
        }
    }
