<?php

    namespace AmaranthNetwork\Database;

    use AmaranthNetwork\Configuration\DBSettings;

    /**
     * Class ConnectionFactory
     *
     * @package AmaranthNetwork\Database
     */
    class ConnectionFactory
    {
        /** @var  ConnectionFactory $factory */
        private static $factory;
        /** @var  PDOAdapter[] dbs */
        private $dbs = array();

        public static function F(){ return self::getFactory(); }
        public static function getFactory()
        {
            if (!self::$factory) {
                self::$factory = new self();
            }
            return self::$factory;
        }

        /**
         * @param string $ConnectionName connection config to use
         * @param bool   $debug enable /  disable debug messages
         *
         * @return PDOAdapter
         */
        public function &C($ConnectionName = "", $debug = true){ return $this->getConnection($ConnectionName,$debug); }

        /**
         * @param string $ConnectionName connection config to use
         * @param bool   $debug enable /  disable debug messages
         *
         * @return PDOAdapter
         */
        public function &getConnection($ConnectionName = "", $debug = false){

            if(!isset($this->dbs[$ConnectionName]) || $this->dbs[$ConnectionName] == null){
                $this->dbs[$ConnectionName] = $this->CreateNewCustomConnection($ConnectionName);
            }

            $this->dbs[$ConnectionName]->setErrorLog($debug);
            return $this->dbs[$ConnectionName];
        }

        private function CreateNewCustomConnection($ConnectionName){
            return new PDOAdapter(
                DBSettings::getInstance($ConnectionName)->get("HOST"),
                DBSettings::getInstance($ConnectionName)->get("PORT"),
                DBSettings::getInstance($ConnectionName)->get("USER"),
                DBSettings::getInstance($ConnectionName)->get("PASSWORD"),
                DBSettings::getInstance($ConnectionName)->get("DATABASE"),
                DBSettings::getInstance($ConnectionName)->get("DISPLAY_NAME")
            );
        }
    }