<?php
    /**
     * User: Jason Townsend
     * Date: 07/09/2018
     * Time: 12:53
     */

    namespace AmaranthNetwork\Database;

    /**
     * Class SQLBuilder
     * @package Database
     */
    class SQLBuilder
    {
        //region Fields
        /** @var SQL_Column[] $Columns */
        private $Columns = Array();
        /** @var SQL_Value[] $Columns */
        private $Values = Array();
        /** @var SQL_Join[] $Joins **/
        private $Joins = Array();
        /** @var SQL_WHERE_ELEMENT[] $Joins **/
        private $Where = Array();
        /** @var string */
        private $Table;
        /** @var string */
        private $TablesAlias;
        /** @var SQL_Type|string|null */
        private $QueryType = SQL_Type::SELECT;
        /** @var array */
        private $Binds = Array();
        /** @var array */
        private $Having = array();
        /** @var SQL_LIMIT $Limit */
        private $Limit;
        /** @var array */
        private $GroupByData = Array();
        /** @var array */
        private $OrderByData = Array();
        /*** @var SQL_DuplicateValue[] */
        Private $DuplicateKeys = array();
        //endregion

        /*** @return bool  */
        public function HasBinds(){ return (count($this->Binds) > 0); }

        /**
         * SQLBuilder constructor.
         *
         * @param string        $table
         * @param string        $tables_alias
         * @param SQL_Type|null $type
         *
         * @throws \ReflectionException
         */
        public function __construct($table = '', $tables_alias = '', $type = null){
            $this->Table       = trim($table);
            $this->TablesAlias = trim($tables_alias);
            if($type !== null) {
                $this->QueryType = ($type instanceof SQL_Type ? $type : SQL_Type::get(strtoupper(trim($type))));
            }
        }
        //region Columns

        /**
         * @param string $Name
         * @param string $Alias
         * @param string $tableAlias
         * @param null   $function
         * @param array  $args
         *
         * @return SQLBuilder
         * @throws \ReflectionException
         */
        public function Column($Name, $Alias, $tableAlias = '', $function = null, $args = array(), $args2 = array()){
            $this->Columns[] = new SQL_Column($Name,$Alias,$tableAlias, $function, $args,$args2);
            return $this;
        }

        /**
         * @param array $array
         *
         * @return SQLBuilder
         * @throws \ReflectionException
         */
        public function Columns($array){
            foreach($array AS $ar){
                $this->Columns[] = new SQL_Column(
                    $ar['name'],
                    (isset($ar['alias']) ? $ar['alias'] : ''),
                    (isset($ar['table_alias']) ? $ar['table_alias'] : ''),
                    isset($ar['function']) ? $ar['function'] : null,isset($ar['args']) ? $ar['args'] : array());
            }

            return $this;
        }

        /**
         * @param string $Name
         * @param string $Alias
         * @param string $tableAlias
         * @param null   $function
         * @param array  $args
         *
         * @return SQL_Column
         * @throws \ReflectionException
         */
        public function CreateColumn($Name, $Alias, $tableAlias = '', $function = null, $args = array()){
            return new SQL_Column($Name,$Alias,$tableAlias, $function, $args);
        }
        //endregion
        #region Values
        /**
         * @param string $name
         * @param null   $bind
         *
         * @return SQLBuilder
         */
        public function Value($name, $bind = null, $function = null){
            if($function != null) {
                $this->Values[$name] = new SQL_Value($name, $function instanceof SQL_FUNCTION ? $function : SQL_FUNCTION::get(strtoupper(trim($function))));
            }
            else {
                $this->Values[$name] = new SQL_Value($name);
            }
            if($bind !== null){
                $this->Binds[trim(str_replace(':','',$name))] = trim($bind);
            }
            return $this;
        }

        /**
         * @param string $name
         * @param null   $custom_content
         * @param null   $function
         *
         * @return SQLBuilder
         */
        public function DuplicateValue($name, $custom_content = null, $function = null){
            if($function != null) {
                $function = $function instanceof SQL_FUNCTION ? $function : SQL_FUNCTION::get(strtoupper(trim($function)));
            }
            $dv = new SQL_DuplicateValue($name,$custom_content,$function);
            $this->DuplicateKeys[$dv->getHash()] = $dv;
            if($custom_content !== null){
                $this->Binds[trim(str_replace(':','',$dv->getName()))] = trim($custom_content);
            }
            return $this;
        }
        /**
         * @param array $array
         *
         * @return SQLBuilder
         */
        public function Values($array){
            foreach($array AS $ar){
                if(isset($ar['function'])){
                    unset($ar['binds']);
                }
                if(isset($ar['function']) && $ar['function'] != null) {
                    $this->Values[$ar['name']] = new SQL_Value($ar['name'], $ar['function'] instanceof SQL_FUNCTION ? $ar['function'] : SQL_FUNCTION::get(strtoupper(trim($ar['function']))));
                }
                else {
                    $this->Values[$ar['name']] = new SQL_Value($ar['name']);
                }

                if(isset($ar['binds']) && $ar['binds'] !== null){
                    $this->Binds[trim(str_replace(':','',$ar['name']))] = trim($ar['binds']);
                }

                /*$this->Values[$ar['name']] = new SQL_Value($ar['name']);
                if($ar['binds'] !== null){
                    $this->Binds[trim(str_replace(':','',$ar['name']))] = trim($ar['binds']);
                }*/
            }

            return $this;
        }
        #endregion
        //region Joins
        /**
         * @param String             $table
         * @param String             $table_alias
         * @param array              $on
         * @param SQL_JOIN_TYPE|null $type
         *
         * @return $this
         * @throws \ReflectionException
         */
        public function Join($table, $table_alias, $on, $type = null){
            $this->Joins[] = new SQL_Join($table,$table_alias,$on,$type);
            return $this;
        }

        /**
         * @param        $field1
         * @param        $field2
         * @param string $compare
         * @param string $table_alias1
         * @param string $table_alias2
         *
         * @return SQL_WHERE_ELEMENT
         */
        public function CreateOrJoinOn($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = ''){
            return new SQL_WHERE_ELEMENT($field1,$field2,$compare,$table_alias1,$table_alias2,1, 'OR');
        }

        /**
         * @param        $field1
         * @param        $field2
         * @param string $compare
         * @param string $table_alias1
         * @param string $table_alias2
         *
         * @return SQL_WHERE_ELEMENT
         */
        public function CreateAndJoinOn($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = ''){
            return new SQL_WHERE_ELEMENT($field1,$field2,$compare,$table_alias1,$table_alias2,1, 'AND');
        }

        /**
         * @param        $field1
         * @param        $field2
         * @param string $compare
         * @param string $table_alias1
         * @param string $table_alias2
         *
         * @return SQL_WHERE_ELEMENT
         */
        public function CreateJoinOn($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = ''){
            return new SQL_WHERE_ELEMENT($field1,$field2,$compare,$table_alias1,$table_alias2,1);
        }
        //endregion
        //region Between Where
        /**
         * @param        $target
         * @param        $field1
         * @param        $field2
         * @param string $target_alias1
         * @param string $table_alias1
         * @param string $table_alias2
         * @param array  $binds
         */
        public function WhereBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array()){
            $this->Where[] =  new SQL_WHERE_ELEMENT($field1,$field2,'BETWEEN',$table_alias1,$table_alias2,0,'',array($target,$target_alias1));
            foreach($binds AS $k=>$bind){
                $this->Binds[trim($k)] = trim($bind);
            }
        }

        /**
         * @param        $target
         * @param        $field1
         * @param        $field2
         * @param string $target_alias1
         * @param string $table_alias1
         * @param string $table_alias2
         * @param array  $binds
         */
        public function WhereAndBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array()){
            $this->Where[] =  new SQL_WHERE_ELEMENT($field1,$field2,'BETWEEN',$table_alias1,$table_alias2,0,'AND',array($target,$target_alias1));
            foreach($binds AS $k=>$bind){
                $this->Binds[trim($k)] = trim($bind);
            }
        }

        /**
         * @param        $target
         * @param        $field1
         * @param        $field2
         * @param string $target_alias1
         * @param string $table_alias1
         * @param string $table_alias2
         * @param array  $binds
         */
        public function WhereOrBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array()){
            $this->Where[] =  new SQL_WHERE_ELEMENT($field1,$field2,'BETWEEN',$table_alias1,$table_alias2,0,'OR',array($target,$target_alias1));
            foreach($binds AS $k=>$bind){
                $this->Binds[trim($k)] = trim($bind);
            }
        }
        //endregion
        //region Where
        /**
         * @param        $field1
         * @param        $compare
         * @param        $field2
         * @param string $table_alias1
         * @param string $table_alias2
         * @param array  $binds
         *
         * @return SQLBuilder
         */
        public function Where($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array(), $func = null, $args = array()){
            $this->Where[] =  new SQL_WHERE_ELEMENT($field1,$field2,$compare,$table_alias1,$table_alias2, 0, '', $args,$func);
            foreach($binds AS $k=>$bind){
                $this->Binds[trim($k)] = trim($bind);
            }

            return $this;
        }

        /**
         * @param        $field1
         * @param        $compare
         * @param        $field2
         * @param string $table_alias1
         * @param string $table_alias2
         *
         * @return SQL_WHERE_ELEMENT
         */
        public function CreateWhere($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = ''){
            return new SQL_WHERE_ELEMENT($field1,$field2,$compare,$table_alias1,$table_alias2);
        }

        /**
         * @param string $join
         * @param bool   $store
         *
         * @return SQL_WHERE_ELEMENT
         */
        public function CreateWhereJoin($join = 'AND', $store = false){
            if($store) {
                $this->Where[] = new SQL_WHERE_ELEMENT('', '', '', '', '', 0, $join);
            }
            else {
                return new SQL_WHERE_ELEMENT('', '', '', '', '', 0, $join);
            }
        }

        /**
         * @param string $join
         * @param array  $data
         *
         * @return SQLBuilder
         */
        public function WhereSubQuery($join = '', $data = array()){
            if(count($data) > 0) {
                if(!empty(trim($join)) && count($this->Where) > 0){
                    $this->Where[] =  new SQL_WHERE_ELEMENT('','','','','',0,$join);
                }
                $this->WhereBracketLeft();
                foreach($data AS $d){
                    $this->Where[] = $d;
                }
                $this->WhereBracketRight();
            }
            return $this;
        }

        /**
         * @param bool $right_add
         *
         * @return SQLBuilder
         */
        public function WhereBracketLeft($right_add = false) {
            if($right_add === true){
                $this->Where[] =  new SQL_WHERE_ELEMENT('','','','','',0,'AND');
            }
            $this->Where[] =  (new SQL_WHERE_ELEMENT('',''))->setLeftBracket(true);
            return $this;
        }

        /**
         * @return SQLBuilder
         */
        public function WhereBracketRight() {
            $this->Where[] =  (new SQL_WHERE_ELEMENT('',''))->setRightBracket(true);
            return $this;
        }

        /**
         * @param        $field1
         * @param        $compare
         * @param        $field2
         * @param string $table_alias1
         * @param string $table_alias2
         * @param array  $binds
         *
         * @return SQLBuilder
         */
        public function WhereAnd($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array(), $func = null, $args = array(), $args2 = array()) {
            //TODO ADD AND
            $this->Where[] =  new SQL_WHERE_ELEMENT($field1,$field2,$compare,$table_alias1,$table_alias2,0,'AND', $args,$func, $args2);
            if($func != null) {
                foreach ($binds as $k => $bind) {
                    $this->Binds[trim($k)] = trim($bind);
                }
            }

            return $this;
        }

        /**
         * @param        $field1
         * @param        $compare
         * @param        $field2
         * @param string $table_alias1
         * @param string $table_alias2
         * @param array  $binds
         *
         * @return SQLBuilder
         */
        public function WhereOr($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array()){
            //TODO ADD OR
            $this->Where[] =  new SQL_WHERE_ELEMENT($field1,$field2,$compare,$table_alias1,$table_alias2,0,'OR');
            foreach($binds AS $k=>$bind){
                $this->Binds[trim($k)] = trim($bind);
            }

            return $this;
        }
        //endregion
        //region Binds
        /**
         * @param string $key
         * @param $val
         */
        public function Bind($key, $val){
            $this->Binds[trim(str_replace(':','',$key))] = $val ;
        }

        /**
         * @param array $array
         * @param bool $append
         */
        public function LoadBinds(array $array, $append = false){
            if($append == true){
                foreach($array AS $k=>$v){
                    $this->Binds[$k] = $v;
                }
            }else {
                $this->Binds = $array;
            }
        }
        //endregion

        /**
         * @param        $field
         * @param string $table
         */
        public function GroupBy($field, $table = ''){
            $this->GroupByData[] = new SQL_Group_By($table, $field);
        }

        /**
         * @param        $field
         * @param string $table
         * @param null   $sort
         *
         * @throws \ReflectionException
         */
        public function OrderBy($field, $table = '', $sort = null){
            $this->OrderByData[] = new SQL_Order_By($table, $field, $sort);
        }

        /**
         * @param null $offset
         * @param null $count
         */
        public function Limit($offset = null, $count = null){
            $this->Limit = new SQL_LIMIT($offset,$count);
        }

        /**
         * @param $val
         *
         * @return mixed
         */
        public static function EscapeOperator($val){
            //$val = '`'.$val.'`';
            return $val;
        }

        /**
         * @param $type
         *
         * @return string
         */
        public function GetSqlCommand($type){
            if($type instanceof SQL_Type){
                switch($type){
                    case SQL_Type::INSERT:
                    case SQL_Type::INSERT_ON_DUPLICATE:
                        return 'INSERT INTO';
                    case SQL_Type::INSERT_IGNORE: return 'INSERT IGNORE INTO';
                    case SQL_Type::SELECT: return 'SELECT';
                    case SQL_Type::DELETE: return 'DELETE FROM';
                    case SQL_Type::UPDATE: return 'UPDATE';
                }
            }
        }

        /**
         * @param bool $html
         *
         * @return string
         * @throws \ReflectionException
         */
        public function DebugQuery($html=false) {
            $type = SQL_Type::get(strtoupper(trim($this->QueryType)));
            $table = trim($this->Table);

            $sql = array();

            if($html === true) {
                $sql[] = '<br />';
            }

            $sql[] = $this->GetSqlCommand($type);

            if($this->IsInsertQuery($type) || $type == SQL_Type::DELETE) {
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }

            if($html === true) {
                $sql[] = '<br />';
            }

            $bind_done = false;
            if(count($this->Columns) > 0) {
                if($type == SQL_Type::SELECT) {
                    $_sql = array();
                    /** @var SQL_Column $col */
                    foreach ($this->Columns AS $col) {
                        $_sql[] = $col->Output();
                    }
                    if (count($_sql) > 0) {
                        if ($html === true) {
                            $sql[] = implode(',<br />', $_sql);
                        }
                        else {
                            $sql[] = implode(',', $_sql);
                        }

                    }
                }
                elseif($this->IsInsertQuery($type)){
                    $_sql = array();
                    $_sql2 = array();
                    /** @var SQL_Column $col */
                    foreach ($this->Columns AS $col) {
                        $_sql[] = $col->Output();
                        $_sql2[] = $this->Values[":_".$col->getName()]->Output();
                    }
                    if (count($_sql) > 0) {
                        if ($html === true) {
                            $sql[] = '('.implode(',<br />', $_sql).')';
                        }
                        else {
                            $sql[] = '('.implode(',', $_sql).')';
                        }

                    }
                    if(count($_sql2) > 0){
                        $bind_done = true;
                        if ($html === true) {
                            $sql[] = 'VALUES ('.implode(',<br />', $_sql2).')';
                        }
                        else {
                            $sql[] = 'VALUES ('.implode(',', $_sql2).')';
                        }
                    }
                }
            }
            else{
                if(count($this->Joins) > 0) {
                    $sql[] = (!empty(trim($this->TablesAlias)) ? $this->TablesAlias : $table) . '.*';
                }
                else {
                    $sql[] = '*';
                }
            }
            if($html === true) {
                $sql[] = '<br /><br />';
            }

            if($type == SQL_Type::SELECT) {
                $sql[] = 'FROM'; //TODO SWITCH BASSED ON QUERRY_TYPE (SET FROM ..)
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }
            elseif($this->IsInsertQuery($type) && !$bind_done) {
                $sql[] = 'VALUES';
                if(count($this->Values) > 0) {

                    $_sql = array();
                    /** @var SQL_Value $col */
                    foreach ($this->Values AS $val) {
                        $_sql[] = $val->Output();
                    }
                    if (count($_sql) > 0) {
                        if ($html === true) {
                            $sql[] = '('.implode(',<br />', $_sql).')';
                        }
                        else {
                            $sql[] = '('.implode(',', $_sql).')';
                        }
                    }

                }
            }

            if($type == SQL_TYPE::INSERT_ON_DUPLICATE){
                if(count($this->DuplicateKeys) <= 0){
                    foreach ($this->Columns AS $col){
                        $this->DuplicateValue($col->getName());
                    }
                }
                $sql[] = "ON DUPLICATE KEY UPDATE";
                if($html === true) {
                    $sql[] = '<br /><br />';
                }
                $dps = array();
                foreach ($this->DuplicateKeys AS $__dp){
                    $dps[] = $__dp->Output();
                }

                $sql[] = implode(", ".(($html === true) ? "<br />" : ""),$dps);
            }

            //Do NOT Need to do any below if a insert query
            if(!$this->IsInsertQuery($type)) {

                if($html === true) {
                    $sql[] = '<br /><br />';
                }

                if (count($this->Joins) > 0) {
                    $_join = array();
                    foreach ($this->Joins AS $join) {
                        $_join[] = $join->Output();
                    }

                    if (count($_join) > 0) {
                        if ($html === true) {
                            $sql[] = implode('<br />', $_join);
                        }
                        else {
                            $sql[] = implode(' ', $_join);
                        }
                    }
                }

                if (count($this->Where) > 0) {
                    if ($html === true) {
                        $sql[] = '<br /><br />';
                    }
                    $_where = array();
                    foreach ($this->Where AS $where) {
                        $_where[] = $where->Output();
                    }
                    if (count($_where) > 0) {
                        $sql[] = 'WHERE';
                        if ($html === true) {
                            $sql[] = '<br />';
                            $sql[] = implode('<br />', $_where);
                        }
                        else {
                            $sql[] = implode(' ', $_where);
                        }
                    }
                }

                //Do we have a Haveing

                //Group
                if (count($this->GroupByData) > 0) {
                    if ($html === true) {
                        $sql[] = '<br />';
                    }

                    $_grpby = array();
                    foreach ($this->GroupByData AS $grp) {
                        $_grpby[] = $grp->Output();
                    }
                    if (count($_grpby) > 0) {
                        $sql[] = 'GROUP BY ' . implode(', ', $_grpby);
                    }

                }
                if (count($this->OrderByData) > 0) {
                    if ($html === true) {
                        $sql[] = '<br />';
                    }

                    $_grpby = array();
                    foreach ($this->OrderByData AS $grp) {
                        $_grpby[] = $grp->Output();
                    }
                    if (count($_grpby) > 0) {
                        if ($html === true) {
                            $sql[] = '<br />';
                            $sql[] = 'ORDER BY ' . implode(', ', $_grpby);
                        }
                        else {
                            $sql[] = 'ORDER BY ' . implode(', ', $_grpby);
                        }
                    }

                }

                if ($this->Limit !== null && $this->Limit instanceof SQL_LIMIT) {
                    if ($html === true) {
                        $sql[] = '<br />';
                    }

                    $sql[] = $this->Limit->Output();
                }
            }

            return implode(' ', $sql);
        }

        private function IsInsertQuery($type){
            return $type == SQL_Type::INSERT || $type == SQL_Type::INSERT_ON_DUPLICATE || $type == SQL_Type::INSERT_IGNORE;
        }

        /**
         * @return string
         * @throws \ReflectionException
         */
        public function Build() {
            $type = SQL_Type::get(strtoupper(trim($this->QueryType)));
            $table = trim($this->Table);

            $sql = array( $this->GetSqlCommand($type) );

            if($this->IsInsertQuery($type) || $type == SQL_Type::DELETE) {
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }

            $bind_done = false;
            if(count($this->Columns) > 0) {
                if($type == SQL_Type::SELECT) {
                    $_sql = array();
                    /** @var SQL_Column $col */
                    foreach ($this->Columns AS $col) {
                        $_sql[] = $col->Output();
                    }
                    if (count($_sql) > 0) {
                        $sql[] = implode(',', $_sql);
                    }
                }
                elseif($this->IsInsertQuery($type)){
                    $_sql = array();
                    $_sql2 = array();
                    /** @var SQL_Column $col */
                    foreach ($this->Columns AS $col) {
                        $_sql[] = $col->Output();
                        $_sql2[] = $this->Values[":_".$col->getName()]->Output();
                    }
                    if (count($_sql) > 0) {
                        $sql[] = '('.implode(',', $_sql).')';
                    }
                    if(count($_sql2) > 0){
                        $bind_done = true;
                        $sql[] = 'VALUES ('.implode(',', $_sql2).')';
                    }
                }
            }
            else{
                if(count($this->Joins) > 0) {
                    $sql[] = (!empty(trim($this->TablesAlias)) ? $this->TablesAlias : $table) . '.*';
                }
                else {
                    $sql[] = '*';
                }
            }

            if($type == SQL_Type::SELECT) {
                $sql[] = 'FROM'; //TODO SWITCH BASSED ON QUERRY_TYPE (SET FROM ..)
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }
            elseif($this->IsInsertQuery($type) && !$bind_done) {
                $sql[] = 'VALUES';
                if(count($this->Values) > 0) {
                    $_sql = array();
                    /** @var SQL_Value $col */
                    foreach ($this->Values AS $val) {
                        $_sql[] = $val->Output();
                    }
                    if (count($_sql) > 0) {
                        $sql[] = '('.implode(',', $_sql).')';
                    }

                }
            }

            if($type == SQL_TYPE::INSERT_ON_DUPLICATE){
                if(count($this->DuplicateKeys) <= 0){
                    foreach ($this->Columns AS $col){
                        $this->DuplicateValue($col->getName());
                    }
                }
                $sql[] = "ON DUPLICATE KEY UPDATE";
                $dps = array();
                foreach ($this->DuplicateKeys AS $__dp){
                    $dps[] = $__dp->Output();
                }

                $sql[] = implode(", ",$dps);
            }

            //Do NOT Need to do any below if a insert query
            if(!$this->IsInsertQuery($type)) {
                if (count($this->Joins) > 0) {
                    $_join = array();
                    foreach ($this->Joins AS $join) {
                        $_join[] = $join->Output();
                    }

                    if (count($_join) > 0) {
                        $sql[] = implode(' ', $_join);
                    }
                }

                if (count($this->Where) > 0) {
                    $_where = array();
                    foreach ($this->Where AS $where) {
                        $_where[] = $where->Output();
                    }
                    if (count($_where) > 0) {
                        $sql[] = 'WHERE';
                        $sql[] = implode(' ', $_where);
                    }
                }


                //Do we have a Haveing
                //TODO

                //Group
                if (count($this->GroupByData) > 0) {
                    $_grpby = array();
                    foreach ($this->GroupByData AS $grp) {
                        $_grpby[] = $grp->Output();
                    }
                    if (count($_grpby) > 0) {
                        $sql[] = 'GROUP BY ' . implode(', ', $_grpby);
                    }

                }
                if (count($this->OrderByData) > 0) {
                    $_grpby = array();
                    foreach ($this->OrderByData AS $grp) {
                        $_grpby[] = $grp->Output();
                    }
                    if (count($_grpby) > 0) {
                        $sql[] = 'ORDER BY ' . implode(', ', $_grpby);
                    }

                }
                if ($this->Limit !== null && $this->Limit instanceof SQL_LIMIT) {
                    $sql[] = $this->Limit->Output();
                }
            }
            //TODO
            return implode(' ', $sql);
        }

        /*** @return array */
        public function getBinds() { return $this->Binds; }

        /*** @return SQL_Type|string|static */
        public function getQueryType() { return $this->QueryType; }
    }

    /**
     * Class SQL_LIMIT
     * @package Database
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
            }

            return '';
        }
    }

    /**
     * Class SQL_Order_By
     * @package Database
     */
    class SQL_Order_By{
        /** @var string */
        private $Table    = '';
        /** @var string */
        private $Field    = '';
        /** @var SQL_SORT_TYPE|string */
        private $SortType = SQL_SORT_TYPE::ASC;

        /**
         * SQL_Order_By constructor.
         *
         * @param string $table
         * @param string $field
         * @param string $sort
         *
         * @throws \ReflectionException
         */
        public function __construct($table = '', $field = '', $sort = SQL_SORT_TYPE::ASC) {
            $this->Table = $table;
            $this->Field = $field;
            if($sort !== null){
                $this->SortType = ($sort instanceof SQL_SORT_TYPE ? $sort : SQL_SORT_TYPE::get(strtoupper(trim($sort))));
            }
        }

        /**
         * @return string
         * @throws \ReflectionException
         */
        public function Output() {
            $data = array();
            $data[] = (!empty(trim($this->Table)) ? trim($this->Table). '.' : '') . $this->Field;
            $data[] = SQL_SORT_TYPE::get(strtoupper(trim($this->SortType)))->getName();

            return implode(' ',$data);
        }
    }

    /**
     * Class SQL_Group_By
     * @package Database
     */
    class SQL_Group_By{
        /** @var string */
        private $Table;
        /** @var string */
        private $Field;

        /**
         * SQL_Group_By constructor.
         *
         * @param string $table
         * @param string $field
         */
        public function __construct($table = '', $field = '') {
            $this->Table = $table;
            $this->Field = $field;
        }

        /** @return string */
        public function Output() {
            $data = array();
            $data[] = (!empty(trim($this->Table)) ? trim($this->Table). '.' : '') . $this->Field;
            return implode(' ',$data);
        }
    }

    /**
     * Class SQL_SORT_TYPE
     * @package Database
     */
    class SQL_SORT_TYPE extends Enum{
        /** @var string */
        CONST ASC  = 'ASC';
        /** @var string */
        CONST DESC = 'DESC';
    }

    /**
     * Class SQL_Join
     * @package Database
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

    /**
     * Class SQL_WHERE_ELEMENT
     * @package Database
     */
    class SQL_WHERE_ELEMENT{
        /** @var string */
        private $Table1Alias;
        /** @var string */
        private $Field1Alias;
        /** @var int */
        public $Type = 0;

        /** @var string */
        private $Compare;

        /** @var string */
        private $Table2Alias;
        /** @var string */
        private $Field2Alias;

        /** @var string */
        private $JoinType     = '';
        /** @var array */
        private $Args         = Array();
        /** @var array */
        private $Args2         = Array();
        /** @var bool */
        private $RightBracket = false;
        /** @var bool */
        private $LeftBracket = false;

        private $Function = null;

        /**
         * SQL_WHERE_ELEMENT constructor.
         *
         * @param string $field1
         * @param string $field2
         * @param string $compare
         * @param string $table_alias1
         * @param string $table_alias2
         * @param int    $type
         * @param string $Join_type
         * @param array  $args
         */
        public function __construct($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = '', $type = 0, $Join_type = '', $args = array(), $func = null, $args2 = null) {
            $this->Type = (int)$type;
            $this->Field1Alias = trim($field1);
            $this->Field2Alias = trim($field2);

            $this->Compare = trim($compare);

            $this->Table1Alias = trim($table_alias1);
            $this->Table2Alias = trim($table_alias2);
            $this->JoinType = strtoupper(trim($Join_type));
            $this->Args = $args;
            $this->Args2 = is_array($args2) ? $args2 : [$args2];
            if($func !== null) {
                $this->Function = ($func instanceof SQL_FUNCTION ? $func : SQL_FUNCTION::get(strtoupper(trim($func))));
            }
        }

        /** @return string */
        public function Output(){
            $data = array();
            if(!empty($this->Function) && $this->Function instanceof SQL_FUNCTION){
                if ($this->Type === 0) {
                    if (!empty($this->JoinType)) {
                        $data[] = $this->JoinType;
                    }
                }
                elseif ($this->Type === 1) {
                    if (empty($this->JoinType)) {
                        $data[] = 'ON';
                    }
                    else {
                        $data[] = $this->JoinType;
                    }
                }
                else {
                    if (!empty($this->JoinType)) {
                        $data[] = $this->JoinType;
                    }
                }
                $func = 'Get'.SQL_FUNCTION::get($this->Function)->getName();
                $field = !empty($this->Table1Alias) ? trim($this->Table1Alias) . '.' . trim($this->Field1Alias) : trim($this->Field1Alias);;
                $val = isset($this->Args) ? is_array($this->Args) ? (isset($this->Args[0]) ? $this->Args[0] : '') : $this->Args : '';
                if($this->Function->is(SQL_FUNCTION::SQL_IF)) {
                    $data[] = "(";
                    $data[] = "(";
                    //IF(compelete == 0, IF(process == 0)
                    $vals = array();
                    $join = "AND";
                    foreach($this->Args AS $arg){
                        if($arg !== null && $arg instanceof self){
                            var_dump("self");
                            $vals[] = $arg->Output();
                        }
                        elseif($arg !==null && $arg instanceof SQL_IF_Data) {
                            $vals[] = $arg->Output();
                            $join = $arg->getJoinType();
                        }
                        else {
                            $vals[] = $arg;
                        }
                    }
                    $val = implode(" {$join} ",$vals);
                    if(isset($this->Args2[0]) && !empty($this->Args2[0])) {
                        $val .= " ,{$this->Args2[0]}";
                    }
                    else {
                        $val .= " ,1,0";
                    }
                }
                $data[] = str_replace(array('{ARG}', '{FIELD}'), array($val, $field), SQL_FUNCTION::$func());
                $data[] = ")";
                $data[] = " {$this->Compare} ";
                $data[] = (!empty(trim($this->Table2Alias)) ? ($this->Table2Alias) . '.' : '') . $this->Field2Alias;
                $data[] = ")";
            }else
            {
                if (!empty($this->Field1Alias) || !empty($this->Field2Alias)) {
                    if ($this->Type === 0) {
                        if (!empty($this->JoinType)) {
                            $data[] = $this->JoinType;
                        }
                    }
                    elseif ($this->Type === 1) {
                        if (empty($this->JoinType)) {
                            $data[] = 'ON';
                        }
                        else {
                            $data[] = $this->JoinType;
                        }
                    }
                    else {
                        if (!empty($this->JoinType)) {
                            $data[] = $this->JoinType;
                        }
                    }

                    if ($this->LeftBracket) {
                        $data[] = '(';
                    }

                    if (strtolower(trim($this->Compare)) === 'find_in_set') {
                        $_i = 'FIND_IN_SET(';
                        $_i .= (!empty($this->Table1Alias) ? $this->Table1Alias . '.' : '');
                        $_i .= $this->Field1Alias;
                        $_i .= ', ';
                        $_i .= (!empty($this->Table2Alias) ? $this->Table2Alias . '.' : '');
                        $_i .= $this->Field2Alias;
                        $_i .= ')';

                        $data[] = $_i;
                    }
                    elseif (strtolower(trim($this->Compare)) === 'in') {
                        $data[] = (!empty(trim($this->Table1Alias)) ? trim($this->Table1Alias) . '.' : '') . $this->Field1Alias;
                        $data[] = " {$this->Compare} (";
                        $data[] = (!empty(trim($this->Table2Alias)) ? trim($this->Table2Alias) . '.' : '') . $this->Field2Alias;
                        $data[] = ')';
                    }
                    elseif (strtolower(trim($this->Compare)) === 'between') {
                        $_i[] = isset($this->Args[1]) && !empty(trim($this->Args[1])) ? trim($this->Args[0]) . '.' : '';
                        $_i[] = trim($this->Args[0]);
                        $_i[] = trim($this->Compare);
                        $_i[] = (!empty($this->Table1Alias) ? $this->Table1Alias . '.' : '');
                        $_i[] = $this->Field1Alias;
                        $_i[] = 'AND';
                        $_i[] = (!empty($this->Table2Alias) ? $this->Table2Alias . '.' : '');
                        $_i[] = $this->Field2Alias;
                        $_i[] = '';

                        $data[] = implode(' ', $_i);
                    }
                    else {
                        $data[] = (!empty(trim($this->Table1Alias)) ? trim($this->Table1Alias) . '.' : '') . $this->Field1Alias;
                        $data[] = " {$this->Compare} ";
                        $data[] = (!empty(trim($this->Table2Alias)) ? ($this->Table2Alias) . '.' : '') . $this->Field2Alias;
                    }

                    if ($this->RightBracket)
                        $data[] = ')';
                }
                else {
                    if (!empty($this->JoinType)) {
                        $data[] = $this->JoinType;
                    }

                    if ($this->LeftBracket) {
                        $data[] = '(';
                    }
                    if ($this->RightBracket) {
                        $data[] = ')';
                    }
                }
            }
            return implode(' ', array_filter($data, 'strlen'));
        }

        /*** @return bool */
        public function isRightBracket() { return $this->RightBracket; }

        /**
         * @param bool $RightBracket
         * @return SQL_WHERE_ELEMENT
         */
        public function setRightBracket($RightBracket) { $this->RightBracket = $RightBracket; return $this; }

        /*** @return bool */
        public function isLeftBracket() { return $this->LeftBracket; }

        /**
         * @param bool $LeftBracket
         * @return SQL_WHERE_ELEMENT
         */
        public function setLeftBracket($LeftBracket) { $this->LeftBracket = $LeftBracket; return $this; }
    }

    /**
     * Class SQL_Column
     * @package Database
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
         * @throws \ReflectionException
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
                $func = 'Get'.SQL_FUNCTION::get($this->Function)->getName();
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

    /**
     * Class SQL_Value
     * @package Database
     */
    class SQL_Value{
        /** @var string */
        private $Name;
        /**
         * @var null|SQL_FUNCTION
         */
        private $SqlFunction = null;

        /**
         * SQL_Value constructor.
         *
         * @param string $name
         */
        public function __construct($name = '', $_sqlFunc = null) {
            $this->Name = trim($name);
            $this->SqlFunction = $_sqlFunc;
        }

        /** @return string */
        public function Output() {
            if($this->SqlFunction != null){
                if($this->SqlFunction->is(SQL_FUNCTION::DATE_NOW)){
                    $func = 'Get'.SQL_FUNCTION::get($this->SqlFunction)->getName();
                    return $this->SqlFunction->$func();
                }

            }

            return $this->getName();
        }

        /*** @return string */
        public function getName()  { return $this->Name; }
    }

    class SQL_DuplicateValue{
        /** @var string */
        private $Name;
        private $CustomContent;
        /**
         * @var null|SQL_FUNCTION
         */
        private $SqlFunction = null;

        /**
         * SQL_Value constructor.
         *
         * @param string $name
         */
        public function __construct($name = '', $CustomContent = null, $_sqlFunc = null) {
            $this->Name = trim($name);
            $this->CustomContent = trim($CustomContent);
            $this->SqlFunction = $_sqlFunc;
        }

        /** @return string */
        public function Output()
        {
            $out[] = "{$this->getName()} = ";
            if($this->SqlFunction != null){
                if($this->SqlFunction->is(SQL_FUNCTION::DATE_NOW)){
                    $func = 'Get'.SQL_FUNCTION::get($this->SqlFunction)->getName();
                    $out[] = $this->SqlFunction->$func();
                }
            }elseif($this->CustomContent != null){
                $out[] = $this->getHash();
            }else{
                $out[] = "VALUES({$this->getName()})";
            }

            return implode(" ",$out);
        }

        public function getHash(){
            return ":_".hash("crc32","{$this->Name}::ONDUPDATE");
        }

        /*** @return string */
        public function getName()  { return $this->Name; }
    }

    /**
     * Class SQL_Type
     * @package Database
     */
    class SQL_Type extends Enum {
        /** @var string */
        CONST SELECT = 'SELECT';
        /** @var string */
        CONST INSERT = 'INSERT';
        CONST INSERT_IGNORE = 'INSERT_IGNORE';
        CONST INSERT_ON_DUPLICATE = 'INSERT_ON_DUPLICATE';
        /** @var string */
        CONST DELETE = 'DELETE';
        /** @var string */
        CONST SHOW   = 'SHOW';
        /** @var string */
        CONST UPDATE = 'UPDATE';
    }


    /**
     * Class SQL_JOIN_TYPE
     * @package Database
     */
    class SQL_JOIN_TYPE extends Enum{
        /** @var string */
        const LEFT  = 'LEFT';
        /** @var string */
        const RIGHT = 'RIGHT';
        /** @var string */
        const INNER = 'INNER';
        /** @var string */
        const OUTER = 'OUTER';
    }

    /**
     * Class SQL_FUNCTION
     * @package Database
     */
    class SQL_FUNCTION extends  Enum{
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