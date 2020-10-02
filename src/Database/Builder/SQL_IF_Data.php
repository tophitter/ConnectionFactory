<?php

    namespace AmaranthNetwork\Database\Builder;

    class SQL_IF_Data{
        /** @var string */
        private $Name;
        /** @var string */
        private $TablesAlias;
        /** @var string */
        private $Compare;
        /** @var mixed */
        private $Value;
        private $JoinType = "AND";

        public function __construct($name = '', $name_alias = '', $tables_alias = '', $compare, $value, $join_type = "AND") {
            $this->Name = trim($name);
            $this->NameAlias = trim($name_alias);
            $this->TablesAlias = trim($tables_alias);
            $this->Compare = trim($compare);
            $this->Value = $value;
            $this->JoinType = $join_type;
        }

        public function Output(){
            $out = array();
            $out[] = !empty($this->getTablesAlias()) ? trim($this->getTablesAlias()) . '.' . trim($this->getName()) : trim($this->getName());
            $out[] = $this->Compare;
            if(is_array($this->Value)){
                foreach ($this->Value AS $val){
                    if($val instanceof SQL_Column){

                    }
                }
            }else{
                if($this->Value instanceof SQL_Column){

                }else{
                    $out[] = $this->Value;
                }
            }


            return implode(" ", $out);
        }

        /*** @return string */
        public function getName() { return $this->Name; }

        /*** @return string */
        public function getTablesAlias() { return $this->TablesAlias; }

        /*** @return mixed|string */
        public function getJoinType() { return $this->JoinType; }
    }
