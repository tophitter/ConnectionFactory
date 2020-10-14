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
        /** @var string */

        private $Field2    = '';
        /** @var SQL_SORT_TYPE|string */
        private $SortType = SQL_SORT_TYPE::ASC;

        /** @var bool */
        private $RightBracket = false;
        /** @var bool */
        private $LeftBracket = false;
        private $joinType = '';
        private $Compare = '';
        private $IsWhereQuery = false;
        private $IsSortSet = false;

        /**
         * SQL_Order_By constructor.
         *
         * @param string $table
         * @param string $field
         * @param string $sort
         * @param string $join_type
         */
        public function __construct($table = '', $field = '', $sort = SQL_SORT_TYPE::ASC, $join_type = '') {
            $this->Table = trim($table);
            $this->Field = trim($field);
            $this->joinType = trim($join_type);
            if($sort !== null && !empty($sort)){
                $this->SortType = ($sort instanceof SQL_SORT_TYPE ? $sort : SQL_SORT_TYPE::get(strtoupper(trim($sort))));
            }else{
                $this->SortType = null;
            }
        }

        public function SetQueryWhere($field, $compare, $field2){
            $this->Field = $field;
            $this->Field2 = $field2;
            $this->Compare = $compare;
            $this->IsWhereQuery = true;
            return $this;
        }

        public function SetQueryWhereSort($sort = SQL_SORT_TYPE::ASC){
            if($sort !== null){
                $this->SortType = ($sort instanceof SQL_SORT_TYPE ? $sort : SQL_SORT_TYPE::get(strtoupper(trim($sort))));
            }
            $this->IsSortSet = true;
            return $this;
        }

        /**
         * @param bool $RightBracket
         * @return SQL_Order_By
         */
        public function setRightBracket($RightBracket) { $this->RightBracket = $RightBracket; return $this; }

        /*** @return bool */
        public function isLeftBracket() { return $this->LeftBracket; }

        /**
         * @param bool $LeftBracket
         * @return SQL_Order_By
         */
        public function setLeftBracket($LeftBracket) { $this->LeftBracket = $LeftBracket; return $this; }

        /**
         * @return string
         * @throws \ReflectionException
         */
        public function Output() {
            $data = array();

            if ($this->RightBracket) {
                $data[] = ')';
                if (!empty(trim($this->joinType)))
                    $data[] = trim($this->joinType);
            }
            elseif ($this->LeftBracket) {
                if (!empty(trim($this->joinType)))
                    $data[] = trim($this->joinType);
                $data[] = '(';
            }
            elseif ($this->IsSortSet) {
                if(isset($this->SortType)) {
                    $data[] = SQL_SORT_TYPE::get(strtoupper(trim($this->SortType)))->getName();
                }
            }
            else {
                if ($this->IsWhereQuery) {
                    $data[] = (!empty(trim($this->Table)) ? trim($this->Table) . '.' : '') . $this->Field;
                    $data[] = $this->Compare;
                    $data[] = $this->Field2;
                }
                else {
                    $data[] = (!empty(trim($this->Table)) ? trim($this->Table) . '.' : '') . $this->Field;
                    if(isset($this->SortType)) {
                        $data[] = SQL_SORT_TYPE::get(strtoupper(trim($this->SortType)))->getName();
                    }
                }
            }


            return implode(' ',$data);
        }
    }
