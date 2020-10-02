<?php
    /**
     * User: Jason Townsend
     * Date: 16/03/2016
     * Updated: 10/07/2020
     */

    namespace AmaranthNetwork\Database;

    use AmaranthNetwork\Database\Builder\SQLBuilder;
    use AmaranthNetwork\Misc;
    use PDO;
    use PDOException;

    class PDOAdapter extends PDO
    {
        //region Fields
        private $User;
        private $Password;
        private $Host;
        private $Port;
        private $Database;
        private $FriendlyName;
        private $Options;
        private $PreparedStatements;

        private $enabled = array(
            'SELECT' => true,
            'DELETE' => true,
            'INSERT' => true,
            ''
        );

        /**
         * PHP Statement Handler
         *
         * @var object
         */
        private $_oSTH = null;

        /**
         * PDO SQL Statement
         *
         * @var string
         */
        public $sSql = '';

        /**
         * PDO SQL table name
         *
         * @var string
         */
        public $sTable = array();

        /**
         * PDO SQL Where Condition
         *
         * @var string
         */
        public $aWhere = array();

        /**
         * PDO SQL table column
         *
         * @var string
         */
        public $aColumn = array();

        /**
         * PDO SQL Other condition
         *
         * @var string
         */
        public $sOther = array();

        /**
         * PDO Results,Fetch All PDO Results array
         *
         * @var array
         */
        public $aResults = array();

        /**
         * PDO Result,Fetch One PDO Row
         *
         * @var array
         */
        public $aResult = array();

        /**
         * Get PDO Last Insert ID
         *
         * @var integer
         */
        public $iLastId = 0;

        /**
         * PDO last insert di in array
         * using with INSERT BATCH Query
         *
         * @var array
         */
        public $iAllLastId = array();

        /**
         * Get PDO Error
         *
         * @var string
         */
        public $sPdoError = '';

        /**
         * Get All PDO Affected Rows
         *
         * @var integer
         */
        public $iAffectedRows = 0;

        /**
         * Catch temp data
         *
         * @var null
         */
        public $aData = null;

        /**
         * Enable/Disable class debug mode
         *
         * @var boolean
         */
        public $log = false;

        /**
         * Set flag for batch insert
         *
         * @var bool
         */
        public $batch = false;

        /**
         * PDO Error File
         *
         * @var string
         */
        const ERROR_LOG_FILE = 'PDO_Errors.log';

        /**
         * PDO SQL log File
         *
         * @var string
         */
        const SQL_LOG_FILE = 'PDO_Sql.log';

        /**
         * Set PDO valid Query operation
         *
         * @var array
         */
        private $aValidOperation = array('SELECT', 'REPLACE', 'INSERT', 'UPDATE', 'DELETE', 'ALTER');

        /**
         * PDO Object
         *
         * @var object
         */
        protected static $oPDO = null;
        //endregion

        /**
         * Auto Start on Object init
         *
         * @param string $host
         * @param int    $port
         * @param string $user
         * @param string $pass
         * @param string $dbName
         * @param string $fName
         * @param array  $options
         * @param array  $PreparedStatements
         */
        public function __construct($host = "localhost", $port = 3306, $user, $pass, $dbName, $fName = "", $options = array(), $PreparedStatements = array())
        {
            $this->User               = $user;
            $this->Password           = $pass;
            $this->Host               = $host;
            $this->Port               = $port;
            $this->Database           = $dbName;
            $this->FriendlyName       = $fName;
            $this->PreparedStatements = $PreparedStatements;
            // Set options TODO LOOP $options Array and add Missing/Update Default options
            $this->Options = array(
                PDO::ATTR_PERSISTENT => false, //ID CONNECTION BEEN RESET DISABLE THIS!!!
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                //PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",

            );

            //Update the options Table with any new otions we need to pass
            foreach ($options as $key => $value) {
                $this->Options[$key] = $value;
            }

            if (empty($this->Host) || $this->Port <= 0 || empty($this->User) || empty($this->Database)) {
                die("invalid Connection Information! For Connection " . $this->FriendlyName);
            }

            // try catch block start
            try {
                // use native pdo class and connect
                parent::__construct('mysql:host=' . $this->Host . ';dbname=' . $this->Database, $this->User, $this->Password, $this->Options);

                // set pdo error mode silent
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                /** If you want to Show Class exceptions on Screen, Uncomment below code **/
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                /** Use this setting to force PDO to either always emulate prepared statements (if TRUE),
                 * or to try to use native prepared statements (if FALSE). **/
                $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                // set default pdo fetch mode as fetch assoc
                $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
            catch (PDOException $e) {
                // get pdo error and pass on error method
                die("ERROR in establish connection: " . $e->getMessage());
            }
        }

        /** Unset The Class Object PDO */
        public function __destruct() { self::$oPDO = null; }

        /**
         * Get Instance of PDO Class as Singleton Pattern
         *
         * @param string $host
         * @param int    $port
         * @param string $user
         * @param string $pass
         * @param string $dbName
         * @param string $fName
         * @param array  $options
         * @param array  $PreparedStatements
         *
         * @return object $oPDO
         */
        public static function getPDO($host = "localhost", $port = 3306, $user, $pass, $dbName, $fName = "", $options = array(), $PreparedStatements = array())
        {
            // if not set self pdo object property or pdo set as null
            if (!isset(self::$oPDO) || (self::$oPDO !== null)) {
                // set class pdo property with new connection
                self::$oPDO = new self($host, $port, $user, $pass, $dbName, $fName, $options, $PreparedStatements);
            }
            // return class property object
            return self::$oPDO;
        }

        /**
         * Start PDO Transaction
         */
        public function start()
        {
            /*** begin the transaction ***/
            $this->beginTransaction();
        }

        /**
         * Start PDO Commit
         */
        public function end()
        {
            /*** commit the transaction ***/
            $this->commit();
        }

        /**
         * Start PDO Rollback
         */
        public function back()
        {
            /*** roll back the transaction if we fail ***/
            $this->rollback();
        }

        /**
         * Return PDO Query result by index value
         *
         * @param int $iRow
         *
         * @return array:boolean
         */
        public function result($iRow = 0)
        {
            return isset($this->aResults[$iRow]) ? $this->aResults[$iRow] : false;
        }

        /**
         * Get Affected rows by PDO Statement
         *
         * @return number:boolean
         */
        public function affectedRows()
        {
            return is_numeric($this->iAffectedRows) ? $this->iAffectedRows : false;
        }

        /**
         * Get Last Insert id by Insert query
         *
         * @return number
         */
        public function getLastInsertId() { return $this->iLastId; }

        /**
         * Get all last insert id by insert batch query
         *
         * @return array
         */
        public function getAllLastInsertId() { return $this->iAllLastId; }

        /**
         * Get Helper Object
         *
         * @return PDOHelper
         */
        public function helper() { return new PDOHelper(); }

        /**
         * Execute PDO Query
         *
         * @param string|int $statement
         *
         * @return PDOAdapter|multi type:|number
         */
        public function PQuery($statement = '')
        {
            $pQueryID = 0;
            //Get PQuery if we have a int as the QueryString
            if (is_numeric($statement)) {
                //Save the ID for logger
                $pQueryID = $statement;
                //Check we have a Valid ID Must be Over 0
                if ((int)$statement <= 0 && count($this->PreparedStatements) <= 0) {
                    return false;
                }
                //We need to change the int to String
                if ($this->PreparedStatements[$statement] != null && !empty($this->PreparedStatements[$statement])) {
                    $statement = $this->PreparedStatements[$statement];
                }
                else {
                    $this->error('invalid PreparedStatement (ID:' . $pQueryID . ') provided');
                }
            }

            // clean query from white space
            $statement = trim($statement);

            $parameters = array();

            if (func_num_args() > 1) {
                $data = func_get_arg(1);

                //did we provide an array or do we have args
                if (!is_array($data)) {
                    for ($i = 1; $i < func_num_args(); $i++) {
                        $parameters[] = func_get_arg($i);
                    }
                }
                else {
                    $parameters = $data;
                }
            }

            // get operation type
            $operation = explode(' ', $statement);
            // make first word in uppercase
            $operation[0] = strtoupper($operation[0]);

            // check valid sql operation statement
            if (!in_array($operation[0], $this->aValidOperation)) {
                $this->error('invalid operation called in ' . ($pQueryID > 0 ? "PQuery" : "query") . ($pQueryID > 0 ? " (ID: " . $pQueryID . ")" : "") . ' (' . $statement . ') . use only ' . implode(', ', $this->aValidOperation));
            }

            // sql query pass with no bind param
            if (!empty($statement) && count($parameters) <= 0) {
                // set class property with pass value
                $this->sSql = $statement;
                // set class statement handler
                $this->_oSTH = $this->prepare($this->sSql);
                // try catch block start
                try {
                    // execute pdo statement
                    if ($this->_oSTH->execute()) {
                        // check operation type
                        switch ($operation[0]):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults = $this->_oSTH->fetchAll();
                                // return PDO instance
                                return $this;
                                break;
                            case 'INSERT':
                                // return last insert id
                                $this->iLastId = $this->lastInsertId();
                                // return PDO instance
                                return $this;
                                break;
                            case 'UPDATE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // return PDO instance
                                return $this;
                                break;
                            case 'DELETE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // return PDO instance
                                return $this;
                                break;
                        endswitch;
                        // close pdo cursor
                        $this->_oSTH->closeCursor();
                        // return pdo result
                        return $this;
                    }
                    else {
                        // if not run pdo statement sed error
                        self::error($this->_oSTH->errorInfo());
                    }
                }
                catch (PDOException $e) {
                    self::error($e->getMessage() . ': ' . __LINE__);
                } // end try catch block
            } // if query pass with bind param
            else if (!empty($statement) && count($parameters) > 0) {
                // set class property with pass query
                $this->sSql = $statement;
                // set class where array
                $this->aData = $parameters;
                // set class pdo statement handler
                $this->_oSTH = $this->prepare($this->sSql);
                // start binding fields
                // bind pdo param
                $this->_bindPdoParam($parameters);
                // use try catch block to get pdo error
                try {
                    // run pdo statement with bind param
                    if ($this->_oSTH->execute()) {
                        // check operation type
                        switch ($operation[0]):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults = $this->_oSTH->fetchAll();
                                // return PDO instance
                                return $this;
                                break;
                            case 'INSERT':
                                // return last insert id
                                $this->iLastId = $this->lastInsertId();
                                // return PDO instance
                                return $this;
                                break;
                            case 'UPDATE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // return PDO instance
                                return $this;
                                break;
                            case 'DELETE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // return PDO instance
                                return $this;
                                break;
                        endswitch;
                        // close pdo cursor
                        $this->_oSTH->closeCursor();
                    }
                    else {
                        $this->error($this->_oSTH->errorInfo());
                    }
                }
                catch (PDOException $e) {
                    $this->error(($pQueryID > 0 ? "PQuery (ID: " . $pQueryID . ") " : "") . $e->getMessage() . ': ' . __LINE__);
                } // end try catch block to get pdo error
            }
            else {
                $this->error('Query is empty..');
            }
        }

        public function FakePQuery($statement)
        {
            // clean query from white space
            $statement  = trim($statement);
            $this->sSql = $statement;
            $parameters = array();

            if (func_num_args() > 1) {
                $data = func_get_arg(1);

                //did we provide an array or do we have args
                if (!is_array($data)) {
                    for ($i = 1; $i < func_num_args(); $i++) {
                        $parameters[] = func_get_arg($i);
                    }
                }
                else {
                    $parameters = $data;
                }
            }

            // get operation type
            $operation = explode(' ', $statement);
            // make first word in uppercase
            $operation[0] = strtoupper($operation[0]);

            // check valid sql operation statement
            if (!in_array($operation[0], $this->aValidOperation)) {
                self::error("invalid operation called in query ({$statement}) . use only " . implode(', ', $this->aValidOperation));
            }

            if (!empty($statement) && count($parameters) <= 0) {
                return $this->sSql;
            }
            else if (!empty($statement) && count($parameters) > 0) {
                $this->aData = $parameters;

                $this->_oSTH = $this->prepare($this->sSql);

                return $this->interpolateQuery();
            }
            else {
                $this->error('Query is empty..');
            }
            return "";
        }

        public function ExecuteBuilder(SQLBuilder $query)
        {
            $this->sSql = $query->Build();

            if (!in_array(explode(" ", str_replace("_", " ", $query->getQueryType()->getName()))[0], $this->aValidOperation, true)) {
                $this->error('invalid operation called in SQLBuilderQuery' . ' (' . $this->sSql . ') . use only ' . implode(', ', $this->aValidOperation));
            }
            if (!empty($this->sSql) && !$query->HasBinds()) {
                $this->_oSTH = $this->prepare($this->sSql);
                try {
                    // execute pdo statement
                    if ($this->_oSTH->execute()) {
                        // check operation type
                        switch ($query->getQueryType()):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults = $this->_oSTH->fetchAll();
                                break;
                            case 'INSERT':
                            case 'INSERT_IGNORE':
                            case 'INSERT_ON_DUPLICATE':
                                // return last insert id
                                $this->iLastId = $this->lastInsertId();
                                break;
                            case 'UPDATE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                break;
                            case 'DELETE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                break;
                        endswitch;
                        // close pdo cursor
                        $this->_oSTH->closeCursor();
                        // return pdo result
                        return $this;
                    }

                    // if not run pdo statement sed error
                    $this->error($this->_oSTH->errorInfo());

                    return $this;
                }
                catch (PDOException $e) {
                    $this->error($e->getMessage() . ': ' . __LINE__);
                } // end try catch block
            }
            elseif (!empty($this->sSql)) {
                $this->aData = $query->getBinds();

                $this->_oSTH = $this->prepare($this->sSql);

                $this->_bindPdoNameSpace($this->aData);

                try {
                    // run pdo statement with bind param
                    if ($this->_oSTH->execute()) {
                        // check operation type
                        switch ($query->getQueryType()):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults = $this->_oSTH->fetchAll();
                                // return PDO instance
                                break;
                            case 'INSERT':
                            case 'INSERT_IGNORE':
                            case 'INSERT_ON_DUPLICATE':
                                // return last insert id
                                $this->iLastId = $this->lastInsertId();
                                // return PDO instance
                                break;
                            case 'UPDATE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // return PDO instance
                                break;
                            case 'DELETE':
                                // get affected rows
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // return PDO instance
                                break;
                        endswitch;
                        // close pdo cursor
                        $this->_oSTH->closeCursor();
                    }
                    else {
                        $this->error($this->_oSTH->errorInfo());
                    }
                }
                catch (PDOException $e) {
                    $this->error($e->getMessage() . ': ' . __LINE__);
                } // end try catch block to get pdo error
            }
            else {
                $this->error('Query is empty..');
            }
            return $this;
        }

        public function FakeExecuteBuilder(SQLBuilder $query)
        {
            $this->sSql = $query->Build();

            if (!in_array(explode(" ", str_replace("_", " ", $query->getQueryType()->getName()))[0], $this->aValidOperation, true)) {
                $this->error('invalid operation called in SQLBuilderQuery' . ' (' . $this->sSql . ') . use only ' . implode(', ', $this->aValidOperation));
            }
            if (!empty($this->sSql) && !$query->HasBinds()) {
                return $this->sSql;
            }
            elseif (!empty($this->sSql)) {
                $this->aData = $query->getBinds();

                $this->_oSTH = $this->prepare($this->sSql);

                return $this->interpolateQuery();
            }
            else {
                $this->error('Query is empty..');
            }
            return "";
        }

        /**
         * Return PDO Query results array/json/xml type
         *
         * @param string $type
         *
         * @return array|mixed
         */
        public function results($type = 'array')
        {
            switch ($type) {
                case 'array':
                    // return array data
                    return $this->aResults;
                    break;
                case 'xml':
                    //send the xml header to the browser
                    header("Content-Type:text/xml");
                    // return xml content
                    return $this->helper()->arrayToXml($this->aResults);
                    break;
                case 'json':
                    // set header as json
                    header('Content-type: application/json; charset="utf-8"');
                    // return json encoded data
                    return json_encode($this->aResults);
                    break;
            }
        }

        /**
         * Get Total Number Of Records in Requested Table
         *
         * @param string $sTable
         * @param string $sWhere
         *
         * @return number
         */
        public function count($sTable = '', $sWhere = '')
        {
            // if table name not pass
            if (!empty($sTable)) {
                if (empty($sWhere)) {
                    // create count query
                    $this->sSql = "SELECT COUNT(*) AS NUMROWS FROM `$sTable`;";
                }
                else {
                    // create count query
                    $this->sSql = "SELECT COUNT(*) AS NUMROWS FROM `$sTable` WHERE $sWhere;";
                }
                // pdo prepare statement
                $this->_oSTH = $this->prepare($this->sSql);
                try {
                    if ($this->_oSTH->execute()) {
                        // fetch array result
                        $this->aResults = $this->_oSTH->fetch();
                        // close pdo
                        $this->_oSTH->closeCursor();
                        // return number of count
                        return $this->aResults['NUMROWS'];
                    }
                    else {
                        $this->error($this->_oSTH->errorInfo());
                    }
                }
                catch (PDOException $e) {
                    // get pdo error and pass on error method
                    $this->error($e->getMessage() . ': ' . __LINE__);
                }
            }
            else {
                $this->error('Table name not found..');
            }
        }
        /** @noinspection MoreThanThreeArgumentsInspection */

        /**
         * @param string $sTable
         * @param string $tableAs
         * @param string $sWhere
         * @param array  $joins
         * @param string $type_type
         *
         * @return mixed
         */
        public function CountJoins($sTable = '', $tableAs = '', $sWhere = '', array $joins = array(), $type_type = 'INNER JOIN', $binds = array())
        {
            $sqljoin = implode(" {$type_type} ", $joins);
            if (!empty($sqljoin)) {
                $sqljoin = "{$type_type} {$sqljoin}";
            }
            // if table name not pass
            if (!empty($sTable)) {
                if (empty($sWhere)) {
                    // create count query
                    $this->sSql = "SELECT COUNT(*) AS NUMROWS FROM `$sTable` {$tableAs} {$sqljoin};";
                }
                else {
                    // create count query
                    $this->sSql = "SELECT COUNT(*) AS NUMROWS FROM `$sTable` {$tableAs} {$sqljoin} WHERE $sWhere;";
                }
                // pdo prepare statement
                $this->_oSTH = $this->prepare($this->sSql);
                try {
                    $this->_bindPdoParam($binds);
                    if ($this->_oSTH->execute()) {
                        // fetch array result
                        $this->aResults = $this->_oSTH->fetch();
                        // close pdo
                        $this->_oSTH->closeCursor();
                        // return number of count
                        return $this->aResults['NUMROWS'];
                    }

                    $this->error($this->_oSTH->errorInfo());
                }
                catch (PDOException $e) {
                    // get pdo error and pass on error method
                    $this->error($e->getMessage() . ': ' . __LINE__);
                }
            }
            else {
                $this->error('Table name not found..');
            }
        }

        /**
         * Return Table Fields of Requested Table
         *
         * @param string $sTable
         *
         * @return array Field Type and Field Name
         */
        public function describe($sTable = '')
        {
            $this->sSql  = $sSql = "DESC $sTable;";
            $this->_oSTH = $this->prepare($sSql);
            $this->_oSTH->execute();
            $aColList = $this->_oSTH->fetchAll();
            foreach ($aColList as $key) {
                $aField[] = $key['Field'];
                $aType[]  = $key['Type'];
            }
            return array_combine($aField, $aType);
        }

        /**
         *
         * @param array $array_data
         *
         * @return array
         */
        public function customWhere($array_data = array())
        {
            $syntax = '';
            foreach ($array_data as $key => $value) {
                $key = trim($key);
                if (strstr($key, ' ')) {
                    $array = explode(' ', $key);
                    if (count($array) == '2') {
                        $random   = '';
                        $field    = $array[0];
                        $operator = $array[1];
                        $tmp[]    = "$field $operator :s_$field" . "$random";
                        $syntax   .= " $field $operator :s_$field" . "$random ";
                    }
                    elseif (count($array) == '3') {
                        $random    = '';
                        $condition = $array[0];
                        $field     = $array[1];
                        $operator  = $array[2];
                        $tmp[]     = "$condition $field $operator :s_$field" . "$random";
                        $syntax    .= " $condition $field $operator :s_$field" . "$random ";
                    }
                }
            }
            return array(
                'where' => $syntax,
                'bind'  => implode(' ', $tmp)
            );
        }

        /**
         * PDO Bind Param with :namespace
         *
         * @param array $array
         */
        private function _bindPdoNameSpace(array $array = array())
        {
            if (strstr(key($array), ' ')) {
                // bind array data in pdo
                foreach ($array as $f => $v) {
                    // get table column from array key
                    $field = $this->getFieldFromArrayKey($f);
                    // check pass data type for appropriate field
                    switch (gettype($array[$f])):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam(':' . $field, $array[$f], PDO::PARAM_STR);
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam(':' . $field, $array[$f], PDO::PARAM_INT);
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam(':' . $field, $array[$f], PDO::PARAM_BOOL);
                            break;
                        default:
                            $this->_oSTH->bindParam(':' . $field, $array[$f], PDO::PARAM_STR);
                            break;
                    endswitch;
                } // end for each here
            }
            else {
                // bind array data in pdo
                foreach ($array as $f => $v) {
                    // check pass data type for appropriate field
                    switch (gettype($array[$f])):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam(':' . $f, $array[$f], PDO::PARAM_STR);
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam(':' . $f, $array[$f], PDO::PARAM_INT);
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam(':' . $f, $array[$f], PDO::PARAM_BOOL);
                            break;
                        case 'NULL':
                            $NULL_VAR = null;
                            $this->_oSTH->bindParam(':' . $f, $NULL_VAR, PDO::PARAM_INT);
                            break;
                        default:
                            $this->_oSTH->bindParam(':' . $f, $array[$f], PDO::PARAM_STR);
                            break;
                    endswitch;
                } // end for each here
            }
        }

        private function _bindPdoNameSpace1($array = array())
        {
            if (strstr(key($array), ' ')) {
                // bind array data in pdo
                foreach ($array as $f => $v) {
                    // get table column from array key
                    $field = $this->getFieldFromArrayKey($f);
                    // check pass data type for appropriate field
                    switch (gettype($array[$f])):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam(":s" . "_" . "$field", $array[$f], PDO::PARAM_STR);
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam(":s" . "_" . "$field", $array[$f], PDO::PARAM_INT);
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam(":s" . "_" . "$field", $array[$f], PDO::PARAM_BOOL);
                            break;
                    endswitch;
                } // end for each here
            }
            else {
                // bind array data in pdo
                foreach ($array as $f => $v) {
                    // check pass data type for appropriate field
                    switch (gettype($array[$f])):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam(":s" . "_" . "$f", $array[$f], PDO::PARAM_STR);
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam(":s" . "_" . "$f", $array[$f], PDO::PARAM_INT);
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam(":s" . "_" . "$f", $array[$f], PDO::PARAM_BOOL);
                            break;
                        case "NULL":
                            $NULL_VAR = null;
                            $this->_oSTH->bindParam(":s" . "_" . "$f", $NULL_VAR, PDO::PARAM_INT);
                            break;
                    endswitch;
                } // end for each here
            }
        }

        /**
         * Bind PDO Param without :namespace
         *
         * @param array $array
         */
        private function _bindPdoParam($array = array())
        {
            // bind array data in pdo
            foreach ($array as $f => $v) {
                // check pass data type for appropriate field

                switch (gettype($array[$f])):
                    // is string found then pdo param as string
                    case 'string':
                        $this->_oSTH->bindParam($f + 1, $array[$f], PDO::PARAM_STR);
                        break;
                    // if int found then pdo param set as int
                    case 'integer':
                        $this->_oSTH->bindParam($f + 1, $array[$f], PDO::PARAM_INT);
                        break;
                    // if boolean found then set pdo param as boolean
                    case 'boolean':
                        $this->_oSTH->bindParam($f + 1, $array[$f], PDO::PARAM_BOOL);
                        break;
                    case "NULL":
                        $NULL_VAR = null;
                        $this->_oSTH->bindParam($f + 1, $NULL_VAR, PDO::PARAM_INT);
                        break;
                endswitch;
            } // end for each here
        }

        /**
         * Catch Error in txt file
         *
         * @param mixed $msg
         */
        public function error($msg)
        {
            // log set as true
            if ($this->log) {
                // show executed query with error
                $this->showQuery();
                // die code
                $this->helper()->errorBox($msg);
            }
            else {
                // show error message in log file
                file_put_contents(self::ERROR_LOG_FILE, date('Y-m-d h:m:s') . ' :: ' . $this->FriendlyName . ' :: ' . $msg . "\n", FILE_APPEND);
                // die with user message
                $this->helper()->error();
            }
        }

        /**
         * Show executed query on call
         *
         * @param boolean $logfile set true if wanna log all query in file
         *
         * @return PDOAdapter
         */
        public function showQuery($logfile = false)
        {
            if (!$logfile) {
                if (Misc::IsCLI()) {
                    Misc::Debug_Print(3, $this->FriendlyName, "    Executed Query -> ", $this->interpolateQuery());
                }
                else {
                    echo "<div style='color:#990099; border:1px solid #777; padding:2px; background-color: #E5E5E5; text-align: center;'>" . $this->FriendlyName . "</div>\r\n";
                    echo "<div style='color:#990099; border:1px solid #777; padding:2px; background-color: #E5E5E5;'>\r\n";
                    echo " Executed Query -> <span style='color:#008000;'> \r\n";
                    echo $this->helper()->formatSQL($this->interpolateQuery()) . "\r\n";
                    echo "</span></div>\r\n";
                }
                return $this;
            }
            else {
                // show error message in log file
                file_put_contents(self::SQL_LOG_FILE, date('Y-m-d h:m:s') . ' :: ' . $this->FriendlyName . ' :: ' . $this->interpolateQuery() . "\n", FILE_APPEND);
                return $this;
            }
        }

        /**
         * Replaces any parameter placeholders in a query with the value of that
         * parameter. Useful for debugging. Assumes anonymous parameters from
         *
         * @return mixed
         */
        protected function interpolateQuery()
        {
            $sql = $this->sSql;// $this->_oSTH->queryString;
            // handle insert batch data
            if (!$this->batch) {
                $params = ((is_array($this->aData)) && (count($this->aData) > 0)) ? $this->aData : $this->sSql;
                if (is_array($params)) {
                    # build a regular expression for each parameter
                    foreach ($params as $key => $value) {
                        if (strstr($key, ' ')) {
                            $real_key = $this->getFieldFromArrayKey($key);
                            // update param value with quotes, if string value
                            $params[$key] = is_string($value) ? '"' . $value . '"' : $value;
                            // make replace array
                            $keys[] = is_string($real_key) ? '/:' . $real_key . '/' : '/[?]/';
                        }
                        else {
                            // update param value with quotes, if string value
                            $params[$key] = is_string($value) ? '"' . $value . '"' : $value;
                            // make replace array
                            $keys[] = is_string($key) ? '/:' . $key . '/' : '/[?]/';
                        }
                    }
                    $sql = preg_replace($keys, $params, $sql, -1, $count);

                    if (strstr($sql, ':')) {
                        foreach ($this->aWhere as $key => $value) {
                            if (strstr($key, ' ')) {
                                $real_key = $this->getFieldFromArrayKey($key);
                                // update param value with quotes, if string value
                                $params[$key] = is_string($value) ? '"' . $value . '"' : $value;
                                // make replace array
                                $keys[] = is_string($real_key) ? '/:' . $real_key . '/' : '/[?]/';
                            }
                            else {
                                // update param value with quotes, if string value
                                $params[$key] = is_string($value) ? '"' . $value . '"' : $value;
                                // make replace array
                                $keys[] = is_string($key) ? '/:' . $key . '/' : '/[?]/';
                            }
                        }
                        $sql = preg_replace($keys, $params, $sql, -1, $count);
                    }
                    return $sql;
                    #trigger_error('replaced '.$count.' keys');
                }
                else {
                    return $params;
                }
            }
            else {
                $params_batch = ((is_array($this->aData)) && (count($this->aData) > 0)) ? $this->aData : $this->sSql;
                $batch_query  = '';
                if (is_array($params_batch)) {
                    # build a regular expression for each parameter
                    foreach ($params_batch as $keys => $params) {
                        //echo $params."\r\n";
                        foreach ($params as $key => $value) {
                            if (strstr($key, ' ')) {
                                $real_key = $this->getFieldFromArrayKey($key);
                                // update param value with quotes, if string value
                                $params[$key] = is_string($value) ? '"' . $value . '"' : $value;
                                // make replace array
                                $array_keys[] = is_string($real_key) ? '/:s_' . $real_key . '/' : '/[?]/';
                            }
                            else {
                                // update param value with quotes, if string value
                                $params[$key] = is_string($value) ? '"' . $value . '"' : $value;
                                // make replace array
                                $array_keys[] = is_string($key) ? '/:s_' . $key . '/' : '/[?]/';
                            }
                        }
                        $batch_query .= "<br />" . preg_replace($array_keys, $params, $sql, -1, $count);
                    }
                    return $batch_query;
                    #trigger_error('replaced '.$count.' keys');
                }
                else {
                    return $params_batch;
                }
            }
        }

        /**
         * Return real table column from array key
         *
         * @param array $array_key
         *
         * @return mixed
         */
        public function getFieldFromArrayKey($array_key = array())
        {
            // get table column from array key
            $key_array = explode(' ', $array_key);
            // check no of chunk
            return (count($key_array) == '2') ? $key_array[0] : ((count($key_array) > 2) ? $key_array[1] : $key_array[0]);
        }

        /**
         * Set PDO Error Mode to get an error log file or true to show error on screen
         *
         * @param bool $mode
         */
        public function setErrorLog($mode = false)
        {
            $this->log = $mode;
            return $this;
        }
    }
