<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_WHERE_ELEMENT
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_WHERE_ELEMENT{
        /** @var string */
        private $Table1Alias;
        /** @var string */
        private $Field1Alias;
        /** @var int */
        public $Type = 0;

        /** @var string */
        private $Compare;

        /** @var string */
        private $Table2Alias;
        /** @var string */
        private $Field2Alias;

        /** @var string */
        private $JoinType     = '';
        /** @var array */
        private $Args         = Array();
        /** @var array */
        private $Args2         = Array();
        /** @var bool */
        private $RightBracket = false;
        /** @var bool */
        private $LeftBracket = false;

        private $Function = null;

        /**
         * SQL_WHERE_ELEMENT constructor.
         *
         * @param string $field1
         * @param string $field2
         * @param string $compare
         * @param string $table_alias1
         * @param string $table_alias2
         * @param int    $type
         * @param string $Join_type
         * @param array  $args
         */
        public function __construct($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = '', $type = 0, $Join_type = '', $args = array(), $func = null, $args2 = null) {
            $this->Type = (int)$type;
            $this->Field1Alias = trim($field1);
            $this->Field2Alias = trim($field2);

            $this->Compare = trim($compare);

            $this->Table1Alias = trim($table_alias1);
            $this->Table2Alias = trim($table_alias2);
            $this->JoinType = strtoupper(trim($Join_type));
            $this->Args = $args;
            $this->Args2 = is_array($args2) ? $args2 : [$args2];
            if($func !== null) {
                $this->Function = ($func instanceof SQL_FUNCTION ? $func : SQL_FUNCTION::get(strtoupper(trim($func))));
            }
        }

        /** @return string */
        public function Output(){
            $data = array();
            if(!empty($this->Function) && $this->Function instanceof SQL_FUNCTION){
                if ($this->Type === 0) {
                    if (!empty($this->JoinType)) {
                        $data[] = $this->JoinType;
                    }
                }
                elseif ($this->Type === 1) {
                    if (empty($this->JoinType)) {
                        $data[] = 'ON';
                    }
                    else {
                        $data[] = $this->JoinType;
                    }
                }
                else {
                    if (!empty($this->JoinType)) {
                        $data[] = $this->JoinType;
                    }
                }
                $func = 'Get' . SQL_FUNCTION::get($this->Function)->getName();
                $field = !empty($this->Table1Alias) ? trim($this->Table1Alias) . '.' . trim($this->Field1Alias) : trim($this->Field1Alias);;
                $val = isset($this->Args) ? is_array($this->Args) ? (isset($this->Args[0]) ? $this->Args[0] : '') : $this->Args : '';
                if($this->Function->is(SQL_FUNCTION::SQL_IF)) {
                    $data[] = "(";
                    $data[] = "(";
                    //IF(compelete == 0, IF(process == 0)
                    $vals = array();
                    $join = "AND";
                    foreach($this->Args AS $arg){
                        if($arg !== null && $arg instanceof self){
                            var_dump("self");
                            $vals[] = $arg->Output();
                        }
                        elseif($arg !==null && $arg instanceof SQL_IF_Data) {
                            $vals[] = $arg->Output();
                            $join = $arg->getJoinType();
                        }
                        else {
                            $vals[] = $arg;
                        }
                    }
                    $val = implode(" {$join} ",$vals);
                    if(isset($this->Args2[0]) && !empty($this->Args2[0])) {
                        $val .= " ,{$this->Args2[0]}";
                    }
                    else {
                        $val .= " ,1,0";
                    }
                }
                $data[] = str_replace(array('{ARG}', '{FIELD}'), array($val, $field), SQL_FUNCTION::$func());
                $data[] = ")";
                $data[] = "{$this->Compare}";
                $data[] = (!empty(trim($this->Table2Alias)) ? ($this->Table2Alias) . '.' : '') . $this->Field2Alias;
                $data[] = ")";
            }else
            {
                if (!empty($this->Field1Alias) || !empty($this->Field2Alias)) {
                    if ($this->Type === 0) {
                        if (!empty($this->JoinType)) {
                            $data[] = $this->JoinType;
                        }
                    }
                    elseif ($this->Type === 1) {
                        if (empty($this->JoinType)) {
                            $data[] = 'ON';
                        }
                        else {
                            $data[] = $this->JoinType;
                        }
                    }
                    else {
                        if (!empty($this->JoinType)) {
                            $data[] = $this->JoinType;
                        }
                    }

                    if ($this->LeftBracket) {
                        $data[] = '(';
                    }

                    if (strtolower(trim($this->Compare)) === 'find_in_set') {
                        $_i = 'FIND_IN_SET(';
                        $_i .= (!empty($this->Table1Alias) ? $this->Table1Alias . '.' : '');
                        $_i .= $this->Field1Alias;
                        $_i .= ', ';
                        $_i .= (!empty($this->Table2Alias) ? $this->Table2Alias . '.' : '');
                        $_i .= $this->Field2Alias;
                        $_i .= ')';

                        $data[] = $_i;
                    }
                    elseif (strtolower(trim($this->Compare)) === 'in') {
                        $data[] = (!empty(trim($this->Table1Alias)) ? trim($this->Table1Alias) . '.' : '') . $this->Field1Alias;
                        $data[] = "{$this->Compare} (";
                        $data[] = (!empty(trim($this->Table2Alias)) ? trim($this->Table2Alias) . '.' : '') . $this->Field2Alias;
                        $data[] = ')';
                    }
                    elseif (strtolower(trim($this->Compare)) === 'between') {
                        $_i[] = isset($this->Args[1]) && !empty(trim($this->Args[1])) ? trim($this->Args[0]) . '.' : '';
                        $_i[] = trim($this->Args[0]);
                        $_i[] = trim($this->Compare);
                        $_i[] = (!empty($this->Table1Alias) ? $this->Table1Alias . '.' : '');
                        $_i[] = $this->Field1Alias;
                        $_i[] = 'AND';
                        $_i[] = (!empty($this->Table2Alias) ? $this->Table2Alias . '.' : '');
                        $_i[] = $this->Field2Alias;
                        $_i[] = '';

                        $data[] = implode(' ', $_i);
                    }
                    else {
                        $data[] = (!empty(trim($this->Table1Alias)) ? trim($this->Table1Alias) . '.' : '') . $this->Field1Alias;
                        $data[] = "{$this->Compare}";
                        $data[] = (!empty(trim($this->Table2Alias)) ? ($this->Table2Alias) . '.' : '') . $this->Field2Alias;
                    }

                    if ($this->RightBracket)
                        $data[] = ')';
                }
                else {
                    if (!empty($this->JoinType)) {
                        $data[] = $this->JoinType;
                    }

                    if ($this->LeftBracket) {
                        $data[] = '(';
                    }
                    if ($this->RightBracket) {
                        $data[] = ')';
                    }
                }
            }
            return implode(' ', array_filter($data, 'strlen'));
        }

        /*** @return bool */
        public function isRightBracket() { return $this->RightBracket; }

        /**
         * @param bool $RightBracket
         * @return SQL_WHERE_ELEMENT
         */
        public function setRightBracket($RightBracket) { $this->RightBracket = $RightBracket; return $this; }

        /*** @return bool */
        public function isLeftBracket() { return $this->LeftBracket; }

        /**
         * @param bool $LeftBracket
         * @return SQL_WHERE_ELEMENT
         */
        public function setLeftBracket($LeftBracket) { $this->LeftBracket = $LeftBracket; return $this; }
    }
