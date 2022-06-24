<?php namespace F1;

use PDO;
use PDOStatement;
use Exception;

/**
 * F1 - DB Class
 * 
 * PDO Database Management class with layered Query Builder
 * to easily build-up different SQL queries based on variable 
 * request scenarios / logic sequences.
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 3.3.0 - 23 Jun 2022
 *
 */
class DB extends PDO
{
  public $log = array();
  protected $connection = array();

  /**
   * Initialize a new database connection and
   * DB service instance.
   *
   * @param array $config
   *   $config = [
   *    'DBHOST'=>'...',
   *    'DBNAME'=>'...',
   *    'DBUSER'=>'...',
   *    'DBPASS'=>'...'
   *   ];
   */
  public function __construct( $config = null )
  {
    if ( $config ) { $this->connect($config); }
  }

  public function connect( $config = null )
  {
    if ( $config and is_array( $config ) ) { $this->config = $config; }
    else { throw new Exception( 'Database connect error. No config.', 500 ); }
    $dbHost = $this->config[ 'DBHOST' ];
    $dbName = $this->config[ 'DBNAME' ];
    $dbUser = $this->config[ 'DBUSER' ];
    $dbPass = $this->config[ 'DBPASS' ];
    $dsn = "mysql:host=$dbHost;dbname=$dbName";
    $opts = [ PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"' ];
    parent::__construct( $dsn, $dbUser, $dbPass, $opts );
    $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );    
  }

  public function beginTransaction()
  {
    return parent::beginTransaction();
  }

  public function commit()
  {
    return parent::commit();
  }

  public function rollBack()
  {
    return parent::rollBack();
  }

  /**
   * Executes SQL commands (For commands without quick methods)
   *
   * Examples:
   * ---------
   * $db->cmd( 'SET GLOBAL log_output = ?', [ 'FILE' ] );
   * $db->cmd( 'SET GLOBAL general_log_file = ?', [ 'nm_mysql.log' ] );
   * $db->cmd( 'SET GLOBAL general_log = "ON"' );
   *
   * @param string $cmdSqlStr
   * @param array   $cmdParams Array of placeholder values.
   * @return integer Number of rows affected.
   */
  public function cmd( $cmdSqlStr, $cmdParams = null )
  {
    if( $cmdParams )
    {
      $preparedCommand = $this->prepare( $cmdSqlStr );
      $affectedRows = $preparedCommand->execute( $cmdParams );
    }
    else
    {
      $affectedRows = parent::exec( $cmdSqlStr );
    }
    return $affectedRows;
  }

  /**
   * @param  string $tableName
   * @param  string $schema          "($FieldDefs) $tblConfig;"
   * @param  boolean $checkIfExists  Prevent DB exception if it already exists!
   * @return boolean 0               Not used
   */
  public function createTable( $tableName, $schema = null, $checkIfExists = 0 )
  {
    $cmd = 'CREATE TABLE ';
    if ($checkIfExists) { $cmd .= 'IF NOT EXISTS '; }
    return $this->cmd("$cmd `$tableName` $schema");
  }

  /**
   * Incremental (multi-stage) PDO query builder and results fetcher.
   *
   * $db->query() makes SQL easier and cleaner to write when you have
   * multiple conditional segments that depend on the current state or
   * request.
   *
   * NOTE: We override PDO::query(). If you ever need to use the
   * legacy implementation, call db->query() with `options`='legacy'
   *
   * @param string $tablesExpr Required e.g 'tblusers', 'tblusers u'
   * @param array|string $options Select LEGACY mode or store metadata.
   * @return PDOQuery|PDOStatement
   */
  public function query( $tablesExpr, $options = null )
  {
    if( $options == 'legacy' )
    {
      $querySqlStr = $tablesExpr;
      return parent::query( $querySqlStr );
    }
    return new PDOQuery( $this, $tablesExpr, $options );
  }

  /**
   * Syntax sugar. Used to create nested queries.
   * The same as $db->query() except for OPTIONAL $tablesExpr.
   *
   * @param string $tablesExpr Optional
   * @param array $options Optional
   * @return PDOQuery
   */
  public function subQuery( $tablesExpr = null, $options = null )
  {
    return new PDOQuery( $this, $tablesExpr, $options );
  }

  /**
   * Inserts a single or multiple data rows into a database table.
   *  - Auto detects multi-row insert.
   *
   * NOTE: When inserting multiple rows at a time, you might want to
   * considder wrapping your call in a try/catch with DB transaction control.
   * I.e. db->beginTransaction, db->commit, db->rollBack.
   *
   * @param string $tablesExpr
   * @param array|object $data
   * @return boolean success
   */
  public function insertInto( $tablesExpr, $data )
  {
    if( ! $row ) { return [ 'insert' => 0, 'failed' => 0 ]; }
    if( $this->isMultiRow( $data ) )
    {
      $this->log[] = 'Multi-row = TRUE';
      $rows = $data;
    }
    else
    {
      $rows = [ $data ];
    }
    $i = 0;
    $sql = '';
    $qMarks = [];
    $colNames = [];
    $affectedRows = [ 'insert' => 0, 'failed' => 0 ];
    foreach( $rows as $r )
    {
      if( is_object( $r ) ) { $r = (array) $r; }
      if( $i == 0 )
      {
        foreach( $r as $colName => $colValue )
        {
          $qMarks[] = '?';
          $colNames[] = $colName;
        }
        $qMarksSql = implode( ',', $qMarks );
        $colNamesSql = implode( ',', $colNames );
        $sql = "INSERT INTO {$tablesExpr} ({$colNamesSql}) VALUES ({$qMarksSql})";
        $preparedPdoStatement = $this->prepare( $sql );
        $this->log[] = 'Batch stmt: ' . $sql;
      }
      // Execute the same prepared statement for each row provided!
      if( $preparedPdoStatement->execute( array_values( $r ) ) )
      {
        $affectedRows[ 'insert' ]++;
      }
      else {
        $affectedRows[ 'failed' ]++;
      }
      $i++;
    }
    $this->log[] = 'affectedRows: ' . print_r( $affectedRows, true );
    return $affectedRows;
  } // end: insertInto

  /**
   * Batch update OR insert multiple database rows in one go.
   *
   * NOTE: For UpdateOrInsert to work, the target database table MUST have
   * a PRIMARY KEY / UNIQUE constraint on at least one or more columns.
   * The values of columns with contraints MUST also be included in
   * the data provided! e.g. [[id=>1, ...], ...] where id == PK.
   *
   * NOTE: UPDATE ONLY mode is enabled when we define the `where` option!
   * See: db->batchUpdate() for more info.
   *
   * NOTE: When updating multiple rows at a time, you might want to
   * considder wrapping your call in a try/catch with DB transaction control.
   * I.e. db->beginTransaction, db->commit, db->rollBack.
   *
   * @param string $tablesExpr
   * @param array|object $data A singe database row object/array or
   *   an array of (multiple) row objects/arrays
   * @param array $options [ 'where'=>[...], 'only'=>[...], 'excl'=>[...] ]
   *   $options['where'] = '{strWhereExpr}'  -OR -
   *   $options['where'] = [ '{strWhereExpr}', '{wExpColName1},{wExpColName2},..' ]
   *     e.g. $opts = [ 'where' => 'id=?', 'excl'=>['id', 'created_at']   ]
   *     e.g. $opts = [ 'where' => 'pk1=? AND pk2=?', 'only' => ['name']  ]
   *     e.g. $opts = [ 'where' => ['pk1=? AND pk2=?', 'pk1,pk2']         ]
   *     e.g. $opts = [ 'only'  => ['name', 'updated_at']                 ]
   * @return boolean success
   */
  public function updateOrInsertInto( $tablesExpr, $data = null, $options = null )
  {
    $sql = '';
    $qMarks = [];
    $setPairs = [];
    $colNames = [];
    $affectedRows = [ 'new' => 0, 'updated' => 0 ];
    if( ! $row ) { return $affectedRows; }
    if( $this->isMultiRow( $data ) )
    {
      $this->log[] = 'Multi-row = TRUE';
      $rows = $data;
    }
    else
    {
      $rows = [ $data ];
    }
    // Extract column info from the first row!
    //  + Build SQL and prepare statements based on info
    $guardedRow = [];
    $firstRow = reset( $rows );
    if( $rowsAreObjects = is_object( $firstRow ) )
    {
      $firstRow = (array) $firstRow;
    }
    $where = isset( $options[ 'where' ] ) ? $options[ 'where' ] : null;
    $only = isset( $options[ 'only' ] ) ? $options[ 'only' ] : null;
    $exclude = isset( $options[ 'excl' ] ) ? $options[ 'excl' ] : null;
    if( $only )
    {
      foreach( $firstRow as $colName => $colVal )
      {
        if( in_array($colName, $only) )
        {
          $guardedRow[$colName] = $colVal;
        }
      }
    }
    elseif( $exclude )
    {
      foreach( $firstRow as $colName => $colVal )
      {
        if( ! in_array($colName, $exclude) )
        {
          $guardedRow[$colName] = $colVal;
        }
      }
    }
    else //( ! $guardedRow )
    {
      $guardedRow = $firstRow;
    }
    if( $where )
    { // UPDATE ONLY
      if( is_array( $where ) )
      { // Use explicitly specified `whereExprColNames` (2nd arg - csv names list)
        // to allow more complex where expressions and avoid regex parsing overhead.
        $whereExpr = $where[0];
        $whereExprColNames = explode( ',', $where[1] );
      }
      else
      { // Auto detect `whereExprColNames`, but REGEX :-/
        $whereExpr = $where;
        $re = '/([^\s!=><]+)[\W]+\?/'; // Simple expressions only!
        preg_match_all($re, $whereExpr, $matches, PREG_PATTERN_ORDER);
        $whereExprColNames = isset( $matches[1] ) ? $matches[1] : [];
      }
      foreach( $guardedRow as $colName => $colValue ) { $updPairs[] = "$colName=?"; }
      $updPairsSql = implode( ',', $updPairs );
      $sql = "UPDATE {$tablesExpr} SET {$updPairsSql} WHERE $whereExpr;";
    }
    else
    {
      foreach( $guardedRow as $colName => $colValue )
      {
        $qMarks[]   = '?';
        $colNames[] = $colName;
        $updPairs[] = "$colName=VALUES($colName)";
      }
      $qMarksSql   = implode( ',', $qMarks   );
      $colNamesSql = implode( ',', $colNames );
      $updPairsSql = implode( ',', $updPairs );
      $sql = "INSERT INTO {$tablesExpr} ({$colNamesSql}) VALUES ({$qMarksSql}) ";
      $sql.= "ON DUPLICATE KEY UPDATE {$updPairsSql};";
    }
    $preparedPdoStatement = $this->prepare( $sql );
    $this->log[] = 'Batch stmt: ' . $sql;
    foreach( $rows as $i => $row )
    {
      $guardedRow = [];
      if( $rowsAreObjects )
      {
        $row = (array) $row;
      }
      if( $only )
      {
        foreach( $row as $colName => $colVal )
        {
          if( in_array( $colName, $only ) )
          {
            $guardedRow[ $colName ] = $colVal;
          }
        }
      }
      if( $exclude )
      {
        foreach( $row as $colName => $colVal )
        {
          if( ! in_array( $colName, $exclude ) )
          {
            $guardedRow[ $colName ] = $colVal;
          }
        }
      }
      if( ! $guardedRow )
      {
        $guardedRow = $row;
      }
      $params = array_values( $guardedRow );
      if( $where )
      { // UPDATE ONLY
        $extraParams = array_map(
          function( $colName ) use ( $row ) { return $row[ $colName ]; },
          $whereExprColNames
        );
        $params = array_merge( $params, $extraParams );
        $preparedPdoStatement->execute( $params );
        $affectedRows[ 'updated' ] += $preparedPdoStatement->rowCount();
      }
      else
      {
        $preparedPdoStatement->execute( $params );
        switch( $preparedPdoStatement->rowCount() )
        {
          case 1: $affectedRows[ 'new' ]++; break;
          case 2: $affectedRows[ 'updated' ]++; break;
        }
      }
    } // end: Update rows loop
    $this->log[] = 'affectedRows: ' . print_r( $affectedRows, true );
    return $affectedRows;
  } // end: updateOrInsertInto

  /**
   * Update multiple database table rows in one go. (UPDATE ONLY)
   *
   * NOTE: Don't forget set the `where` option if your primary key
   * is NOT just 'id'.  See db->updateOrInsertInto()
   *
   * NOTE: When updating multiple rows at a time, you might want to
   * considder wrapping your call in a try/catch with DB transaction control.
   * I.e. db->beginTransaction, db->commit, db->rollBack.
   *
   * @param string $tablesExpr
   * @param array|object $rows
   * @param array $options [ 'where'=>[...], 'only'=>[...], 'excl'=>[...] ]
   *   $options['where'] = '{strWhereExpr}'  -OR -
   *   $options['where'] = [ '{strWhereExpr}', '{wExpColName1},{wExpColName2},..' ]
   * @return boolean success
   */
  public function batchUpdate( $tablesExpr, $rows = null, $options = null )
  {
    if( empty( $options['where'] ) )
    {
      // We use the ARRAY FORMAT to define `where` to save us later
      // having to use REGEX to extract the expression parameter names.
      $options['where'] = [ 'id=?', 'id' ];
    }
    return self::updateOrInsertInto( $tablesExpr, $rows, $options );
  }

  /**
   * Utillity
   * Detect if the $data param represents multiple DB rows.
   * @param array $data
   * @return boolean  yes/no
   */
  public function isMultiRow( $data )
  {
    return is_array( $data ) and is_array( reset( $data ) );
  }

  /**
   * Utility
   * Index or re-index a list using one or multiple
   * item properties as index.
   * e.g. $list = [
   *        'item1' => [ 'id'=>1, 'col'=>'red' , 'name'=>'item1' ],
   *        'item2' => [ 'id'=>2, 'col'=>'blue', 'name'=>'item2' ]
   *      ]
   *      Re-index using $itemKeyNames = 'id,col'
   *      $result = [
   *       '1-red'  => [ 'id'=>1, 'col'=>'red' , 'name'=>'item1' ],
   *       '2-blue' => [ 'id'=>2, 'col'=>'blue', 'name'=>'item2' ]
   *      ]
   * @param array $list
   * @param string $itemKeyNames Comma separated list of item key names to use.
   * @param array $options [ 'logic' => '-' ]
   * @return boolean success
   */
  public function indexList( array $list, $itemKeyNames = null, $options = null )
  {
    if( ! $list ) { return $list; }
    $indexedList = [];
    $duplicatesCount = 0;
    $options = $options ?: [];
    $firstItem = reset( $list );
    $isObjectList = is_object( $firstItem );
    $logic = isset( $options[ 'logic' ] ) ? $options[ 'logic' ] : '-';
    if( $itemKeyNames )
    {
      if( ! is_string( $itemKeyNames ) )
      {
        throw new Exception( 'DB::indexList() - Key names param must be a string.' );
      }
      $itemKeyNames = explode( ',', $itemKeyNames );
    }
    else
    {
      $itemKeyNames = [ 'id' ];
    }
    // Save a few CPU cycles... ;-)
    if( count( $itemKeyNames ) == 1 )
    {
      $itemKeyName = reset( $itemKeyNames );
      foreach( $list as $listItem )
      {
        $index = $isObjectList
          ? $listItem->{ $itemKeyName }
          : $listItem[ $itemKeyName ];
        if( isset( $indexedList[ $index ] ) ) { $duplicatesCount++; }
        $indexedList[ $index ] = $listItem;
      }
    }
    else
    {
      foreach( $list as $listItem )
      {
        $index = '';
        foreach( $itemKeyNames as $itemKeyName )
        {
          $itemKey = $isObjectList
            ? $listItem->{ $itemKeyName }
            : $listItem[ $itemKeyName ];
          $indexPart = $index ? $logic . $itemKey : $itemKey;
          $index .= $indexPart;
        }
        if( isset( $indexedList[ $index ] ) ) { $duplicatesCount++; }
        $indexedList[ $index ] = $listItem;
      }
    }
    return $indexedList;
  } // end: indexList

} // end: Database class


/**
 * 
 * PDOQuery - PDO Query Builder Class
 *
 */
class PDOQuery
{
  public $db;
  public $error;
  public $options;

  protected $indexBy;
  protected $indexByLogic = '-';
  protected $whereExpressions = [];
  protected $havingExpressions = [];
  protected $selectExpr;
  protected $tablesExpr;
  protected $groupByExpr;
  protected $orderByExpr;
  protected $limitExpr;

  /**
   * Create new PDOQuery object.
   * @param object $db PDO Database instance.
   * @param string $tablesExpr SQL FROM expression string in PDO format.
   *   e.g. 'tblusers'  - OR -
   *   e.g. 'tblusers u LEFT JOIN tblroles r ON r.id=u.role_id'
   * @param array $options Store instance specific META data here.
   */
  public function __construct( $db, $tablesExpr = null, $options = null )
  {
    $this->db = $db;
    $this->tablesExpr = $tablesExpr;
    $this->options = $options ?: [];
  }

  /**
   * Explicitly SET the QUERY SELECT expression.
   * @param string $selectExpr  e.g. 'DISTINCT u.name', ...
   * @return object PDOQuery instance
   */
  public function select( $selectExpr = '*' )
  {
    $this->selectExpr = $selectExpr;
    return $this;
  }

  /**
   * Append a WHERE expression using AND or OR (AND by default).
   * @param string $whereExpr WHERE expression string in PDO format.
   * @param array|str $params Param value(s) required to replace PDO placeholders.
   * @param array  $options e.g. ['logic'=>'OR', 'ignore'=>[null,'',0]]
   * @return object PDOQuery instance
   */
  public function where( $whereExpr, $params = null, $options = null )
  {
    $options = $options ?: [];
    $params = $params ? ( is_array( $params ) ? $params : [ $params ] ) : [];
    // Only check "ignore" on single param expressions.
    if( count( $params ) <= 1 and array_key_exists( 'ignore', $options ) )
    {
      $value = $params ? reset( $params ) : null;
      $valuesToIgnore = $options[ 'ignore' ];
      if( $value === $valuesToIgnore or is_array( $valuesToIgnore ) and
        in_array( $value, $valuesToIgnore ) )
      {
        return $this;
      }
    }
    if( $this->whereExpressions and empty( $options[ 'logic' ] ) )
    {
      $options['logic'] = 'AND';
    }
    $this->whereExpressions[] = new PDOWhere( $whereExpr, $params, $options );
    return $this;
  }

  /**
   * Append a WHERE expression using OR.
   * @param string $whereExpr WHERE expression string in PDO format.
   * @param array|string $params Param value(s) required to replace PDO placeholders.
   * @param array  $options e.g. ['test'=>'FROM TO'], ['test'=>'IN'], ...
   * @return object PDOQuery instance
   */
  public function orWhere( $whereExpr, $params = null, $options = null )
  {
    $options = array_merge( $options?:[], [ 'logic' => 'OR' ] );
    return $this->where( $whereExpr, $params, $options );
  }

  public function groupBy( $groupBy )
  {
    $this->groupByExpr = $groupBy ? ' GROUP BY ' . $groupBy : null;
    return $this;
  }

  /**
   * Append a GROUP expression using AND or OR (AND by default).
   * @param string $havingExpr HAVING expression string in PDO format.
   * @param array|str $params Param value(s) required to replace PDO placeholders.
   * @param array  $options e.g. ['logic'=>'OR', 'ignore'=>[null,'',0]]
   * @return object PDOQuery instance
   */
  public function having( $havingExpr, $params = null, $options = null )
  {
    $options = $options ?: [];
    $params = $params ? ( is_array( $params ) ? $params : [ $params ] ) : [];
    if( count( $params ) == 1 and isset( $options[ 'ignore' ] ) )
    {
      $value = reset( $params );
      $valuesToIgnore = $options[ 'ignore' ];
      if( $value === $valuesToIgnore or is_array( $valuesToIgnore ) and
        in_array( $value, $valuesToIgnore ) )
      {
        return $this;
      }
    }
    if( $this->havingExpressions and empty( $options[ 'logic' ] ) )
    {
      $options['logic'] = 'AND';
    }
    $this->havingExpressions[] = new PDOWhere( $havingExpr, $params, $options );
    return $this;
  }

  public function orHaving( $havingExpr, $params = null, $options = null )
  {
    $options = array_merge( $options?:[], [ 'logic' => 'OR' ] );
    return $this->having( $havingExpr, $params, $options );
  }

  public function orderBy( $orderBy )
  {
    $this->orderByExpr = $orderBy ? ' ORDER BY ' . $orderBy : null;
    return $this;
  }

  public function limit( $itemsPerPage, $offset = 0 )
  {
    $this->limitExpr = " LIMIT $offset,$itemsPerPage";
    return $this;
  }

  /**
   * Set the column names to index database table query results by.
   * See: Database::indexList()
   * @param string $columnNamesStr CSV string of column names to index by.
   * @param string $logic String used to join index parts.
   * @return object PDOQuery instance
   */
  public function indexBy( $columnNamesStr, $logic = null )
  {
    $this->indexBy = $columnNamesStr;
    if( isset( $logic ) ) { $this->indexByLogic = $logic; }
    return $this;
  }

  public function buildSelectSql( $selectExpr = null )
  {
    $selectExpr = $selectExpr ?: ( $this->selectExpr ?: '*' );
    return "SELECT $selectExpr FROM {$this->tablesExpr}";
  }

  public function buildWhereSql( &$params )
  {
    $sql = '';
    foreach( $this->whereExpressions as $whereExpr )
    {
      $sql .= $whereExpr->build( $params );
    }
    return $sql;
  }

  public function buildHavingSql( &$params )
  {
    $sql = '';
    foreach( $this->havingExpressions as $havingExpr )
    {
      $sql .= $havingExpr->build( $params );
    }
    return $sql;
  }

  /**
   * Build a PDO SQL conditions string using
   * the current PDOQuery instance configuration.
   * @param array &$params EMPTY array to receive all PDO params used.
   * @return string FULL PDO SQL query conditions string.
   */
  public function buildCondSql( &$params )
  {
    $sql = '';
    if( $this->whereExpressions )
    {
      $sql = ' WHERE ' . $this->buildWhereSql( $params );
    }
    if( $this->groupByExpr ) { $sql .= $this->groupByExpr; }
    if( $this->havingExpressions )
    {
      $sql .= ' HAVING ' . $this->buildHavingSql( $params );
    }
    if( $this->orderByExpr ) { $sql .= $this->orderByExpr; }
    if( $this->limitExpr   ) { $sql .= $this->limitExpr;   }
    return $sql;
  }

  public function buildSql( &$params, $selectExpr = null )
  {
    $selectSql = $this->buildSelectSql( $selectExpr );
    $condSql = $this->buildCondSql( $params );
    $sql = $selectSql.$condSql;
    $this->db->log[] = $sql;
    return $sql;
  }


  public function getAll( $selectExpr = null )
  {
    // NOTE: $params is passed by ref!
    $sql = $this->buildSql( $params, $selectExpr );
    $preparedPdoStatement = $this->db->prepare( $sql );
    if( $preparedPdoStatement->execute( $params ) )
    {
      return $this->indexBy
        ? $this->db->indexList(
            $preparedPdoStatement->fetchAll( PDO::FETCH_OBJ ),
            $this->indexBy, $this->indexByLogic
          )
        : $preparedPdoStatement->fetchAll( PDO::FETCH_OBJ );
    }
    return [];
  }

  public function getFirst( $selectExpr = null )
  {
    $sql = $this->buildSql( $params, $selectExpr );
    $preparedPdoStatement = $this->db->prepare( $sql );
    if( $preparedPdoStatement->execute( $params ) )
    {
      return $preparedPdoStatement->fetch( PDO::FETCH_OBJ );
    }
  }

  public function count()
  {
    $sql = $this->buildSql( $params, 'COUNT(*)' );
    $preparedPdoStatement = $this->db->queryRaw($sql);
    $count = $preparedPdoStatement->fetchColumn();
    return $count;
  }

  /**
   * Update a selection of rows with the same data.
   * @param  array|stdClass $data
   * @return integer Number of updated rows.
   */
  public function update( $data = null )
  {
    $values = [];
    $setPairs = [];
    if( is_object( $data ) ) { $data = (array) $data; }
    foreach( $data as $colName => $value )
    {
      $setPairs[] = "$colName=?";
      $values[] = $value;
    }
    $setPairsSql = implode( ',', $setPairs );
    $sql = "UPDATE {$this->tablesExpr} SET {$setPairsSql}";
    $sql .= $this->buildCondSql( $params );
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare( $sql );
    $preparedPdoStatement->execute( array_merge( $values, $params ) );
    $affectedRows = $preparedPdoStatement->rowCount();
    return $affectedRows;
  }

  public function delete()
  {
    $sql = "DELETE FROM {$this->tablesExpr}";
    $sql .= $this->buildCondSql( $params );
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare( $sql );
    $preparedPdoStatement->execute( $params );
    $affectedRows = $preparedPdoStatement->rowCount();
    return $affectedRows;
  }

} //end: Query Statement Class


/**
 *
 * PDOWhere - PDO Where Expression Builder Class
 *
 */
class PDOWhere
{
  protected $whereExpr;
  protected $params;
  protected $logic = '';

  /*
   * @param string $whereExpr
   *   e.g. 'id=?', 'status=? AND age>=?', 'name LIKE ?', 'fieldname_only'
   * @param mixed  $params
   *   e.g. 100, [100], [1,46], '%john%', ['john']
   * @param array  $options
   *   e.g. ['ignore' => ['', 0, null], logic => 'OR', 'test' => 'IN']
   */
  public function __construct( $whereExpr, $params = null, $options = null )
  {
    $options = $options ?: [];
    if( isset( $options[ 'logic' ] ) )
    {
      $this->logic =  ' ' . $options[ 'logic' ] . ' ';
    }
    if( isset( $options[ 'test' ] ) )
    {
      $test = $options[ 'test' ];
      switch( $test )
      {
        case 'IN':
        case 'NOT IN':
          $qMarks = array_map( function() { return '?'; }, $params );
          $qMarksSql = implode( ',', $qMarks );
          $whereExpr .= " $test ($qMarksSql)";
          break;
        case 'FROM TO':
          $whereExpr = "$whereExpr >= ? AND $whereExpr <= ?";
          break;
      }
    }
    $this->whereExpr = $whereExpr;
    $this->params = $params ?: [];
  }

  public function build( &$params )
  {
    if( ! $params ) { $params = []; }
    $params = array_merge( $params, $this->params );
    if( is_object( $this->whereExpr ) )
    { // I.e $this->whereExpr == instanceof PDOQuery
      $pdoQuery = $this->whereExpr;
      return $this->logic . '(' . $pdoQuery->buildWhereSql( $params ) . ')';
    }
    return $this->logic . $this->whereExpr;
  }
}
