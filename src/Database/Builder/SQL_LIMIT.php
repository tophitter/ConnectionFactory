<?php
    namespace AmaranthNetwork\Database\Builder;

    /**
     * Class SQL_LIMIT
     * @package AmaranthNetwork\Database\Builder
     */
    class SQL_LIMIT{
        /** @var null */
        public $Offset = null;
        /** @var null */
        public $Count = null;

        /**
         * SQL_LIMIT constructor.
         *
         * @param null $offset
         * @param null $count
         */
        public function __construct($offset = null, $count = null) {
            $this->Offset = $offset;
            $this->Count = $count;
        }

        /** @return string */
        public function Output() {
            if ($this->Offset !== null && $this->Count !== null) {
                return "LIMIT {$this->Offset},{$this->Count}";
            }

            if($this->Count !== null) {
                return "LIMIT {$this->Count}";
            }else{
                if($this->Offset !== null) {
                    return "LIMIT {$this->Offset}";
                }
            }

            return '';
        }
    }
