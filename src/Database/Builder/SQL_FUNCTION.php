<?php

    namespace AmaranthNetwork\Database\Builder;

    use AmaranthNetwork\Enums\Enum;

    /**
     * Class SQL_FUNCTION
     *
     * @package Database
     */
    class SQL_FUNCTION extends Enum{
        /** @var string */
        const COALESCE       = 'COALESCE';
        /** @var string */
        const CONCAT         = 'CONCAT';
        /** @var string */
        const DATE_FORMAT    = 'DATE_FORMAT';
        /** @var string */
        const UNIX_TIMESTAMP = 'UNIX_TIMESTAMP';
        const SQL_IF = 'IF';
        const COUNT = 'COUNT';
        const GROUP_CONCAT = 'GROUP_CONCAT';
        const GROUP_CONCAT_SEPARATOR = 'GROUP_CONCAT_SEPARATOR';

        const DATE_NOW = "NOW";
        const CURDATE = "CURDATE";


        /** @return string */
        public static function getDATE_FORMAT() { return 'DATE_FORMAT({FIELD},{ARG})'; }

        /** @return string */
        public static function getCOALESCE() { return 'COALESCE({FIELD},{ARG})'; }
        public static function getCOUNT() { return 'COUNT({FIELD})'; }
        public static function getGROUP_CONCAT() { return 'GROUP_CONCAT({FIELD})'; }
        public static function getGROUP_CONCAT_SEPARATOR() { return 'GROUP_CONCAT({FIELD} SEPARATOR {ARG})'; }

        /** @return string */
        public static function getCONCAT() { return 'CONCAT({FIELD},{ARG})'; }

        /** @return string */
        public static function getUNIX_TIMESTAMP() { return 'UNIX_TIMESTAMP({FIELD})'; }
        public static function getDATE_NOW() { return 'now()'; }
        public static function getCURDATE() { return 'CURDATE()'; }
        public static function getSQL_IF() { return 'if({ARG})'; }
    }