<?php
    /**
     * User: Jason Townsend
     * Date: 15/08/2019 10:52
     */

    namespace AmaranthNetwork\Configuration;

    /**
     * Class DBSettings
     * @package AmaranthNetwork\Configuration
     */
    class DBSettings
    {
        /*** @var DBSettings */
        private static $_instance     = array();
        /*** @var string */
        private $Module        = "";
        /*** @var array */
        private $config        = array();
        /*** @var string */
        private $target_plugin = "";


        /**
         * Settings constructor.
         *
         * @param string $module
         * @param string $_plugin
         */
        private function __construct( $module = "", $_plugin = "" )
        {
            $this->Module        = $module;
            $this->target_plugin = $_plugin;
            $_module             = ( !empty( $module ) ? $module : "global" );
            $config_file         = "Config.db.global.php";

            if ( $module != null && !empty( $module ) ) {
                $config_file = "Config.db.{$module}.php";
            }

            $path = __DIR__."/../../../";

            if ( file_exists( $path . "Configs/{$config_file}" ) ) {
                /** @noinspection PhpIncludeInspection */
                $this->config = require( $path . "Configs/{$config_file}" );
                unset( $_conf_db );
            }
            else {
                //Are we a plugin
                if ( !empty( $this->target_plugin ) ) {
                    if ( file_exists( $path . "Plugins/{$this->target_plugin}/Configs/{$config_file}" ) ) {
                        /** @noinspection PhpIncludeInspection */
                        $this->config = require($path . "Plugins/{$this->target_plugin}/Configs/{$config_file}" );
                        unset( $_conf_db );
                    }
                    else {
                        $this->config = array();
                        $_module      = "global";
                    }
                }
                else {
                    $this->config = array();
                    $_module      = "global";
                }
            }
            self::$_instance[ $_module ] = null;
        }

        /**
         * Returns the instance.
         * @static
         *
         * @param string $module
         * @param string $_plugin
         *
         * @return DBSettings
         */
        public static function getInstance( $module = "", $_plugin = "")
        {
            $_module = ( !empty( $module ) ? $module : "global" );


            if ( !isset( self::$_instance[ $_module ] ) ) {
                self::$_instance[$_module] = null;
            }

            if ( self::$_instance[ $_module ] == null ) {
                self::$_instance[$_module] = new DBSettings($module, $_plugin);
            }

            return self::$_instance[ $_module ];
        }

        //Nothing to do
        private function __clone() { }

        //Nothing to do
        private function __wakeup() { }

        public function __destruct() { self::$_instance = null; }

        /**
         * Get a particular value back from the config array
         *
         * @param string $index   The index to fetch in dot notation
         * @param string $default If the requested index/value does not exist then the default value (as long as it is
         *                        not null) will be returned
         *
         * @return mixed

         * @global array $config  The config array defined in the config files
         */
        public function get( $index, $default = null )
        {
            $_index = explode( '.', strtolower( $index ) );

            try {
                $return = $this->getValue( $_index, $this->config );
            } catch ( ConfigException $e ) {
                if ( !is_null( $default ) ) {
                    $return = $default;
                }
                else {
                    return "";
                }
            }

            return $return;
        }

        /**
         * Navigate through a config array looking for a particular index
         *
         * @param array $index The index sequence we are navigating down
         * @param array $value The portion of the config array to process
         *
         * @return mixed
         * @throws ConfigException
         */
        protected function getValue( $index, $value )
        {
            $current_index = null;

            if ( is_array( $index ) && count( $index ) ) {
                $current_index = array_shift($index);
            }

            if ( is_array( $index ) && count( $index ) && isset( $value[ $current_index ] ) && is_array( $value[ $current_index ] ) && count( $value[ $current_index ] ) ) {
                return $this->getValue($index, $value[$current_index]);
            }
            elseif ( isset( $value[ $current_index ] ) ) {
                return $value[$current_index];
            }
            elseif ( isset( $value[ strtoupper( $current_index ) ] ) ) {
                return $value[strtoupper($current_index)];
            }
            else {
                throw new ConfigException("Attempt to access missing configuration variable: $current_index");
            }
        }

        /**
         * @param $compositeKey
         * @param $value
         */
        protected function set_value( $compositeKey, $value )
        {
            $root = &$this->config;

            if ( $root == null ) {
                $root = array();
            }

            $keys = explode( '.', $compositeKey );

            while ( count( $keys ) > 1 ) {
                $key = array_shift( $keys );

                if ( !isset( $root[ $key ] ) ) {
                    $root[$key] = array();
                }

                $root = &$root[ $key ];
            }

            $key          = reset( $keys );
            $root[ $key ] = $value;
        }
    }
