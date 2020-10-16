<?php
    /**
     * User: Jason Townsend
     * Date: 07/09/2018
     * Time: 12:53
     */

    namespace AmaranthNetwork\Database\Builder;

    use AmaranthNetwork\Enums\Enum;

    /**
     * Class SQLBuilder
     *
     * @package AmaranthNetwork\Database\Builder
     */
    class SQLBuilder
    {
        //region Fields
        /** @var SQL_Column[] $Columns */
        private $Columns = array();
        /** @var SQLBuilder[] $Columns */
        private $SubColumns = array();
        /** @var SQL_UpdateField[] $Columns */
        private $UpdateFields = array();
        /** @var SQL_Value[] $Columns */
        private $Values = array();

        /** @var SQL_Value[][] $Columns */
        private $BatchValues = array();

        /** @var SQL_Join[] $Joins * */
        private $Joins = array();
        /** @var SQL_WHERE_ELEMENT[] $Joins * */
        private $Where = array();
        /** @var string */
        private $Table;
        /** @var string */
        private $TablesAlias;
        /** @var SQL_Type|string|null */
        private $QueryType = SQL_Type::SELECT;
        /** @var array */
        private $Binds = array();
        /** @var SQL_Having[] $Having */
        private $HavingData = array();
        /** @var SQL_LIMIT $Limit */
        private $Limit;
        /** @var array */
        private $GroupByData = array();
        /** @var SQL_Order_By[] */
        private $OrderByData = array();
        /** @var SQL_Order_By[] */
        private $OrderByData2 = array();
        /*** @var SQL_DuplicateValue[] */
        private $DuplicateKeys = array();
        //endregion

        /*** @return bool */
        public function HasBinds() { return (count($this->Binds) > 0); }

        /**
         * @param string $Table
         */
        public function setTable($Table) { $this->Table = $Table; }

        /**
         * SQLBuilder constructor.
         *
         * @param string        $table
         * @param string        $tables_alias
         * @param SQL_Type|null $type
         *
         */
        public function __construct($table = '', $tables_alias = '', $type = null)
        {
            $this->Table       = trim($table);
            $this->TablesAlias = trim($tables_alias);
            if ($type !== null) {
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
         */
        public function Column($Name, $Alias = '', $tableAlias = '', $function = null, $args = array(), $args2 = array())
        {
            $this->Columns[] = new SQL_Column($Name, $Alias, $tableAlias, $function, $args, $args2);
            return $this;
        }

        /**
         * @param SQLBuilder $builder
         * @param string     $alias
         */
        public function SubColumn($builder, $alias = ""){
            $this->SubColumns[] = Array("obj"=>$builder, "alias"=> $alias);
        }

        public function UpdateField($Name, $value, $func = null){
            $this->UpdateFields[] = new SQL_UpdateField($Name, $value,$func);
            return $this;
        }

        public function UpdateFieldBind($Name, $value, $bind = null){
            $bind_name = ":_{$Name}";
            if($bind != null){
                $bind_name = ":_{$value}";
            }
            $this->UpdateFields[] = new SQL_UpdateField($Name, $bind_name,null);

            if($bind == null) {
                if (!empty($bind_name) && !empty($bind_name) && $bind_name !== ":_")
                    $this->Bind($bind_name, $value);
            }else{
                $this->Bind($bind_name, $bind);
            }

            return $this;
        }

        public function UpdateFieldFunc($Name, $func, $args = ""){
            if($func !== null) {
                $this->UpdateFields[] = new SQL_UpdateField($Name, $args, $func);
            }

            return $this;
        }

        public function UpdateFieldValue($Name, $value, $func = null){
            $bind_name = $value;

            if($func == null)
                $bind_name = ":_{$Name}";

            $this->UpdateFields[] = new SQL_UpdateField($Name, $bind_name, $func);

            if(!empty($bind_name) && !empty($bind_name) && $bind_name !== ":_")
                $this->Bind($bind_name, $value);

            return $this;
        }

        /**
         * @param array $array
         *
         * @return SQLBuilder
         */
        public function Columns($array)
        {
            foreach ($array as $ar) {
                $this->Columns[] = new SQL_Column(
                    $ar['name'],
                    (isset($ar['alias']) ? $ar['alias'] : ''),
                    (isset($ar['table_alias']) ? $ar['table_alias'] : ''),
                    isset($ar['function']) ? $ar['function'] : null, isset($ar['args']) ? $ar['args'] : array());
                if(isset($ar['binds'])){
                    //bind_function
                    $this->Value(":_{$ar['name']}",$ar['binds'], isset($ar['bind_function']) ? $ar['bind_function'] : null);
                }elseif(isset($ar['bind_function'])){
                    $this->Value(":_{$ar['name']}","", isset($ar['bind_function']) ? $ar['bind_function'] : null);
                }
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
         */
        public function CreateColumn($Name, $Alias, $tableAlias = '', $function = null, $args = array())
        {
            return new SQL_Column($Name, $Alias, $tableAlias, $function, $args);
        }
        //endregion
        #region Values
        /**
         * @param string $name
         * @param null   $bind
         *
         * @return SQLBuilder
         */
        public function Value($name, $bind = null, $function = null)
        {
            if ($function != null) {
                $this->Values[$name] = new SQL_Value($name, $function instanceof SQL_FUNCTION ? $function : SQL_FUNCTION::get(strtoupper(trim($function))));
            }
            else {
                $this->Values[$name] = new SQL_Value($name);
            }
            if ($bind !== null) {
                $this->Binds[trim(str_replace(':', '', $name))] = trim($bind);
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
        public function DuplicateValue($name, $custom_content = null, $function = null)
        {
            if ($function != null) {
                $function = $function instanceof SQL_FUNCTION ? $function : SQL_FUNCTION::get(strtoupper(trim($function)));
            }
            $dv                                  = new SQL_DuplicateValue($name, $custom_content, $function);
            $this->DuplicateKeys[$dv->getHash()] = $dv;
            if ($custom_content !== null) {
                $this->Binds[trim(str_replace(':', '', $dv->getName()))] = trim($custom_content);
            }
            return $this;
        }

        /**
         * @param array $array
         *
         * @return SQLBuilder
         */
        public function Values($array)
        {
            foreach ($array as $ar) {
                if (isset($ar['function'])) {
                    unset($ar['binds']);
                }
                if (isset($ar['function']) && $ar['function'] != null) {
                    $this->Values[$ar['name']] = new SQL_Value($ar['name'], $ar['function'] instanceof SQL_FUNCTION ? $ar['function'] : SQL_FUNCTION::get(strtoupper(trim($ar['function']))));
                }
                else {
                    $this->Values[$ar['name']] = new SQL_Value($ar['name']);
                }

                if (isset($ar['binds']) && $ar['binds'] !== null) {
                    $this->Binds[trim(str_replace(':', '', $ar['name']))] = trim($ar['binds']);
                }
                /*$this->Values[$ar['name']] = new SQL_Value($ar['name']);
                if($ar['binds'] !== null){
                    $this->Binds[trim(str_replace(':','',$ar['name']))] = trim($ar['binds']);
                }*/
            }

            return $this;
        }

        /**
         * @param array $array
         *
         * @return SQLBuilder
         */
        public function BatchValues($array)
        {
            $count = count($this->BatchValues) + 1;

            foreach ($array as $ar) {
                $bind_name = trim($ar['name'])."_{$count}";
                if (isset($ar['function'])) {
                    unset($ar['binds']);
                }
                if (isset($ar['function']) && $ar['function'] != null) {
                    $this->BatchValues[$count][$ar['name']] = new SQL_Value($ar['name'], $ar['function'] instanceof SQL_FUNCTION ? $ar['function'] : SQL_FUNCTION::get(strtoupper(trim($ar['function']))),$count);
                }
                else {
                    $this->BatchValues[$count][$ar['name']] = new SQL_Value($ar['name'],null, $count);
                }

                if (isset($ar['binds']) && $ar['binds'] !== null) {
                    $this->Binds[trim(str_replace(':', '', $bind_name))] = trim($ar['binds']);
                }
                /*$this->BatchValues[$count][$ar['name']] = new SQL_Value($ar['name']);
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
         */
        public function Join($table, $table_alias, $on, $type = null)
        {
            $this->Joins[] = new SQL_Join($table, $table_alias, $on, $type);
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
        public function CreateOrJoinOn($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = '')
        {
            return new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2, 1, 'OR');
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
        public function CreateAndJoinOn($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = '')
        {
            return new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2, 1, 'AND');
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
        public function CreateJoinOn($field1, $field2, $compare = '=', $table_alias1 = '', $table_alias2 = '')
        {
            return new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2, 1);
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
        public function WhereBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            $this->Where[] = new SQL_WHERE_ELEMENT($field1, $field2, 'BETWEEN', $table_alias1, $table_alias2, 0, '', array($target, $target_alias1));
            foreach ($binds as $k => $bind) {
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
        public function WhereAndBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            $this->Where[] = new SQL_WHERE_ELEMENT($field1, $field2, 'BETWEEN', $table_alias1, $table_alias2, 0, 'AND', array($target, $target_alias1));
            foreach ($binds as $k => $bind) {
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
        public function WhereOrBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            $this->Where[] = new SQL_WHERE_ELEMENT($field1, $field2, 'BETWEEN', $table_alias1, $table_alias2, 0, 'OR', array($target, $target_alias1));
            foreach ($binds as $k => $bind) {
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
        public function Where($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array(), $func = null, $args = array())
        {
            $this->Where[] = new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2, 0, '', $args, $func);
            foreach ($binds as $k => $bind) {
                $this->Binds[trim($k)] = trim($bind);
            }

            return $this;
        }

        public function WhereFirstOrWhereOr($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array(), $func = null, $args = array())
        {
            if(empty($this->Where)) {
                $this->Where[] = new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2, 0, '', $args, $func);
                foreach ($binds as $k => $bind) {
                    $this->Binds[trim($k)] = trim($bind);
                }
            }else{

                $this->WhereOr($field1, $compare, $field2, $table_alias1, $table_alias2, $binds);
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
        public function CreateWhere($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '')
        {
            return new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2);
        }

        /**
         * @param string $join
         * @param bool   $store
         *
         * @return SQL_WHERE_ELEMENT
         */
        public function CreateWhereJoin($join = 'AND', $store = false)
        {
            if ($store) {
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
        public function WhereSubQuery($join = '', $data = array())
        {
            if (count($data) > 0) {
                if (!empty(trim($join)) && count($this->Where) > 0) {
                    $this->Where[] = new SQL_WHERE_ELEMENT('', '', '', '', '', 0, $join);
                }
                $this->WhereBracketLeft();
                foreach ($data as $d) {
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
        public function WhereBracketLeft($right_add = false)
        {
            if ($right_add === true) {
                $this->Where[] = new SQL_WHERE_ELEMENT('', '', '', '', '', 0, 'AND');
            }
            $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setLeftBracket(true);
            return $this;
        }

        /**
         * @param string $type ("l" = Left Bracket, "r" = Right bracket, "la" = left 'AND', "ra" = right 'AND', "lo" = left 'OR', "ro" right 'OR')
         *
         * @return SQLBuilder
         */
        public function WhereBracket( $type = '')
        {
            $type = strtolower(trim($type));
            switch($type){
                case "l": $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setLeftBracket(true); break;
                case "r": $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setRightBracket(true); break;
                case "ra":
                    $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setRightBracket(true);
                    $this->Where[] = new SQL_WHERE_ELEMENT('', '', '', '', '', 0, 'AND');
                    break;
                case "ro":
                    $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setRightBracket(true);
                    $this->Where[] = new SQL_WHERE_ELEMENT('', '', '', '', '', 0, 'OR');
                    break;
                case "la":
                    $this->Where[] = new SQL_WHERE_ELEMENT('', '', '', '', '', 0, 'AND');
                    $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setLeftBracket(true);
                    break;
                case "lo":
                    $this->Where[] = new SQL_WHERE_ELEMENT('', '', '', '', '', 0, 'OR');
                    $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setLeftBracket(true);
                    break;
                default:
                    (new SQL_WHERE_ELEMENT('', ''))->setLeftBracket(true);
            }
            return $this;
        }

        /**
         * @return SQLBuilder
         */
        public function WhereBracketRight()
        {
            $this->Where[] = (new SQL_WHERE_ELEMENT('', ''))->setRightBracket(true);
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
        public function WhereAnd($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array(), $func = null, $args = array(), $args2 = array())
        {
            //TODO ADD AND
            $this->Where[] = new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2, 0, 'AND', $args, $func, $args2);
            if ($func != null) {
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
        public function WhereOr($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            //TODO ADD OR
            $this->Where[] = new SQL_WHERE_ELEMENT($field1, $field2, $compare, $table_alias1, $table_alias2, 0, 'OR');
            foreach ($binds as $k => $bind) {
                $this->Binds[trim($k)] = trim($bind);
            }

            return $this;
        }
        //endregion
        //region Binds
        /**
         * @param string $key
         * @param        $val
         */
        public function Bind($key, $val)
        {
            $this->Binds[trim(str_replace(':', '', $key))] = $val;
            return $this;
        }

        /**
         * @param array $array
         * @param bool  $append
         */
        public function LoadBinds(array $array, $append = false)
        {
            if ($append == true) {
                foreach ($array as $k => $v) {
                    $this->Binds[$k] = $v;
                }
            }
            else {
                $this->Binds = $array;
            }
            return $this;
        }
        //endregion

        #region having
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
        public function Having($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array(), $func = null, $args = array())
        {
            $this->HavingData[] = new SQL_Having($field1, $field2, $compare, $table_alias1, $table_alias2, 0, '', $args, $func);
            foreach ($binds as $k => $bind) {
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
         * @param array  $binds
         *
         * @return SQLBuilder
         */
        public function HavingAnd($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array(), $func = null, $args = array(), $args2 = array())
        {
            //TODO ADD AND
            $this->HavingData[] = new SQL_Having($field1, $field2, $compare, $table_alias1, $table_alias2, 0, 'AND', $args, $func, $args2);
            if ($func != null) {
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
        public function HavingOr($field1, $compare, $field2, $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            //TODO ADD OR
            $this->HavingData[] = new SQL_Having($field1, $field2, $compare, $table_alias1, $table_alias2, 0, 'OR');
            foreach ($binds as $k => $bind) {
                $this->Binds[trim($k)] = trim($bind);
            }

            return $this;
        }

        public function HavingBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            $this->HavingData[] = new SQL_Having($field1, $field2, 'BETWEEN', $table_alias1, $table_alias2, 0, '', array($target, $target_alias1));
            foreach ($binds as $k => $bind) {
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
        public function HavingAndBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            $this->HavingData[] = new SQL_Having($field1, $field2, 'BETWEEN', $table_alias1, $table_alias2, 0, 'AND', array($target, $target_alias1));
            foreach ($binds as $k => $bind) {
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
        public function HavingOrBetween($target, $field1, $field2, $target_alias1 = '', $table_alias1 = '', $table_alias2 = '', $binds = array())
        {
            $this->HavingData[] = new SQL_Having($field1, $field2, 'BETWEEN', $table_alias1, $table_alias2, 0, 'OR', array($target, $target_alias1));
            foreach ($binds as $k => $bind) {
                $this->Binds[trim($k)] = trim($bind);
            }
        }
        #endregion

        /**
         * @param        $field
         * @param string $table
         */
        public function GroupBy($field, $table = '')
        {
            $this->GroupByData[] = new SQL_Group_By($table, $field);
            return $this;
        }

        /**
         * @param                    $field
         * @param string             $table
         * @param null|SQL_SORT_TYPE $sort
         *
         */
        public function OrderBy($field, $table = '', $sort = null)
        {
            $this->OrderByData[] = new SQL_Order_By($table, $field, $sort);
            return $this;
        }

        public function OrderByQuery($field, $compare, $field2)
        {

            $this->OrderByData2[] = (new SQL_Order_By("", $field, null))->SetQueryWhere($field, $compare, $field2);
            return $this;
        }

        public function OrderByQuerySort($sort = null)
        {
            $this->OrderByData2[] = (new SQL_Order_By("", "", $sort))->SetQueryWhereSort($sort);
            return $this;
        }

        /**
         * @param string $type ("l" = Left Bracket, "r" = Right bracket)
         * @param string $join_type (e.g '+')
         *
         * @return SQLBuilder
         */
        public function OrderByBracket($type = '', $join_type = '')
        {
            switch($type){
                case "l":
                    $this->OrderByData2[] = (new SQL_Order_By("", "", null,$join_type))->setLeftBracket(true);
                break;
                case "r":
                    $this->OrderByData2[] = (new SQL_Order_By("", "", null,$join_type))->setRightBracket(true);
                break;
            }
            $this->OrderByData2[] = (new SQL_Order_By("", "", null));
            return $this;
        }

        /**
         * @param null $offset
         * @param null $count
         */
        public function Limit($offset = null, $count = null)
        {
            $this->Limit = new SQL_LIMIT($offset, $count);
            return $this;
        }

        /**
         * @param $val
         *
         * @return mixed
         */
        public static function EscapeOperator($val)
        {
            //$val = '`'.$val.'`';
            return $val;
        }

        /**
         * @param $type
         *
         * @return string
         */
        public function GetSqlCommand($type)
        {
            if ($type instanceof SQL_Type) {
                switch ($type) {
                    case SQL_Type::INSERT:
                    case SQL_Type::INSERT_ON_DUPLICATE:
                        return 'INSERT INTO';
                    case SQL_Type::INSERT_IGNORE:
                        return 'INSERT IGNORE INTO';
                    case SQL_Type::SELECT:
                        return 'SELECT';
                    case SQL_Type::DELETE:
                        return 'DELETE FROM';
                    case SQL_Type::UPDATE:
                        return 'UPDATE';
                }
            }
        }

        /**
         * @param bool $html
         *
         * @return string
         */
        public function DebugQuery($html = false)
        {
            $type  = SQL_Type::get(strtoupper(trim($this->QueryType)));
            $table = trim($this->Table);

            $sql = array();

            if ($html === true) {
                $sql[] = '<br />';
            }

            $sql[] = $this->GetSqlCommand($type);

            if ($this->IsInsertQuery($type) || $type == SQL_Type::DELETE) {
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }

            if ($html === true) {
                $sql[] = '<br />';
            }

            $bind_done = false;
            if (count($this->Columns) > 0) {
                if ($type == SQL_Type::SELECT) {
                    $_sql = array();
                    /** @var SQL_Column $col */
                    foreach ($this->Columns as $col) {
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
                elseif ($this->IsInsertQuery($type)) {
                    $_sql  = array();
                    $_sql2 = array();
                    /** @var SQL_Column $col */
                    foreach ($this->Columns as $col) {
                        if(isset($this->Values[":_" . $col->getName()])) {
                            $_sql[]  = $col->Output();
                            $_sql2[] = $this->Values[":_" . $col->getName()]->Output();
                        }
                    }
                    if (count($_sql) > 0) {
                        if ($html === true) {
                            $sql[] = '(' . implode(',<br />', $_sql) . ')';
                        }
                        else {
                            $sql[] = '(' . implode(',', $_sql) . ')';
                        }
                    }
                    if (count($_sql2) > 0) {
                        $bind_done = true;
                        if ($html === true) {
                            $sql[] = 'VALUES (' . implode(',<br />', $_sql2) . ')';
                        }
                        else {
                            $sql[] = 'VALUES (' . implode(',', $_sql2) . ')';
                        }
                    }
                }
            }
            else {
                if (count($this->Joins) > 0) {
                    $sql[] = (!empty(trim($this->TablesAlias)) ? $this->TablesAlias : $table) . '.*';
                }
                else {
                    $sql[] = '*';
                }
            }
            if ($html === true) {
                $sql[] = '<br /><br />';
            }

            if ($type == SQL_Type::SELECT) {
                $sql[] = 'FROM'; //TODO SWITCH BASSED ON QUERRY_TYPE (SET FROM ..)
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }
            elseif ($this->IsInsertQuery($type) && !$bind_done) {
                $sql[] = 'VALUES';
                if (count($this->Values) > 0) {
                    $_sql = array();
                    /** @var SQL_Value $col */
                    foreach ($this->Values as $val) {
                        $_sql[] = $val->Output();
                    }
                    if (count($_sql) > 0) {
                        if ($html === true) {
                            $sql[] = '(' . implode(',<br />', $_sql) . ')';
                        }
                        else {
                            $sql[] = '(' . implode(',', $_sql) . ')';
                        }
                    }
                }
            }

            if ($type == SQL_TYPE::INSERT_ON_DUPLICATE) {
                if (count($this->DuplicateKeys) <= 0) {
                    foreach ($this->Columns as $col) {
                        $this->DuplicateValue($col->getName());
                    }
                }
                $sql[] = "ON DUPLICATE KEY UPDATE";
                if ($html === true) {
                    $sql[] = '<br /><br />';
                }
                $dps = array();
                foreach ($this->DuplicateKeys as $__dp) {
                    $dps[] = $__dp->Output();
                }

                $sql[] = implode(", " . (($html === true) ? "<br />" : ""), $dps);
            }

            //Do NOT Need to do any below if a insert query
            if (!$this->IsInsertQuery($type)) {
                if ($html === true) {
                    $sql[] = '<br /><br />';
                }

                if (count($this->Joins) > 0) {
                    $_join = array();
                    foreach ($this->Joins as $join) {
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
                    foreach ($this->Where as $where) {
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

                //Group
                if (count($this->GroupByData) > 0) {
                    if ($html === true) {
                        $sql[] = '<br />';
                    }

                    $_grpby = array();
                    foreach ($this->GroupByData as $grp) {
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
                    foreach ($this->OrderByData as $grp) {
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

        private function IsInsertQuery($type)
        {
            return $type == SQL_Type::INSERT || $type == SQL_Type::INSERT_ON_DUPLICATE || $type == SQL_Type::INSERT_IGNORE;
        }

        /**
         * @param bool $debug
         *
         * @return string
         */
        public function Build($debug = false)
        {

            $type  = SQL_Type::get(strtoupper(trim($this->QueryType)));
            $table = trim($this->Table);

            $sql = array($this->GetSqlCommand($type));

            if ($this->IsInsertQuery($type) || $type == SQL_Type::DELETE) {
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }

            if($debug){
                $sql[] = PHP_EOL;
            }

            if($type == SQL_Type::UPDATE){
                $sql[] = $table;
                $sql[] = "SET";
            }

            $bind_done = false;
            if (count($this->Columns) > 0) {
                if ($type == SQL_Type::SELECT) {
                    $_sql = array();
                    /** @var SQL_Column $col */
                    foreach ($this->Columns as $col) {
                        if($col != null)
                            $_sql[] = $col->Output();
                    }

                    foreach ($this->SubColumns AS $obj){
                        if(isset($obj['obj']) && $obj['obj'] != null) {
                            if(isset($obj['alias']) && !empty($obj['alias'])){
                                $_sql[] = "(" . $obj['obj']->Build() . ") AS {$obj['alias']}";
                            }else {
                                $_sql[] = "(" . $obj['obj']->Build() . ")";
                            }
                        }
                    }
                    if (count($_sql) > 0) {
                        $sql[] = implode(',', $_sql);
                    }
                }
                elseif ($this->IsInsertQuery($type)) {
                    $_sql  = array();
                    $_sql2 = array();
                    if(isset($this->BatchValues) && !empty($this->BatchValues)){
                        /** @var SQL_Column $col */
                        foreach ($this->Columns as $col) {
                            if($col != null) {
                                $_sql[] = $col->Output();
                                $count  = 0;
                                foreach ($this->BatchValues as $_id => $_val) {
                                    if(isset($this->BatchValues[$_id][":_" . $col->getName()])) {
                                        if (!isset($_sql2[$_id]))
                                            $_sql2[$_id] = array();

                                        $_sql2[$_id][] = $this->BatchValues[$_id][":_" . $col->getName()]->Output();
                                    }
                                }
                            }
                        }
                        if (count($_sql) > 0) {
                            $sql[] = '(' . implode(',', $_sql) . ')';
                        }
                        if (count($_sql2) > 0) {
                            $bind_done = true;
                            $sql[]     = 'VALUES ';
                            $bValues = array();
                            if($debug){
                                $sql[] = PHP_EOL;
                            }
                            foreach ($_sql2 AS $_s2){
                                $bValues[] = '(' . implode(',', $_s2) . ')';
                            }
                            $__explode_key = ",";
                            if($debug){
                                $__explode_key = ",".PHP_EOL;
                            }
                            $sql[]     = implode($__explode_key, $bValues);
                        }
                    }else {
                        /** @var SQL_Column $col */
                        foreach ($this->Columns as $col) {
                            if(isset($this->Values[":_" . $col->getName()])) {
                                $_sql[]  = $col->Output();
                                $_sql2[] = $this->Values[":_" . $col->getName()]->Output();
                            }
                        }
                        if (count($_sql) > 0) {
                            $sql[] = '(' . implode(',', $_sql) . ')';
                        }
                        if (count($_sql2) > 0) {
                            $bind_done = true;
                            $sql[]     = 'VALUES';
                            if($debug){
                                $sql[] = PHP_EOL;
                            }
                            $sql[]     = ' (' . implode(',', $_sql2) . ')';
                        }
                    }
                }
            }
            else {
                if (count($this->Joins) > 0) {
                    $sql[] = (!empty(trim($this->TablesAlias)) ? $this->TablesAlias : $table) . '.*';
                }
                else {
                    if($type != SQL_Type::UPDATE) {
                        $sql[] = '*';
                    }
                }
            }

            if($type == SQL_Type::UPDATE){
                $_sql = array();
                foreach ($this->UpdateFields AS $f){
                    $_sql[] = $f->Output();
                }
                if (count($_sql) > 0) {
                    $sql[] = implode(', ', $_sql);
                }
            }

            if($debug){
                $sql[] = PHP_EOL;
            }

            if ($type == SQL_Type::SELECT) {
                $sql[] = 'FROM'; //TODO SWITCH BASSED ON QUERRY_TYPE (SET FROM ..)
                $sql[] = self::EscapeOperator($table);
                if (!empty(trim($this->TablesAlias))) {
                    $sql[] = "AS {$this->TablesAlias}";
                }
            }
            elseif ($this->IsInsertQuery($type) && !$bind_done) {
                if($debug){
                    $sql[] = PHP_EOL;
                }
                $sql[] = 'VALUES';
                if($debug){
                    $sql[] = PHP_EOL;
                }
                if (count($this->Values) > 0) {
                    $_sql = array();
                    /** @var SQL_Value $col */
                    foreach ($this->Values as $val) {
                        $_sql[] = $val->Output();
                    }
                    if (count($_sql) > 0) {
                        $sql[] = '(' . implode(',', $_sql) . ')';
                    }
                }
            }

            if ($type == SQL_TYPE::INSERT_ON_DUPLICATE) {
                if (count($this->DuplicateKeys) <= 0) {
                    foreach ($this->Columns as $col) {
                        $this->DuplicateValue($col->getName());
                    }
                }
                if($debug){
                    $sql[] = PHP_EOL;
                }
                $sql[] = "ON DUPLICATE KEY UPDATE";
                $dps   = array();
                foreach ($this->DuplicateKeys as $__dp) {
                    $dps[] = $__dp->Output();
                }

                $sql[] = implode(", ", $dps);
            }

            //Do NOT Need to do any below if a insert query
            if (!$this->IsInsertQuery($type)) {
                if (count($this->Joins) > 0) {
                    $_join = array();
                    foreach ($this->Joins as $join) {
                        $_join[] = $join->Output();
                    }

                    if (!empty($_join)) {
                        $sql[] = implode(' ', $_join);
                    }
                }

                if (count($this->Where) > 0) {
                    $_where = array();
                    foreach ($this->Where as $where) {
                        $_where[] = $where->Output();
                    }
                    if (!empty($_where)) {
                        $sql[] = 'WHERE';
                        if($debug){
                            $sql[] = PHP_EOL;
                        }
                        $sql[] = implode(' ', $_where);
                    }
                }

                //Group
                if (count($this->GroupByData) > 0) {
                    $_grpby = array();
                    foreach ($this->GroupByData as $grp) {
                        $_grpby[] = $grp->Output();
                    }
                    if (!empty($_grpby)) {
                        if($debug){
                            $sql[] = PHP_EOL;
                        }
                        $sql[] = 'GROUP BY ' . implode(', ', $_grpby);
                    }
                }

                //Do we have a Having
                if(count($this->HavingData) > 0){
                    $_Having = array();
                    foreach ($this->HavingData as $have) {
                        $_Having[] = $have->Output();
                    }

                    if (!empty($_Having)) {
                        if($debug){
                            $sql[] = PHP_EOL;
                        }
                        $sql[] = 'HAVING';
                        $sql[] = implode(' ', $_Having);
                    }
                }

                if (count($this->OrderByData) > 0) {
                    $_grpby = array();
                    foreach ($this->OrderByData as $grp) {
                        $_grpby[] = $grp->Output();
                    }
                    if (!empty($_grpby)) {
                        if($debug){
                            $sql[] = PHP_EOL;
                        }
                        $sql[] = 'ORDER BY ' . implode(', ', $_grpby);
                    }
                }else{
                    if (count($this->OrderByData2) > 0) {
                        $_grpby = array();
                        foreach ($this->OrderByData2 as $grp) {
                            $_grpby[] = $grp->Output();
                        }
                        if (!empty($_grpby)) {
                            if($debug){
                                $sql[] = PHP_EOL;
                            }
                            $sql[] = 'ORDER BY ' . implode(' ', $_grpby);
                        }
                    }
                }
                if ($this->Limit !== null && $this->Limit instanceof SQL_LIMIT) {
                    $sql[] = $this->Limit->Output();
                }
            }

            return implode(" ", $sql);
        }

        /*** @return array */
        public function getBinds() { return $this->Binds; }

        /*** @return SQL_Type|string|static */
        public function getQueryType() { return $this->QueryType; }

        /**
         * @return string
         */
        public function getTable(): string { return $this->Table; }
    }