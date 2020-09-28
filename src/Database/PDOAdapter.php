<?php
    /**
     * User: Jason Townsend
     * Date: 16/03/2016
     * Updated: 10/07/2020
     */


    namespace AmaranthNetwork\Database;



    use PDO;

    class PDOAdapter extends PDO {
        //region Fields
        private $User;
        private $Password;
        private $Host;
        private $Port;
        private $Database;
        private $FriendlyName;
        private $Options;
        private $PreparedStatements;

        private $enabled = Array(
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
        private $aValidOperation = array( 'SELECT', 'REPLACE', 'INSERT', 'UPDATE', 'DELETE', 'ALTER'  );

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
        public function __construct($host = "localhost", $port = 3306, $user, $pass, $dbName, $fName = "", $options = Array(), $PreparedStatements = Array()){
            $this->User = $user;
            $this->Password = $pass;
            $this->Host = $host;
            $this->Port = $port;
            $this->Database = $dbName;
            $this->FriendlyName = $fName;
            $this->PreparedStatements = $PreparedStatements;
            // Set options TODO LOOP $options Array and add Missing/Update Default options
            $this->Options = array(
                PDO::ATTR_PERSISTENT => false, //ID CONNECTION BEEN RESET DISABLE THIS!!!
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                //PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",

            );

            //Update the options Table with any new otions we need to pass
            foreach($options AS $key => $value){
                $this->Options[$key] = $value;
            }

            if(empty($this->Host) || $this->Port <= 0 || empty($this->User) ||  empty($this->Database) ) {
                die("invalid Connection Information! For Connection " . $this->FriendlyName);
            }

            // try catch block start
            try {
                // use native pdo class and connect
                parent::__construct('mysql:host=' . $this->Host . ';dbname=' . $this->Database, $this->User, $this->Password, $this->Options);

                // set pdo error mode silent
                $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
                /** If you want to Show Class exceptions on Screen, Uncomment below code **/
                $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                /** Use this setting to force PDO to either always emulate prepared statements (if TRUE),
                or to try to use native prepared statements (if FALSE). **/
                $this->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );
                // set default pdo fetch mode as fetch assoc
                $this->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
            }
            catch ( PDOException $e ) {
                // get pdo error and pass on error method
                die("ERROR in establish connection: ".$e->getMessage());
            }

        }
        /**
         * Unset The Class Object PDO
         */
        public function __destruct() {
            self::$oPDO = null;
        }

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
         *
         */
        public static function getPDO( $host = "localhost", $port = 3306, $user, $pass, $dbName, $fName = "", $options = Array(), $PreparedStatements = Array() ) {
            // if not set self pdo object property or pdo set as null
            if ( !isset( self::$oPDO ) || ( self::$oPDO !== null ) ) {
                // set class pdo property with new connection
                self::$oPDO = new self( $host, $port, $user, $pass, $dbName, $fName, $options, $PreparedStatements);
            }
            // return class property object
            return self::$oPDO;
        }
        /**
         * Start PDO Transaction
         */
        public function start() {
            /*** begin the transaction ***/
            $this->beginTransaction();
        }
        /**
         * Start PDO Commit
         */
        public function end() {
            /*** commit the transaction ***/
            $this->commit();
        }
        /**
         * Start PDO Rollback
         */
        public function back() {
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
        public function result( $iRow = 0 ) {
            return isset($this->aResults[$iRow]) ? $this->aResults[$iRow] : false;
        }
        /**
         * Get Affected rows by PDO Statement
         *
         * @return number:boolean
         */
        public function affectedRows() {
            return is_numeric($this->iAffectedRows) ? $this->iAffectedRows : false;
        }
        /**
         * Get Last Insert id by Insert query
         *
         * @return number
         */
        public function getLastInsertId() {
            return $this->iLastId;
        }
        /**
         * Get all last insert id by insert batch query
         *
         * @return array
         */
        public function getAllLastInsertId() {
            return $this->iAllLastId;
        }
        /**
         * Get Helper Object
         *
         * @return PDOHelper
         */
        public function helper() {
            return new PDOHelper();
        }

        /**
         * @deprecated
         * function query (DEPRECATED)
         *
         * OLD CALL FOR MYSQLI QUERY STRING (REMOVE ONCE CONVERTED OVER) USE PQUERY FOR NEW PDO QUERY
         *
         * @param string $statement
         *
         * @return bool|object|\PDOStatement|Array
         */
        public function query($statement){
            $pQueryID = 0;
            //Get PQuery if we have a int as the QueryString
            if(is_numeric($statement)){
                //Save the ID for logger
                $pQueryID = $statement;
                //Check we have a Valid ID Must be Over 0
                if ((int)$statement <= 0 && count($this->PreparedStatements) <= 0) {
                    return false;
                }
                //We need to change the int to String
                if($this->PreparedStatements[$statement] != null && !empty($this->PreparedStatements[$statement])) {
                    $statement = $this->PreparedStatements[$statement];
                }
                else {
                    self::error('invalid PreparedStatement (ID:' . $pQueryID . ') provided');
                }
            }

            $statement = trim($statement);

            if(func_num_args() > 1) {
                $data = func_get_arg(1);
            }

            if(isset($data) && is_array($data)) {
                $this->_oSTH= $this->prepare($statement);
                try {
                    if ($this->_oSTH->execute($data)) {
                        return $this->_oSTH;
                    }
                    else {
                        self::error($this->_oSTH->errorInfo());
                    }
                }
                catch ( PDOException $e ) {
                    self::error($e->getMessage() . ': ' . __LINE__);
                }
            }else{
                $args = array();
                //start at index 1 as index 0 is the query string and we only want the args after it to bind to the string
                for($i=1;$i<func_num_args();$i++){
                    $args[] = func_get_arg($i);
                }
                if(count($args) > 0) {
                    $this->_oSTH=  $this->prepare($statement);
                    try {
                        if ($this->_oSTH->execute($args)) {
                            return $this->_oSTH;
                        }
                        else {
                            self::error($this->_oSTH->errorInfo());
                        }
                    }
                    catch ( PDOException $e ) {
                        self::error($e->getMessage() . ': ' . __LINE__);
                    }
                    //return parent::prepare($query)->execute($args);
                }
                else
                    return parent::query($statement);
            }
        }

        /**
         * Execute PDO Query
         *
         * @param string|int $statement
         *
         * @return PDOAdapter|multi type:|number
         */
        public function PQuery($statement = '') {

            $pQueryID = 0;
            //Get PQuery if we have a int as the QueryString
            if(is_numeric($statement)){
                //Save the ID for logger
                $pQueryID = $statement;
                //Check we have a Valid ID Must be Over 0
                if ((int)$statement <= 0 && count($this->PreparedStatements) <= 0) {
                    return false;
                }
                //We need to change the int to String
                if($this->PreparedStatements[$statement] != null && !empty($this->PreparedStatements[$statement])) {
                    $statement = $this->PreparedStatements[$statement];
                }
                else {
                    self::error('invalid PreparedStatement (ID:' . $pQueryID . ') provided');
                }
            }

            // clean query from white space
            $statement = trim( $statement );

            $parameters = Array();

            if(func_num_args() > 1) {
                $data = func_get_arg(1);

                //did we provide an array or do we have args
                if (!is_array($data)) {
                    for ($i = 1; $i < func_num_args(); $i++) {
                        $parameters[] = func_get_arg($i);
                    }
                } else {
                    $parameters = $data;
                }
            }

            // get operation type
            $operation    = explode( ' ', $statement );
            // make first word in uppercase
            $operation[0] = strtoupper( $operation[0] );

            // check valid sql operation statement
            if ( !in_array( $operation[0], $this->aValidOperation ) ) {
                self::error( 'invalid operation called in '.($pQueryID > 0 ? "PQuery":"query").($pQueryID > 0 ? " (ID: ".$pQueryID.")":"").' ('.$statement.') . use only ' . implode( ', ', $this->aValidOperation ) );
            }

            // sql query pass with no bind param
            if ( !empty( $statement ) && count( $parameters ) <= 0 ) {
                // set class property with pass value
                $this->sSql  = $statement;
                // set class statement handler
                $this->_oSTH = $this->prepare( $this->sSql );
                // try catch block start
                try {
                    // execute pdo statement
                    if ( $this->_oSTH->execute() ) {
                        // check operation type
                        switch ( $operation[0] ):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults      = $this->_oSTH->fetchAll();
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
                    } else {
                        // if not run pdo statement sed error
                        self::error( $this->_oSTH->errorInfo() );
                    }
                }
                catch ( PDOException $e ) {
                    self::error( $e->getMessage() . ': ' . __LINE__ );
                } // end try catch block
            } // if query pass with bind param
            else if ( !empty( $statement ) && count( $parameters ) > 0 ) {
                // set class property with pass query
                $this->sSql   = $statement;
                // set class where array
                $this->aData = $parameters;
                // set class pdo statement handler
                $this->_oSTH  = $this->prepare( $this->sSql );
                // start binding fields
                // bind pdo param
                $this->_bindPdoParam( $parameters );
                // use try catch block to get pdo error
                try {
                    // run pdo statement with bind param
                    if ( $this->_oSTH->execute() ) {
                        // check operation type
                        switch ( $operation[0] ):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults      = $this->_oSTH->fetchAll();
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
                    } else {
                        self::error( $this->_oSTH->errorInfo() );
                    }
                }
                catch ( PDOException $e ) {
                    self::error( ($pQueryID > 0 ? "PQuery (ID: ".$pQueryID.") " : "").$e->getMessage() . ': ' . __LINE__ );
                } // end try catch block to get pdo error
            } else {
                self::error( 'Query is empty..' );
            }
        }

        public function FakePQuery($statement)  {
            // clean query from white space
            $statement = trim( $statement );
            $this->sSql   = $statement;
            $parameters = Array();

            if(func_num_args() > 1) {
                $data = func_get_arg(1);

                //did we provide an array or do we have args
                if (!is_array($data)) {
                    for ($i = 1; $i < func_num_args(); $i++) {
                        $parameters[] = func_get_arg($i);
                    }
                } else {
                    $parameters = $data;
                }
            }

            // get operation type
            $operation    = explode( ' ', $statement );
            // make first word in uppercase
            $operation[0] = strtoupper( $operation[0] );

            // check valid sql operation statement
            if ( !in_array( $operation[0], $this->aValidOperation ) ) {
                self::error( "invalid operation called in query ({$statement}) . use only " . implode( ', ', $this->aValidOperation ) );
            }

            if ( !empty( $statement ) && count( $parameters ) <= 0 ){
                return $this->sSql;
            }
            else if ( !empty( $statement ) && count( $parameters ) > 0 ){
                $this->aData = $parameters;

                $this->_oSTH  = $this->prepare( $this->sSql );

                return $this->interpolateQuery();

            }
            else{
                $this->error('Query is empty..' );
            }
            return "";
        }

        public function ExecuteBuilder(SQL_Builder $query)  {
            $this->sSql = $query->Build();

            if ( !in_array(explode(" ",str_replace("_"," ",$query->getQueryType()->getName()))[0], $this->aValidOperation, true)) {
                $this->error('invalid operation called in SQLBuilderQuery' . ' (' . $this->sSql . ') . use only ' . implode(', ', $this->aValidOperation ) );
            }
            if(!empty($this->sSql) && !$query->HasBinds()){
                $this->_oSTH = $this->prepare( $this->sSql );
                try {
                    // execute pdo statement
                    if ( $this->_oSTH->execute() ) {
                        // check operation type
                        switch ( $query->getQueryType() ):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults      = $this->_oSTH->fetchAll();
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
                    $this->error($this->_oSTH->errorInfo() );

                    return $this;
                }
                catch ( PDOException $e ) {
                    $this->error($e->getMessage() . ': ' . __LINE__ );
                } // end try catch block
            }
            elseif(!empty($this->sSql)){
                $this->aData = $query->getBinds();

                $this->_oSTH  = $this->prepare( $this->sSql );

                $this->_bindPdoNameSpace($this->aData);

                try {
                    // run pdo statement with bind param
                    if ( $this->_oSTH->execute() ) {
                        // check operation type
                        switch ( $query->getQueryType() ):
                            case 'SELECT':
                                // get affected rows by select statement
                                $this->iAffectedRows = $this->_oSTH->rowCount();
                                // get pdo result array
                                $this->aResults      = $this->_oSTH->fetchAll();
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
                    } else {
                        $this->error($this->_oSTH->errorInfo() );
                    }
                }
                catch ( PDOException $e ) {
                    $this->error($e->getMessage() . ': ' . __LINE__ );
                } // end try catch block to get pdo error
            }
            else{
                $this->error('Query is empty..' );
            }
            return $this;

        }

        public function FakeExecuteBuilder(SQL_Builder $query)  {
            $this->sSql = $query->Build();

            if ( !in_array(explode(" ",str_replace("_"," ",$query->getQueryType()->getName()))[0], $this->aValidOperation, true)) {
                $this->error('invalid operation called in SQLBuilderQuery' . ' (' . $this->sSql . ') . use only ' . implode(', ', $this->aValidOperation ) );
            }
            if(!empty($this->sSql) && !$query->HasBinds()){
                return $this->sSql;
            }
            elseif(!empty($this->sSql)){
                $this->aData = $query->getBinds();

                $this->_oSTH  = $this->prepare( $this->sSql );

                return $this->interpolateQuery();

            }
            else{
                $this->error('Query is empty..' );
            }
            return "";
        }

        /**
         * MySQL SELECT Query/Statement
         *
         * @param string $sTable
         * @param array $aColumn example Array('name','lastname') or empty array for all columns
         * @param array $aWhere example Array('name =' => 'jason', 'or name = ' => 'Ben')
         * @param string $sOther
         *
         * @return multi type: array/error
         */
        public function select( $sTable = '', $aColumn = array(), $aWhere = array(), $sOther = '' ) {
            // handle column array data
            if(!is_array($aColumn)) {
                $aColumn = array();
            }
            // get field if pass otherwise use *
            $sField = count( $aColumn ) > 0 ? implode( ', ', $aColumn ) : '*';
            // check if table name not empty
            if ( !empty( $sTable ) ) {
                // if more then 0 array found in where array
                if ( count( $aWhere ) > 0 && is_array( $aWhere ) ) {
                    // set class where array
                    $this->aData = $aWhere;
                    // parse where array and get in temp var with key name and val
                    if(strstr(key($aWhere), ' ')){
                        $tmp = $this->customWhere($this->aData);
                        // get where syntax with namespace
                        $sWhere = $tmp['where'];
                    }else{
                        foreach ( $aWhere as $k => $v ) {
                            $tmp[] = "$k = :s_$k";
                        }
                        // join temp array with AND condition
                        $sWhere = implode( ' AND ', $tmp );
                    }
                    // unset temp var
                    unset( $tmp );
                    // set class sql property
                    $this->sSql = "SELECT $sField FROM `$sTable` WHERE $sWhere $sOther;";
                } else {
                    // if no where condition pass by user
                    $this->sSql = "SELECT $sField FROM `$sTable` $sOther;";
                }
                // pdo prepare statement with sql query
                $this->_oSTH = $this->prepare( $this->sSql );
                // if where condition has valid array number
                if ( count( $aWhere ) > 0 && is_array( $aWhere ) ) {
                    // bind pdo param
                    $this->_bindPdoNameSpace( $aWhere );
                } // if end here
                // use try catch block to get pdo error
                try {
                    // check if pdo execute
                    if ( $this->_oSTH->execute() ) {
                        // set class property with affected rows
                        $this->iAffectedRows = $this->_oSTH->rowCount();
                        // set class property with sql result
                        $this->aResults      = $this->_oSTH->fetchAll();
                        // close pdo
                        $this->_oSTH->closeCursor();
                        // return self object
                        return $this;
                    } else {
                        // catch pdo error
                        self::error( $this->_oSTH->errorInfo() );
                    }
                }
                catch ( PDOException $e ) {
                    // get pdo error and pass on error method
                    self::error( $e->getMessage() . ': ' . __LINE__ );
                } // end try catch block to get pdo error
            } // if table name empty
            else {
                self::error( 'Table name not found..' );
            }
        }
        /**
         * Execute PDO Insert
         *
         * @param string $sTable
         * @param array $aData
         *
         * @return number last insert ID
         */
        public function insert( $sTable, $aData = array(), $isIngnore = false ) {
            // check if table name not empty
            if ( !empty( $sTable ) ) {
                // and array data not empty
                if ( count( $aData ) > 0 && is_array( $aData ) ) {
                    // get array insert data in temp array
                    foreach ( $aData as $f => $v ) {
                        $tmp[] = ":s_$f";
                    }
                    // make name space param for pdo insert statement
                    $sNameSpaceParam = implode( ',', $tmp );
                    // unset temp var
                    unset( $tmp );
                    // get insert fields name
                    $sFields     = implode( ',', array_keys( $aData ) );
                    // set pdo insert statement in class property
                    $this->sSql  = 'INSERT ' . ($isIngnore == true ? 'IGNORE' : '') . " INTO `$sTable` ($sFields) VALUES ($sNameSpaceParam);";
                    // pdo prepare statement
                    $this->_oSTH = $this->prepare( $this->sSql );
                    // set class where property with array data
                    $this->aData = $aData;
                    // bind pdo param
                    $this->_bindPdoNameSpace( $aData );
                    // use try catch block to get pdo error
                    try {
                        // execute pdo statement
                        if ( $this->_oSTH->execute() ) {
                            // set class property with last insert id
                            $this->iLastId = $this->lastInsertId();
                            // close pdo
                            $this->_oSTH->closeCursor();
                            // return this object
                            return $this;
                        } else {
                            $this->error($this->_oSTH->errorInfo() );
                        }
                    }
                    catch ( PDOException $e ) {
                        // get pdo error and pass on error method
                        $this->error($e->getMessage() . ': ' . __LINE__ );
                    }
                } else {
                    $this->error('Data not in valid format..' );
                }
            } else {
                $this->error('Table name not found..' );
            }
        }
        /**
         * Execute PDO Insert as Batch Data
         *
         * @param string $sTable mysql table name
         * @param array $aData mysql insert array data
         * @param boolean $safeModeInsert set true if want to use pdo bind param
         *
         * @return number last insert ID
         */
        public function insertBatch( $sTable, $aData = array(), $safeModeInsert = true, $isIngnore = false ) {
            // PDO transactions start
            $this->start();
            // check if table name not empty
            if ( !empty( $sTable ) ) {
                // and array data not empty
                if ( count( $aData ) > 0 && is_array( $aData ) ) {
                    // get array insert data in temp array
                    foreach ( $aData[0] as $f => $v ) {
                        $tmp[] = ":s_$f";
                    }
                    // make name space param for pdo insert statement
                    $sNameSpaceParam = implode( ', ', $tmp );
                    // unset temp var
                    unset( $tmp );
                    // get insert fields name
                    $sFields = implode( ', ', array_keys( $aData[0] ) );
                    // handle safe mode. If it is set as false means user not using bind param in pdo
                    if ( !$safeModeInsert ) {
                        // set pdo insert statement in class property
                        $this->sSql = 'INSERT ' . ($isIngnore == true ? 'IGNORE' : '') . " INTO `$sTable` ($sFields) VALUES ";
                        foreach ( $aData as $key => $value ) {
                            $this->sSql .= '(' . "'" . implode( "', '", array_values( $value ) ) . "'" . '), ';
                        }
                        $this->sSql  = rtrim( $this->sSql, ', ' );
                        // return this object
                        // return $this;
                        // pdo prepare statement
                        $this->_oSTH = $this->prepare( $this->sSql );
                        // start try catch block
                        try {
                            // execute pdo statement
                            if ( $this->_oSTH->execute() ) {
                                // store all last insert id in array
                                $this->iAllLastId[] = $this->lastInsertId();
                            } else {
                                $this->error($this->_oSTH->errorInfo() );
                            }
                        }
                        catch ( PDOException $e ) {
                            // get pdo error and pass on error method
                            $this->error($e->getMessage() . ': ' . __LINE__ );
                            // PDO Rollback
                            $this->back();
                        } // end try catch block
                        // PDO Commit
                        $this->end();
                        // close pdo
                        $this->_oSTH->closeCursor();
                        // return this object
                        return $this;
                    }
                    // end here safe mode
                    // set pdo insert statement in class property
                    $this->sSql  = 'INSERT ' . ($isIngnore == true ? 'IGNORE' : '') . " INTO `$sTable` ($sFields) VALUES ($sNameSpaceParam);";
                    // pdo prepare statement
                    $this->_oSTH = $this->prepare( $this->sSql );
                    // set class property with array
                    $this->aData = $aData;
                    // set batch insert flag true
                    $this->batch = true;
                    // parse batch array data
                    foreach ( $aData as $key => $value ) {
                        // bind pdo param
                        $this->_bindPdoNameSpace( $value );
                        try {
                            // execute pdo statement
                            if ( $this->_oSTH->execute() ) {
                                // set class property with last insert id as array
                                $this->iAllLastId[] = $this->lastInsertId();
                            } else {
                                self::error( $this->_oSTH->errorInfo() );
                                // on error PDO Rollback
                                $this->back();
                            }
                        }
                        catch ( PDOException $e ) {
                            // get pdo error and pass on error method
                            $this->error($e->getMessage() . ': ' . __LINE__ );
                            // on error PDO Rollback
                            $this->back();
                        }
                    }
                    // fine now PDO Commit
                    $this->end();
                    // close pdo
                    $this->_oSTH->closeCursor();
                    // return this object
                    return $this;
                } else {
                    $this->error('Data not in valid format..' );
                }
            } else {
                $this->error('Table name not found..' );
            }
        }
        /**
         * Execute PDO Update Statement
         * Get No OF Affected Rows updated
         *
         * @param string $sTable
         * @param array $aData
         * @param array $aWhere
         * @param string $sOther
         *
         * @return number
         */
        public function update( $sTable = '', $aData = array(), $aWhere = array(), $sOther = '' ) {
            // if table name is empty
            if ( !empty( $sTable ) ) {
                // check if array data and where array is more then 0
                if ( count( $aData ) > 0 && count( $aWhere ) > 0 ) {
                    // parse array data and make a temp array
                    foreach ( $aData as $k => $v ) {
                        $tmp[] = "$k = :s_$k";
                    }
                    // join temp array value with ,
                    $sFields = implode( ', ', $tmp );
                    // delete temp array from memory
                    unset( $tmp );
                    // parse where array and store in temp array
                    foreach ( $aWhere as $k => $v ) {
                        $tmp[] = "$k = :s_$k";
                    }
                    $this->aData = $aData;
                    $this->aWhere = $aWhere;
                    // join where array value with AND operator and create where condition
                    $sWhere = implode( ' AND ', $tmp );
                    // unset temp array
                    unset( $tmp );
                    // make sql query to update
                    $this->sSql  = "UPDATE `$sTable` SET $sFields WHERE $sWhere $sOther;";
                    // on PDO prepare statement
                    $this->_oSTH = $this->prepare( $this->sSql );
                    // bind pdo param for update statement
                    $this->_bindPdoNameSpace( $aData );
                    // bind pdo param for where clause
                    $this->_bindPdoNameSpace( $aWhere );
                    // try catch block start
                    try {
                        // if PDO run
                        if ( $this->_oSTH->execute() ) {
                            // get affected rows
                            $this->iAffectedRows = $this->_oSTH->rowCount();
                            // close PDO
                            $this->_oSTH->closeCursor();
                            // return self object
                            return $this;
                        } else {
                            $this->error($this->_oSTH->errorInfo() );
                        }
                    }
                    catch ( PDOException $e ) {
                        // get pdo error and pass on error method
                        $this->error($e->getMessage() . ': ' . __LINE__ );
                    } // try catch block end
                } else {
                    $this->error('update statement not in valid format..' );
                }
            } else {
                $this->error('Table name not found..' );
            }
        }
        /**
         * Execute PDO Delete Query
         *
         * @param string $sTable
         * @param array $aWhere
         * @param string $sOther
         *
         * @return object PDO object
         */
        /*public function delete( $sTable, $aWhere = array(), $sOther = '' ) {
            // if table name not pass
            if ( !empty( $sTable ) ) {
                // check where condition array length
                if ( count( $aWhere ) > 0 && is_array( $aWhere ) ) {
                    // set class where array
                    $this->aData = $aWhere;
                    // parse where array and get in temp var with key name and val
                    if(strstr(key($aWhere), ' ')){
                        $tmp = $this->customWhere($this->aData);
                        // get where syntax with namespace
                        $sWhere = $tmp['where'];
                    }else{
                        foreach ( $aWhere as $k => $v ) {
                            $tmp[] = "$k = :s_$k";
                        }
                        // join temp array with AND condition
                        $sWhere = implode( ' AND ', $tmp );
                    }
                    // delete temp array
                    unset( $tmp );
                    // set DELETE PDO Statement
                    $this->sSql  = "DELETE FROM `$sTable` WHERE $sWhere $sOther;";
                    // Call PDo Prepare Statement
                    $this->_oSTH = $this->prepare( $this->sSql );
                    // if where condition has valid array number
                    if ( count( $aWhere ) > 0 && is_array( $aWhere ) ) {
                        // bind pdo param
                        $this->_bindPdoNameSpace( $aWhere );
                    } // if end here

                    // Use try Catch
                    try {
                        if ( $this->_oSTH->execute() ) {
                            // get affected rows
                            $this->iAffectedRows = $this->_oSTH->rowCount();
                            // close pdo
                            $this->_oSTH->closeCursor();
                            // return this object
                            return $this;
                        } else {
                            self::error( $this->_oSTH->errorInfo() );
                        }
                    }
                    catch ( PDOException $e ) {
                        // get pdo error and pass on error method
                        self::error( $e->getMessage() . ': ' . __LINE__ );
                    } // end try catch here
                } else {
                    self::error( 'Not a valid where condition..' );
                }
            } else {
                self::error( 'Table name not found..' );
            }
        }*/

        public function delete( $sTable, $aWhere = '', $sOther = '' ) {
            // if table name not pass
            if ( !empty( $sTable ) ) {
                // check where condition array length
                if ( !empty($aWhere ) ) {
                    // set DELETE PDO Statement
                    $this->sSql  = "DELETE FROM `$sTable` WHERE $aWhere $sOther;";
                    // Call PDo Prepare Statement
                    $this->_oSTH = $this->prepare( $this->sSql );
                    // bind delete where param
                    $this->_bindPdoNameSpace( $aWhere );
                    // set array data
                    $this->aData = $aWhere;
                    // Use try Catch
                    try {
                        if ( $this->_oSTH->execute() ) {
                            // get affected rows
                            $this->iAffectedRows = $this->_oSTH->rowCount();
                            // close pdo
                            $this->_oSTH->closeCursor();
                            // return this object
                            return $this;
                        } else {
                            $this->error($this->_oSTH->errorInfo() );
                        }
                    }
                    catch ( PDOException $e ) {
                        // get pdo error and pass on error method
                        $this->error($e->getMessage() . ': ' . __LINE__ );
                    } // end try catch here
                } else {
                    $this->error('Not a valid where condition..' );
                }
            } else {
                $this->error('Table name not found..' );
            }
        }

        public function delete_All( $sTable, $sOther = '' ) {
            // if table name not pass
            if ( !empty( $sTable ) ) {
                // check where condition array length
                // set DELETE PDO Statement
                $this->sSql  = "DELETE FROM `$sTable` $sOther;";
                // Call PDo Prepare Statement
                $this->_oSTH = $this->prepare( $this->sSql );
                // bind delete where param
                // Use try Catch
                try {
                    if ( $this->_oSTH->execute() ) {
                        // get affected rows
                        $this->iAffectedRows = $this->_oSTH->rowCount();
                        // close pdo
                        $this->_oSTH->closeCursor();
                        // return this object
                        return $this;
                    } else {
                        $this->error($this->_oSTH->errorInfo() );
                    }
                }
                catch ( PDOException $e ) {
                    // get pdo error and pass on error method
                    $this->error($e->getMessage() . ': ' . __LINE__ );
                } // end try catch here
            } else {
                $this->error('Table name not found..' );
            }
        }
        /**
         * Return PDO Query results array/json/xml type
         *
         * @param string $type
         *
         * @return array|mixed
         */
        public function results( $type = 'array' ) {
            switch ( $type ) {
                case 'array':
                    // return array data
                    return $this->aResults;
                    break;
                case 'xml':
                    //send the xml header to the browser
                    header( "Content-Type:text/xml" );
                    // return xml content
                    return $this->helper()->arrayToXml( $this->aResults );
                    break;
                case 'json':
                    // set header as json
                    header( 'Content-type: application/json; charset="utf-8"' );
                    // return json encoded data
                    return json_encode( $this->aResults );
                    break;
            }
        }
        /**
         * Get Total Number Of Records in Requested Table
         *
         * @param string $sTable
         * @param string $sWhere
         * @return number
         */
        public function count( $sTable = '', $sWhere = '' ) {
            // if table name not pass
            if ( !empty( $sTable ) ) {
                if(empty($sWhere)){
                    // create count query
                    $this->sSql  = "SELECT COUNT(*) AS NUMROWS FROM `$sTable`;";
                }else{
                    // create count query
                    $this->sSql  = "SELECT COUNT(*) AS NUMROWS FROM `$sTable` WHERE $sWhere;";
                }
                // pdo prepare statement
                $this->_oSTH = $this->prepare( $this->sSql );
                try {
                    if ( $this->_oSTH->execute() ) {
                        // fetch array result
                        $this->aResults = $this->_oSTH->fetch();
                        // close pdo
                        $this->_oSTH->closeCursor();
                        // return number of count
                        return $this->aResults['NUMROWS'];
                    } else {
                        $this->error($this->_oSTH->errorInfo() );
                    }
                }
                catch ( PDOException $e ) {
                    // get pdo error and pass on error method
                    $this->error($e->getMessage() . ': ' . __LINE__ );
                }
            } else {
                $this->error('Table name not found..' );
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
        public function CountJoins($sTable = '', $tableAs  = '', $sWhere = '', array $joins = array(), $type_type = 'INNER JOIN', $binds = array()) {
            $sqljoin = implode(" {$type_type} ", $joins );
            if(!empty($sqljoin)){
                $sqljoin = "{$type_type} {$sqljoin}";
            }
            // if table name not pass
            if ( !empty( $sTable ) ) {
                if(empty($sWhere)){
                    // create count query
                    $this->sSql  = "SELECT COUNT(*) AS NUMROWS FROM `$sTable` {$tableAs} {$sqljoin};";
                }else{
                    // create count query
                    $this->sSql  = "SELECT COUNT(*) AS NUMROWS FROM `$sTable` {$tableAs} {$sqljoin} WHERE $sWhere;";
                }
                // pdo prepare statement
                $this->_oSTH = $this->prepare( $this->sSql );
                try {
                    $this->_bindPdoParam($binds);
                    if ( $this->_oSTH->execute() ) {
                        // fetch array result
                        $this->aResults = $this->_oSTH->fetch();
                        // close pdo
                        $this->_oSTH->closeCursor();
                        // return number of count
                        return $this->aResults['NUMROWS'];
                    }

                    $this->error($this->_oSTH->errorInfo() );
                }
                catch ( PDOException $e ) {
                    // get pdo error and pass on error method
                    $this->error($e->getMessage() . ': ' . __LINE__ );
                }
            } else {
                $this->error('Table name not found..' );
            }
        }

        /**
         * Return Table Fields of Requested Table
         *
         * @param string $sTable
         *
         * @return array Field Type and Field Name
         */
        public function describe( $sTable = '' ) {
            $this->sSql = $sSql  = "DESC $sTable;";
            $this->_oSTH = $this->prepare( $sSql );
            $this->_oSTH->execute();
            $aColList = $this->_oSTH->fetchAll();
            foreach ( $aColList as $key ) {
                $aField[] = $key['Field'];
                $aType[]  = $key['Type'];
            }
            return array_combine( $aField, $aType );
        }

        /**
         *
         * @param array $array_data
         * @return array
         */
        public function customWhere ($array_data = array()){
            $syntax = '';
            foreach ($array_data as $key => $value) {
                $key = trim($key);
                if(strstr($key, ' ')){
                    $array = explode(' ',$key);
                    if(count($array)=='2'){
                        $random = '';
                        $field = $array[0];
                        $operator  = $array[1];
                        $tmp[] = "$field $operator :s_$field"."$random";
                        $syntax .= " $field $operator :s_$field"."$random ";
                    }elseif(count($array)=='3'){
                        $random = '';
                        $condition = $array[0];
                        $field = $array[1];
                        $operator = $array[2];
                        $tmp[] = "$condition $field $operator :s_$field"."$random";
                        $syntax .= " $condition $field $operator :s_$field"."$random ";
                    }
                }
            }
            return array(
                'where' => $syntax,
                'bind' => implode(' ',$tmp)
            );
        }

        /**
         * PDO Bind Param with :namespace
         * @param array $array
         */
        private function _bindPdoNameSpace( array $array = array() ) {
            if(strstr(key($array), ' ')){
                // bind array data in pdo
                foreach ( $array as $f => $v ) {
                    // get table column from array key
                    $field = $this->getFieldFromArrayKey($f);
                    // check pass data type for appropriate field
                    switch ( gettype( $array[$f] ) ):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam( ':' . $field, $array[$f], PDO::PARAM_STR );
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam( ':' . $field, $array[$f], PDO::PARAM_INT );
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam( ':' . $field, $array[$f], PDO::PARAM_BOOL );
                            break;
                        default:
                            $this->_oSTH->bindParam( ':' . $field, $array[$f], PDO::PARAM_STR );
                            break;
                    endswitch;
                } // end for each here
            }else{
                // bind array data in pdo
                foreach ( $array as $f => $v ) {
                    // check pass data type for appropriate field
                    switch ( gettype( $array[$f] ) ):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam( ':' . $f, $array[$f], PDO::PARAM_STR );
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam( ':' . $f, $array[$f], PDO::PARAM_INT );
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam( ':' . $f, $array[$f], PDO::PARAM_BOOL );
                            break;
                        case 'NULL':
                            $NULL_VAR = null;
                            $this->_oSTH->bindParam(':' . $f, $NULL_VAR, PDO::PARAM_INT);
                            break;
                        default:
                            $this->_oSTH->bindParam( ':' . $f, $array[$f], PDO::PARAM_STR );
                            break;
                    endswitch;
                } // end for each here
            }
        }

        private function _bindPdoNameSpace1( $array = array() ) {
            if(strstr(key($array), ' ')){
                // bind array data in pdo
                foreach ( $array as $f => $v ) {
                    // get table column from array key
                    $field = $this->getFieldFromArrayKey($f);
                    // check pass data type for appropriate field
                    switch ( gettype( $array[$f] ) ):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam( ":s" . "_" . "$field", $array[$f], PDO::PARAM_STR );
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam( ":s" . "_" . "$field", $array[$f], PDO::PARAM_INT );
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam( ":s" . "_" . "$field", $array[$f], PDO::PARAM_BOOL );
                            break;
                    endswitch;
                } // end for each here
            }else{
                // bind array data in pdo
                foreach ( $array as $f => $v ) {
                    // check pass data type for appropriate field
                    switch ( gettype( $array[$f] ) ):
                        // is string found then pdo param as string
                        case 'string':
                            $this->_oSTH->bindParam( ":s" . "_" . "$f", $array[$f], PDO::PARAM_STR );
                            break;
                        // if int found then pdo param set as int
                        case 'integer':
                            $this->_oSTH->bindParam( ":s" . "_" . "$f", $array[$f], PDO::PARAM_INT );
                            break;
                        // if boolean found then set pdo param as boolean
                        case 'boolean':
                            $this->_oSTH->bindParam( ":s" . "_" . "$f", $array[$f], PDO::PARAM_BOOL );
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
         * @param array $array
         */
        private function _bindPdoParam( $array = array() ) {
            // bind array data in pdo
            foreach ( $array as $f => $v ) {
                // check pass data type for appropriate field

                switch ( gettype( $array[$f] ) ):
                    // is string found then pdo param as string
                    case 'string':
                        $this->_oSTH->bindParam( $f + 1, $array[$f], PDO::PARAM_STR );
                        break;
                    // if int found then pdo param set as int
                    case 'integer':
                        $this->_oSTH->bindParam( $f + 1, $array[$f], PDO::PARAM_INT );
                        break;
                    // if boolean found then set pdo param as boolean
                    case 'boolean':
                        $this->_oSTH->bindParam( $f + 1, $array[$f], PDO::PARAM_BOOL );
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
        public function error( $msg ) {


            // log set as true
            if ( $this->log ) {
                // show executed query with error
                $this->showQuery();
                // die code
                $this->helper()->errorBox($msg);
            } else {
                // show error message in log file
                file_put_contents( self::ERROR_LOG_FILE, date( 'Y-m-d h:m:s' ) . ' :: '.$this->FriendlyName.' :: '. $msg . "\n", FILE_APPEND );
                // die with user message
                $this->helper()->error();
            }
        }

        /**
         * Show executed query on call
         * @param boolean $logfile set true if wanna log all query in file
         * @return PDOAdapter
         */
        public function showQuery($logfile=false) {
            if(!$logfile){
                if(Misc::IsCLI()){
                    Misc::Debug_Print(3,$this->FriendlyName,"    Executed Query -> ",$this->interpolateQuery());
                }else {
                    echo "<div style='color:#990099; border:1px solid #777; padding:2px; background-color: #E5E5E5; text-align: center;'>" . $this->FriendlyName . "</div>\r\n";
                    echo "<div style='color:#990099; border:1px solid #777; padding:2px; background-color: #E5E5E5;'>\r\n";
                    echo " Executed Query -> <span style='color:#008000;'> \r\n";
                    echo $this->helper()->formatSQL($this->interpolateQuery()) . "\r\n";
                    echo "</span></div>\r\n";
                }
                return $this;
            }else{
                // show error message in log file
                file_put_contents( self::SQL_LOG_FILE, date( 'Y-m-d h:m:s' ) . ' :: ' .$this->FriendlyName.' :: '. $this->interpolateQuery() . "\n", FILE_APPEND );
                return $this;
            }
        }

        /**
         * Replaces any parameter placeholders in a query with the value of that
         * parameter. Useful for debugging. Assumes anonymous parameters from
         *
         * @return mixed
         */
        protected function interpolateQuery() {
            $sql = $this->sSql;// $this->_oSTH->queryString;
            // handle insert batch data
            if(!$this->batch){
                $params = ( ( is_array( $this->aData ) ) && ( count( $this->aData ) > 0 ) ) ? $this->aData : $this->sSql;
                if ( is_array( $params ) ) {
                    # build a regular expression for each parameter
                    foreach ( $params as $key => $value ) {
                        if(strstr($key, ' ')){
                            $real_key = $this->getFieldFromArrayKey($key);
                            // update param value with quotes, if string value
                            $params[$key] = is_string( $value ) ? '"' . $value . '"' : $value;
                            // make replace array
                            $keys[]       = is_string( $real_key ) ? '/:' . $real_key . '/' : '/[?]/';
                        }else{
                            // update param value with quotes, if string value
                            $params[$key] = is_string( $value ) ? '"' . $value . '"' : $value;
                            // make replace array
                            $keys[]       = is_string( $key ) ? '/:' . $key . '/' : '/[?]/';
                        }
                    }
                    $sql = preg_replace( $keys, $params, $sql, 1, $count );

                    if(strstr($sql,':')){
                        foreach ( $this->aWhere as $key => $value ) {
                            if(strstr($key, ' ')){
                                $real_key = $this->getFieldFromArrayKey($key);
                                // update param value with quotes, if string value
                                $params[$key] = is_string( $value ) ? '"' . $value . '"' : $value;
                                // make replace array
                                $keys[]       = is_string( $real_key ) ? '/:' . $real_key . '/' : '/[?]/';
                            }else{
                                // update param value with quotes, if string value
                                $params[$key] = is_string( $value ) ? '"' . $value . '"' : $value;
                                // make replace array
                                $keys[]       = is_string( $key ) ? '/:' . $key . '/' : '/[?]/';
                            }
                        }
                        $sql = preg_replace( $keys, $params, $sql, 1, $count );
                    }
                    return $sql;
                    #trigger_error('replaced '.$count.' keys');
                } else {
                    return $params;
                }
            }else{
                $params_batch = ( ( is_array( $this->aData ) ) && ( count( $this->aData ) > 0 ) ) ? $this->aData : $this->sSql;
                $batch_query = '';
                if ( is_array( $params_batch ) ) {
                    # build a regular expression for each parameter
                    foreach ($params_batch as $keys => $params){
                        //echo $params."\r\n";
                        foreach ( $params as $key => $value ) {
                            if(strstr($key, ' ')){
                                $real_key = $this->getFieldFromArrayKey($key);
                                // update param value with quotes, if string value
                                $params[$key] = is_string( $value ) ? '"' . $value . '"' : $value;
                                // make replace array
                                $array_keys[]       = is_string( $real_key ) ? '/:s_' . $real_key . '/' : '/[?]/';
                            }else{
                                // update param value with quotes, if string value
                                $params[$key] = is_string( $value ) ? '"' . $value . '"' : $value;
                                // make replace array
                                $array_keys[]       = is_string( $key ) ? '/:s_' . $key . '/' : '/[?]/';
                            }
                        }
                        $batch_query .= "<br />".preg_replace( $array_keys, $params, $sql, 1, $count );
                    }
                    return $batch_query;
                    #trigger_error('replaced '.$count.' keys');
                } else {
                    return $params_batch;
                }
            }
        }
        /**
         * Return real table column from array key
         * @param array $array_key
         * @return mixed
         */
        public function getFieldFromArrayKey($array_key=array()){
            // get table column from array key
            $key_array = explode(' ',$array_key);
            // check no of chunk
            return (count($key_array)=='2') ? $key_array[0] : ((count($key_array)> 2) ? $key_array[1] : $key_array[0]);
        }
        /**
         * Set PDO Error Mode to get an error log file or true to show error on screen
         *
         * @param bool $mode
         */
        public function setErrorLog( $mode = false ) {
            $this->log = $mode;
            return $this;
        }
    }



    class PDOHelper {
        /**
         * function definition to convert array to xml
         * send an array and get xml
         *
         * @param array $arrayData
         *
         * @return string
         */
        public function arrayToXml( $arrayData = array() ) {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
            $xml .= "<root>";
            foreach ( $arrayData as $key => $value ) {
                $xml .= "<xml_data>";
                if ( is_array( $value ) ) {
                    foreach ( $value as $k => $v ) {
                        //$k holds the table column name
                        $xml .= "<$k>";
                        //embed the SQL data in a CDATA element to avoid XML entity issues
                        $xml .= "<![CDATA[$v]]>";
                        //and close the element
                        $xml .= "</$k>";
                    }
                } else {
                    //$key holds the table column name
                    $xml .= "<$key>";
                    //embed the SQL data in a CDATA element to avoid XML entity issues
                    $xml .= "<![CDATA[$value]]>";
                    //and close the element
                    $xml .= "</$key>";
                }
                $xml .= "</xml_data>";
            }
            $xml .= "</root>";
            return $xml;
        }
        /**
         * Format the SQL Query
         *
         * @param $sql string
         * @return mixed
         */
        public function formatSQL( $sql = '' ) {
            // Reserved SQL Keywords Data
            $reserveSqlKey = "select|insert|update|delete|truncate|drop|create|add|except|percent|all|exec|plan|alter|execute|precision|and|exists|primary|any|exit|print|as|fetch|proc|asc|file|procedure|authorization|fillfactor|public|backup|for|raiserror|begin|foreign|read|between|freetext|readtext|break|freetexttable|reconfigure|browse|from|references|bulk|full|replication|by|function|restore|cascade|goto|restrict|case|grant|return|check|group|revoke|checkpoint|having|right|close|holdlock|rollback|clustered|identity|rowcount|coalesce|identity_insert|rowguidcol|collate|identitycol|rule|column|if|save|commit|in|schema|compute|index|select|constraint|inner|session_user|contains|insert|set|containstable|intersect|setuser|continue|into|shutdown|convert|is|some|create|join|statistics|cross|key|system_user|current|kill|table|current_date|left|textsize|current_time|like|then|current_timestamp|lineno|to|current_user|load|top|cursor|national|tran|database|nocheck|transaction|dbcc|nonclustered|trigger|deallocate|not|truncate|declare|null|tsequal|default|nullif|union|delete|of|unique|deny|off|update|desc|offsets|updatetext|disk|on|use|distinct|open|user|distributed|opendatasource|values|double|openquery|varying|drop|openrowset|view|dummy|openxml|waitfor|dump|option|when|else|or|where|end|order|while|errlvl|outer|with|escape|over|writetext|absolute|overlaps|action|pad|ada|partial|external|pascal|extract|position|allocate|false|prepare|first|preserve|float|are|prior|privileges|fortran|assertion|found|at|real|avg|get|global|relative|go|bit|bit_length|both|rows|hour|cascaded|scroll|immediate|second|cast|section|catalog|include|char|session|char_length|indicator|character|initially|character_length|size|input|smallint|insensitive|space|int|sql|collation|integer|sqlca|sqlcode|interval|sqlerror|connect|sqlstate|connection|sqlwarning|isolation|substring|constraints|sum|language|corresponding|last|temporary|count|leading|time|level|timestamp|timezone_hour|local|timezone_minute|lower|match|trailing|max|min|translate|date|minute|translation|day|module|trim|month|true|dec|names|decimal|natural|unknown|nchar|deferrable|next|upper|deferred|no|usage|none|using|describe|value|descriptor|diagnostics|numeric|varchar|disconnect|octet_length|domain|only|whenever|work|end-exec|write|year|output|zone|exception|free|admin|general|after|reads|aggregate|alias|recursive|grouping|ref|host|referencing|array|ignore|result|returns|before|role|binary|initialize|rollup|routine|blob|inout|row|boolean|savepoint|breadth|call|scope|search|iterate|large|sequence|class|lateral|sets|clob|less|completion|limit|specific|specifictype|localtime|constructor|localtimestamp|sqlexception|locator|cube|map|current_path|start|current_role|state|cycle|modifies|statement|data|modify|static|structure|terminate|than|nclob|depth|new|deref|destroy|treat|destructor|object|deterministic|old|under|dictionary|operation|unnest|ordinality|out|dynamic|each|parameter|variable|equals|parameters|every|without|path|postfix|prefix|preorder";
            // convert in array
            $list = explode('|',$reserveSqlKey);
            foreach ($list as &$verb) {
                $verb = '/\b' . preg_quote($verb, '/') . '\b/';
            }
            $regex_sign = array('/\b','\b/');
            // replace matching words
            return str_replace($regex_sign,'',preg_replace( $list, array_map( array(
                $this,
                'highlight_sql'
            ), $list ), strtolower( $sql ) ));
        }
        /**
         * Coloring for MySQL reserved keywords
         *
         * @param $param
         * @return string
         */
        public function highlight_sql( $param ) {
            return "<span style='color:#990099; font-weight:bold; text-transform: uppercase;'>$param</span>";
        }
        /**
         * Get HTML Table with Data
         * Send complete array data and get an HTML table with mysql data
         *
         * @param array $aColList Result Array data
         * @return string HTML Table with data
         */
        public function displayHtmlTable( $aColList = array() ) {
            $r        = '';
            if ( count( $aColList ) > 0 ) {
                $r .= '<table border="1">';
                $r .= '<thead>';
                $r .= '<tr>';
                foreach ( $aColList[0] as $k => $v ) {
                    $r .= '<td>' . $k . '</td>';
                }
                $r .= '</tr>';
                $r .= '</thead>';
                $r .= '<tbody>';
                foreach ( $aColList as $record ) {
                    $r .= '<tr>';
                    foreach ( $record as $data ) {
                        $r .= '<td>' . $data . '</td>';
                    }
                    $r .= '</tr>';
                }
                $r .= '</tbody>';
                $r .= '<table>';
            } else {
                $r .= '<div class="no-results">No results found for query.</div>';
            }
            return $r;
        }

        /**
         * Check That a Array is Associative or Not
         * @param array $array
         * @return bool true/false
         */
        public function isAssocArray( $array = array() ) {
            return array_keys( $array ) !== range( 0, count( $array ) - 1 );
        }
        /**
         * Function to print array with pre tag
         *
         * @param array $array
         */
        public function PA( $array ) {
            echo '<pre>', print_r( $array, true ), '</pre>\n';
        }
        /**
         * Show Error to user
         */
        public function error(){
            $style = "style='color:#333846; border:1px solid #777; padding:2px; background-color: #FFC0CB;'";
            die( "<div $style >ERROR: error occurred. Please, Check you error log file.</div>" );
        }
        /**
         * Show Error Array Data and stop code execution
         * @param array $data
         */
        public function errorBox( $data = array() ) {
            if(Misc::IsCLI()){
                Misc::Debug_Print(2,"ERROR: ",$data);
            }else {
                $style = "style='color:#333846; border:1px solid #777; padding:2px; background-color: #FFC0CB;'";
                die( "<div $style >ERROR:" . json_encode( $data ) . "</div>" );
            }
        }

    }
    ?>