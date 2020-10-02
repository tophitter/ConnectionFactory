<?php
    /**
     * Created by PhpStorm.
     * User: Jason Townsend
     * Company: 6B Digital
     * Website: http://www.6bdigital.com/
     * Date: 15/03/2016
     * Time: 12:47
     */

    namespace AmaranthNetwork;


    use Exception;

    class Misc
    {
        public static function locateServicesParams(){
            global $argc,$argv,$_GET;
            if (php_sapi_name() === "cli") {
                for ($c = 1; $c < $argc; $c++) {
                    $param = explode("=", $argv[$c], 2);
                    $_GET[$param[0]] = $param[1]; // $_GET['name1'] = 'value1'
                }
            }
        }

        public static function GetLevelArgs($level,&$color,&$level_msg,&$term_col){
            $color     = "#959393";
            $term_col = "";
            $level_msg = "";

            switch($level) {
                case -1:
                    return;
                case 1: {
                    $color     = "#959393";
                    $term_col = "\e[0;90m";
                    $level_msg = "-INFO";
                    break;
                }
                case 3: {
                    $color     = "#A26A66";
                    $term_col = "\033[0;93m\033[40m";
                    $level_msg = "-SQL";
                    break;
                }
                case 2: {
                    $color     = "darkred";
                    $term_col = "\033[1;93m";
                    $level_msg = "-WARNING";
                    break;
                }
                case 4:
                {
                    $color = "green";
                    $term_col = "\e[0;32m";
                    $level_msg = "";
                    break;
                }
                case 5: {
                    $color     = "orange";
                    $term_col = "\e[0;91m";
                    $level_msg = "";
                    break;
                }
                default: {
                    $color     = "#959393";
                    $term_col = "\e[0;90m";
                    $level_msg = "";
                    break;
                }
            }
        }

        /** Debug Print Pass Unlimited Amount of Variables
         *
         * @param int $level
         * @param string|mixed ...
         *
         */
        public static function Debug_Print($level = 1)
        { //},...$data){ //SERVER NOT PHP% (..$data) WILL NTO WORK FOR LIVE SERVER
            $skip_prefix = false;
            $term=self::IsCLI();
            //Make this work with PHP4 and below versions
            $data = Array();
            if(func_num_args() > 1)
            {
                foreach(func_get_args() AS $k => $arg)
                {
                    //if the first args is skip_prefix then trigger it
                    if(func_get_arg(1) == "skip_prefix") {
                        $skip_prefix = true;
                    }

                    if($k > 0) {
                        if($skip_prefix === true && $k === 1) {
                            continue;
                        }

                        $data[] = $arg;
                    }
                }
            }

            if($data[0] === "<pre>" || $data[0] === "</pre>") {
                echo $data[0];
            } else {
                $color     = "#959393";
                $term_col = "\033[0;90m\033[40m";
                $level_msg = "";
                self:self::GetLevelArgs($level,$color,$level_msg,$term_col);

                if( $term === true ){
                    echo $term_col.(!$skip_prefix ? "DEBUG{$level_msg}" : "").":\e[0m ";

                    foreach($data AS $_data) {
                        if(is_array($_data) || is_object($_data)) {
                            print_r("\033[0;96m\r\n");
                        }

                        if(is_array($_data) || is_object($_data)){
                            // First cast clone to array
                            if(is_object($_data)) {
                                if($_data instanceof Exception) {
                                    print_r($_data);
                                }
                                else {
                                    $b = (array)clone((object)$_data);
                                }
                            }else {
                                $b = (array)$_data;
                            }
                            self::unsetProtectedData_Loop($b);
                            print_r((object)$b);
                            unset($b);
                        }else{
                            if(is_string ($_data)) {
                                $_data = html_entity_decode($_data);
                            }
                            print_r($_data);
                        }
                        if(is_array($_data) || is_object($_data)) {
                            print_r("\033[0m\r\n");
                        }
                    }
                    echo "\r\n";

                }
                else {
                    echo "<span style='font-weight: bold; color:{$color};'>" . (!$skip_prefix ? "DEBUG{$level_msg}" : "") . ": ";
                    foreach ($data AS $_data) {
                        if (is_array($_data) || is_object($_data)) {
                            print_r("<span style='color:#7B89AC;'><pre>");
                        }

                        if(is_array($_data) || is_object($_data)){
                            // First cast clone to array
                            if(is_object($_data)) {
                                if($_data instanceof Exception) {
                                    print_r($_data);
                                }
                                else {
                                    $b = (array)clone((object)$_data);
                                }
                            }
                            else {
                                $b = (array)$_data;
                            }
                            self::unsetProtectedData_Loop($b);
                            print_r((object)$b);
                            unset($b);
                        }else{
                            print_r($_data);
                        }

                        if (is_array($_data) || is_object($_data)) {
                            print_r("<pre></span>");
                        }
                    }

                    echo "</span><br />\r\n";
                }
            }
        }

        public static function IsCLI(){
            if(php_sapi_name() === "cli") // || php_sapi_name() == "cgi-fcgi")
            {
                return true;
            }

            return false;
        }

        public static function unsetProtectedDataFromArray($r){
            // Then unset value (There will be null bytes around A and to use them you need to run it in double quotes
            // Replace A for * to remove protected properties

            unset($r["Database"], $r["db"], $r["\0*\0Database"], $r["\0A\0Database"], $r["\0*\0db"], $r["\0A\0db"], $r["\0A\0_db"]);
            return (object)$r;
        }

        public static function unsetProtectedData_Loop(&$b){
            $b = self::unsetProtectedDataFromArray((array)$b);
            if(is_array($b) || is_object($b)){
                foreach ($b AS &$r){
                    $r = self::unsetProtectedDataFromArray((array)$r);
                    if(is_array($r)){
                        self::unsetProtectedData_Loop($r);
                    }
                }
            }
        }
    }