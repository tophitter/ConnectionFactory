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

        /**
         * @param $data
         *
         * @return bool
         */
        public static function is_serialized( $data ) {
            // if it isn't a string, it isn't serialized
            if ( !is_string( $data ) ) {
                return false;
            }

            $data = trim( $data );
            if ( 'N;' == $data ) {
                return true;
            }

            if ( !preg_match( '/^([adObis]):/', $data, $badions ) ) {
                return false;
            }

            switch ( $badions[1] ) {
                case 'a' :
                case 'O' :
                case 's' :
                    if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) )
                        return true;
                break;
                case 'b' :
                case 'i' :
                case 'd' :
                    if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) )
                        return true;
                break;
            }
            return false;
        }

        /**
         * @param null $object
         *
         * @return string
         */
        public static function var_error_str( $object=null ){
            ob_start();                    // start buffer capture
            var_dump( $object );           // dump the values
            $contents = ob_get_contents(); // put the buffer into a variable
            ob_end_clean();                // end capture
            return $contents;
        }

        /*** @param $time_start */
        public static function ScriptStarted(&$time_start){
            date_default_timezone_set('Europe/London');
            // Output Script Run time
            Misc::Print_Message(1, "Script Started: ", date('D d M Y H:i:s'));
            Misc::Print_Message(0,"<hr />");
            $time_start = microtime(true);
        }
        public static function ScriptStarted_Internal(&$time_start){
            date_default_timezone_set('Europe/London');
            $time_start = microtime(true);
            return "Script Started: ". date('D d M Y H:i:s');
        }

        public static function ScriptFinished_Internal(&$time_start){
            return array(
                "Script Finished: ".date('D d M Y H:i:s'),
                "Took: ".round((microtime(true) - $time_start), 2) . " s"." load!"
            );
        }

        /*** @param $time_start */
        public static function ScriptFinished(&$time_start){
            //Misc::Print_Message(0,"<br />");
            Misc::Print_Message(0,"<hr />");
            Misc::Print_Message(1,"Script Finished: ", date('D d M Y H:i:s'));
            Misc::Print_Message(1,"Took: ", round((microtime(true) - $time_start), 2) . " s", " load!");
        }

        public static function GetLevelArgs($level,&$color,&$level_msg,&$term_col){
            $color     = "#959393";
            $term_col = "";
            $level_msg = "";

            switch($level) {
                case -1: {
                    if(self::Debug_Function_Calls_Enabled) {
                        $color     = "green";
                        $term_col = "\e[0;32m";//"\033[0;32m\033[40m";
                        $level_msg = "-FUNC";
                        break;
                    }
                    else
                        return;
                }
                case 1: {
                    $color     = "#959393"; //"#446CC0";
                    $term_col = "\e[0;90m";//"\033[0;90m\033[40m";
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
                    $term_col = "\033[1;93m";//"\033[1;93m\e[0;101m";
                    $level_msg = "-WARNING";
                    break;
                }
                case 4:
                {
                    $color = "green";
                    $term_col = "\e[0;32m";//"\033[0;32m\033[40m";
                    $level_msg = "";
                    break;
                }
                case 5: {
                    $color     = "orange";
                    $term_col = "\e[0;91m";//"\033[0;91m\033[40m";
                    $level_msg = "";
                    break;
                }
                default: {
                    $color     = "#959393";
                    $term_col = "\e[0;90m";//"\033[0;90m\033[40m";
                    $level_msg = "";
                    break;
                }
            }
        }

        /**
         * @param      $array
         * @param bool $string_only
         *
         * @return array|string
         */
        public static function utf8_converter(&$array,$string_only = false, $enHTML = true) {
            if($string_only === true) {
                if(is_string($array)) {
                    if(!mb_detect_encoding($array, 'utf-8', true)) {
                        $array = utf8_encode($array);
                    }
                    else
                        if($enHTML) {
                            $array = mb_convert_encoding($array, 'HTML-ENTITIES', 'UTF-8');
                        }
                        else {
                            $array = mb_convert_encoding($array, 'UTF-8', 'UTF-8');
                        }
                }
            }
            else {
                if(is_string($array)) {
                    if(!mb_detect_encoding($array, 'utf-8', true)) {
                        $array = utf8_encode($array);
                    }
                    else
                        if($enHTML) {
                            $array = mb_convert_encoding($array, 'HTML-ENTITIES', 'UTF-8');
                        }
                        else {
                            $array = mb_convert_encoding($array, 'UTF-8', 'UTF-8');
                        }
                }
                elseif(is_array($array)) {
                    array_walk_recursive($array, function(&$item, $key) use($enHTML) {
                        if(is_array($item)) {
                            self::utf8_converter($item);
                        }
                        elseif(is_object($item)) {
                            foreach($item AS &$obItem) {
                                if(is_string($obItem) && !empty($obItem)) {
                                    if(!mb_detect_encoding($obItem, 'utf-8', true)) {
                                        $obItem = utf8_encode($obItem);
                                    }
                                    else
                                        if($enHTML) {
                                            $obItem = mb_convert_encoding($obItem, 'HTML-ENTITIES', 'UTF-8');
                                        }
                                        else {
                                            $obItem = mb_convert_encoding($obItem, 'UTF-8', 'UTF-8');
                                        }
                                }
                            }
                        }
                        else if(!mb_detect_encoding($item, 'utf-8', true)) {
                            $item = utf8_encode($item);
                        }
                    });
                }
            }

            return $array;
        }

        /**
         * @param      $input
         * @param bool $b_encode
         * @param bool $b_entity_replace
         *
         * @return string|string[]|null|\SimpleXMLElement
         */
        static function utf8_code_deep(&$input, $b_encode = true, $b_entity_replace = true) {
            if(is_string($input)) {
                if($b_encode) {
                    $input = utf8_encode($input);

                    //return Entities to UTF8 characters
                    //important for interfaces to blackbox-pages to send the correct UTF8-Characters and not Entities.
                    if($b_entity_replace) {
                        $input = html_entity_decode($input, ENT_NOQUOTES/* | ENT_HTML5*/, 'UTF-8');
                    } //ENT_HTML5 is a PHP 5.4 Parameter.
                }
                else {
                    //Replace NON-ISO Characters with their Entities to stop setting them to '?'-Characters.
                    if($b_entity_replace) {
                        $input = preg_replace("/([\304-\337])([\200-\277])/e", "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'", $input);
                    }

                    $input = utf8_decode($input);
                }
            }
            elseif(is_array($input)) {
                foreach($input as &$value) {
                    self::utf8_code_deep($value, $b_encode, $b_entity_replace);
                }
            }
            elseif(is_object($input)) {
                $vars = array_keys(get_object_vars($input));

                if(get_class($input) == 'SimpleXMLElement') //DOES NOT WORK!
                {
                    return $input;
                }

                foreach($vars as $var) {
                    self::utf8_code_deep($input->$var, $b_encode, $b_entity_replace);
                }
            }

            return $input;
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
            $term=self::IsCLI();//$term=false;
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
                        if($skip_prefix == true && $k == 1) {
                            continue;
                        }

                        $data[] = $arg;
                    }
                }
            }

           /* if( ( isset( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) >= 1 ))
                $term=true;*/

            if(self::Debug_Enabled != true) {
                return;
            }
            if($data[0] === "<pre>" || $data[0] === "</pre>") {
                echo $data[0];
            } else {
                $color     = "#959393";
                $term_col = "\033[0;90m\033[40m";
                $level_msg = "";
                self:self::GetLevelArgs($level,$color,$level_msg,$term_col);

                if( $term == true ){
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

        /**
         * @param int $level
         */
        public static function Print_Header($level = 1){ //},...$data){ //SERVER NOT PHP% (..$data) WILL NTO WORK FOR LIVE SERVER
            $term=self::IsCLI();//$term=false;
            $html_tag = 'h1';
            $custom_style = '';
            //Make this work with PHP4 and below versions
            $data = Array();
            if(func_num_args() > 1){
                $first_arg = (int)func_get_arg(1);
                if($first_arg > 0) {
                    $html_tag = 'h' . $first_arg;
                    if($first_arg > 2) {
                        $custom_style = 'margin: auto;';
                    }
                }
                foreach(func_get_args() AS $k=>$arg){
                    if($k > 0 && ($first_arg > 0 && $k > 1)) {
                        if($first_arg > 0 && $k === 1) {
                            continue;
                        }
                        $data[] = $arg;
                    }
                }
            }
            global $Debug_Enabled;

            /*if( ( isset( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) >= 1 )) {
                echo "<pre>",php_sapi_name();
                echo "<pre>",var_dump(1,$_SERVER['argv']);
                $term = true;
            }*/

            if($data[0] === '<pre>' || $data[0] === '</pre>') {
                echo $data[0];
            }else {
                $color = '#959393';
                $term_col = "\033[0;90m\033[40m";
                $level_msg = "";
                self::GetLevelArgs($level,$color,$level_msg,$term_col);

                if( $term == true ){

                    echo $term_col."\r\n"; //."<{$html_tag}>";

                    foreach($data AS $_data) {
                        if(is_array($_data) || is_object($_data)) {
                            print_r("\033[0;96m\r\n");
                        }

                        if(is_array($_data) || is_object($_data)){
                            // First cast clone to array
                            if(is_object($_data)){
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
                            if(is_string ($_data)) {
                                $_data = html_entity_decode($_data);
                            }
                            print_r($_data);
                        }
                        if(is_array($_data) || is_object($_data)) {
                            print_r("\033[0m\r\n");
                        }
                    }
                    echo "\033[0m; \r\n"; //</{$html_tag}>

                }
                else {

                    echo "<{$html_tag} style='font-weight: bold; color:{$color}; {$custom_style}'>";
                    foreach ($data AS $_data) {
                        if (is_array($_data) || is_object($_data)) {
                            print_r("<span style='color:#7B89AC;'><pre>");
                        }

                        if(is_array($_data) || is_object($_data)){
                            // First cast clone to array
                            if(is_object($_data)){
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

                    echo "</{$html_tag}>\r\n";
                }
            }
        }

        public static function Print_R()
        { //},...$data){ //SERVER NOT PHP% (..$data) WILL NTO WORK FOR LIVE SERVER
            $skip_prefix = false;
            $term=self::IsCLI();//$term=false;
            //Make this work with PHP4 and below versions
            $data = Array();
            if(func_num_args() >= 0)
            {
                foreach(func_get_args() AS $k => $arg) {
                    $data[] = $arg;
                }
            }

            /*if( ( isset( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) >= 1 ))
                $term=true;*/

            if(self::Debug_Enabled != true) {
                return;
            }

            if($data[0] == "<pre>" || $data[0] == "</pre>") {
                echo $data[0];
            } else {
                $color     = "#959393";
                $term_col = "\033[0;90m\033[40m";
                $level_msg = "";
                self:self::GetLevelArgs(4,$color,$level_msg,$term_col);

                if( $term == true ){
                    echo $term_col."\e[0m ";

                    foreach($data AS $_data) {
                        if(is_array($_data) || is_object($_data)) {
                            print_r("\033[0;96m");
                        }

                        print_r($_data);
                        if(is_array($_data) || is_object($_data)) {
                            print_r("\033[0m");
                        }
                    }
                    //echo "\r\n";

                }
            }
            //self::Print_Message(1,"");
        }

        /**
         * @param int $level
         */
        public static function Print_Message($level = 1){ //},...$data){ //SERVER NOT PHP% (..$data) WILL NTO WORK FOR LIVE SERVER
            $term=self::IsCLI();//$term=false;
            //Make this work with PHP4 and below versions
            $data = Array();
            if(func_num_args() > 1){
                foreach(func_get_args() AS $k=>$arg){
                    if($k > 0) {
                        $data[] = $arg;
                    }
                }
            }
            global $Debug_Enabled;

            /*if( ( isset( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) >= 1 ))
                $term=true;*/

            if($data[0] == "<pre>" || $data[0] == "</pre>") {
                echo $data[0];
            }else {
                $color = "#959393";
                $term_col = "\033[0;90m\033[40m";
                $level_msg = "";
                self::GetLevelArgs($level,$color,$level_msg,$term_col);

                if($term){
                    if($data[0] == "<hr />") {
                        echo "{$term_col}                 \r\n";
                    }else {
                        echo "{$term_col}";
                        foreach($data AS $_data) {
                            if(is_array($_data) || is_object($_data)) {
                                print_r("\033[0;96m\r\n");
                            }

                            if(is_array($_data) || is_object($_data)){
                                // First cast clone to array
                                if(is_object($_data)){
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
                                if(is_string ($_data)) {
                                    $_data = html_entity_decode($_data);
                                }
                                print_r($_data);
                            }

                            if(is_array($_data) || is_object($_data)) {
                                print_r("\033[0m\r\n");
                            }
                        }
                        echo "\033[0m\r\n";
                    }
                }
                else {
                    if ($data[0] == "<hr />") {
                        echo "<hr style='border-color:{$color};' />";
                    }
                    else {
                        echo "<span style='font-weight: bold; color:{$color};'>";
                        foreach ($data AS $_data) {
                            if (is_array($_data) || is_object($_data)) {
                                print_r("<span style='color:#7B89AC;'><pre>");
                            }

                            if(is_array($_data) || is_object($_data)){
                                // First cast clone to array
                                if(is_object($_data)){
                                    if($_data instanceof Exception)
                                        print_r($_data);
                                    else {
                                        $b = (array)clone((object)$_data);
                                    }
                                }
                                else
                                    $b =  (array)$_data;
                                self::unsetProtectedData_Loop($b);
                                print_r((object)$b);
                                unset($b);
                            }else{
                                print_r($_data);
                            }

                            if (is_array($_data) || is_object($_data))
                                print_r("<pre></span>");
                        }
                        echo "</span><br />\r\n";
                    }
                }
            }
        }
        public static function unsetProtectedDataFromArray($r){
            // Then unset value (There will be null bytes around A and to use them you need to run it in double quotes
            // Replace A for * to remove protected properties

            unset($r["Database"], $r["db"], $r["\0*\0Database"], $r["\0A\0Database"], $r["\0*\0db"], $r["\0A\0db"], $r["\0A\0_db"]);
            return (object)$r;
        }

        public static function unsetProtectedData_Loop(&$b){
            $b = self::unsetProtectedDataFromArray((array)$b);
            /*$r2 = (array)$b;
            print_r($r2);
            die();*/
            if(is_array($b) || is_object($b)){
                foreach ($b AS &$r){
                    //$r2 = (array)$r;
                    $r = self::unsetProtectedDataFromArray((array)$r);
                    if(is_array($r)){

                        //self::unsetProtectedDataFromArray($b);
                        self::unsetProtectedData_Loop($r);
                    }
                }
            }
        }
    }