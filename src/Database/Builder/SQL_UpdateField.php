<?php

    namespace AmaranthNetwork\Database\Builder;

    use Exception;

    /**
     * Class SQL_UpdateField
     *
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_UpdateField{
        /** @var string */
        private $Name;
        /** @var string */
        private $Value;
        /** @var SQL_FUNCTION|null */
        private $Function;

        /**
         * SQL_UpdateField constructor.
         *
         * @param string $name
         * @param string $value
         * @param null   $function
         *
         */
        public function __construct($name, $value, $function = null) {
            $this->Name = trim($name);
            $this->Value = trim($value);
            if($function !== null) {
                try {
                    $this->Function = ($function instanceof SQL_FUNCTION ? $function : SQL_FUNCTION::get(strtoupper(trim($function))));
                }
                catch (Exception $e){

                }
            }
        }

        /**
         * @return string
         */
        public function Output(){
            $data = array();
            $data[] = trim($this->getName());
            $data[] = "=";
            if(!empty($this->Function) && $this->Function instanceof SQL_FUNCTION){
                $func = 'Get' . SQL_FUNCTION::get($this->Function)->getName();
                $field = trim($this->getName());
                $val = $this->Value;

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
                $data[] = str_replace(array('{ARG}', '{FIELD}'), array($val, $field), SQL_FUNCTION::$func());
            }else {

                $data[] = $this->Value;
            }

            return implode(' ', array_filter($data, 'strlen'));
        }

        /*** @return string */
        public function getName() { return $this->Name; }
        public function getValue() { return $this->Value; }

    }