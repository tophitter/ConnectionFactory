<?php

    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_Join
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_Join{
        /** @var SQL_JOIN_TYPE */
        private $Type = SQL_JOIN_TYPE::LEFT;
        /** @var string */
        private $Table;
        /** @var string */
        private $TableAlias;
        /** @var SQL_WHERE_ELEMENT[] $ON  */
        private $ON;

        /**
         * SQL_Join constructor.
         *
         * @param                    $table
         * @param                    $table_alias
         * @param array              $on
         * @param SQL_JOIN_TYPE|null $type
         *
         * @throws \ReflectionException
         */
        public function __construct($table, $table_alias, $on = array(), $type = null ) {
            $this->Table = trim($table);
            $this->TableAlias = trim($table_alias);
            $this->ON = $on;

            if($type !== null){
                $this->Type = ($type instanceof SQL_JOIN_TYPE ? $type : SQL_JOIN_TYPE::get(strtoupper(trim($type))));
            }
        }

        /**
         * @return string
         * @throws \ReflectionException
         */
        public function Output() {
            $data = array(
                SQL_JOIN_TYPE::get(strtoupper(trim($this->Type)))->getName(),
                'JOIN',
                $this->Table
            );

            if(!empty($this->TableAlias)) {
                $data[] = "AS {$this->TableAlias}";
            }

            foreach($this->ON AS $on){
                $data[] = $on->Output();
            }

            return implode(' ', array_filter($data, 'strlen'));
        }
    }
