<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_Column
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_Column{
        /** @var string */
        private $Name;
        /** @var string */
        private $NameAlias;
        /** @var string */
        private $TablesAlias;
        /** @var SQL_FUNCTION|null */
        private $Function;
        /** @var array */
        private $Args = array();
        /** @var array */
        private $Args2 = array();

        /**
         * SQL_Column constructor.
         *
         * @param string $name
         * @param string $name_alias
         * @param string $tables_alias
         * @param null   $function
         * @param array  $args
         *
         */
        public function __construct($name = '', $name_alias = '', $tables_alias = '', $function = null, $args = array(), $args2 = array()) {
            $this->Name = trim($name);
            $this->NameAlias = trim($name_alias);
            $this->TablesAlias = trim($tables_alias);
            $this->Args2 = is_array($args2) ? $args2 : [$args2];
            if($function !== null) {
                $this->Function = ($function instanceof SQL_FUNCTION ? $function : SQL_FUNCTION::get(strtoupper(trim($function))));
            }
            $this->Args = $args;
        }

        /**
         * @return string
         * @throws \ReflectionException
         */
        public function Output(){
            $data = array();

            if(!empty($this->Function) && $this->Function instanceof SQL_FUNCTION){
                $func = 'Get' . SQL_FUNCTION::get($this->Function)->getName();
                $field = !empty($this->getTablesAlias()) ? trim($this->getTablesAlias()) . '.' . trim($this->getName()) : trim($this->getName());;
                $val = isset($this->Args) ? is_array($this->Args) ? (isset($this->Args[0]) ? $this->Args[0] : '') : $this->Args : '';

                if($this->Function->is(SQL_FUNCTION::GROUP_CONCAT_SEPARATOR)){
                    $val = is_string($val) ? "'{$val}'" : $val;
                }
                if($this->Function->is(SQL_FUNCTION::COALESCE)){
                    $vals = array();
                    foreach($this->Args AS $arg){
                        if($arg !== null && $arg instanceof self){
                            $vals[] = $arg->Output();
                        }else {
                            $vals[] = $arg;
                        }
                    }
                    $val = implode(',',$vals);
                }
                if($this->Function->is(SQL_FUNCTION::CONCAT)){
                    $vals = array();
                    foreach($this->Args AS $arg){
                        if($arg !== null && $arg instanceof self){
                            $vals[] = $arg->Output();
                        }else {
                            $vals[] = $arg;
                        }
                    }
                    $val = implode(',',$vals);
                }
                if($this->Function->is(SQL_FUNCTION::SQL_IF)) {
                    //IF(compelete == 0, IF(process == 0)
                    $vals = array();
                    foreach($this->Args AS $arg){
                        if($arg !== null && $arg instanceof self){
                            $vals[] = $arg->Output();
                        }
                        elseif($arg !==null && $arg instanceof SQL_IF_Data) {
                            $vals[] = $arg->Output();
                        }
                        else {
                            $vals[] = $arg;
                        }
                    }

                    $val = implode(' AND ',$vals);
                    if(isset($this->Args2[0]) && !empty($this->Args2[0])) {
                        $val .= " ,{$this->Args2[0]}";
                    }
                    else {
                        $val .= " ,1,0";
                    }
                }
                $data[] = str_replace(array('{ARG}', '{FIELD}'), array($val, $field), SQL_FUNCTION::$func());
            }else {
                $data[] = !empty($this->getTablesAlias()) ? trim($this->getTablesAlias()) . '.' . trim($this->getName()) : trim($this->getName());
            }

            if(!empty($this->getNameAlias())) {
                $data[] = "AS '" . trim($this->getNameAlias()) . "'";
            }

            return implode(' ', array_filter($data, 'strlen'));
        }

        /*** @return string */
        public function getName() { return $this->Name; }

        /*** @return string */
        public function getNameAlias() { return $this->NameAlias; }

        /*** @return string */
        public function getTablesAlias() { return $this->TablesAlias; }
    }