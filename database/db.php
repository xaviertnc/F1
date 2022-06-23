<?php namespace OneFile;

use PDO;
use PDOStatement;
use Exception;

/**
 *
 * PDO Database Class
 *
 * @author: C. Moller 08 Jan 2017 <xavier.tnc@gmail.com>
 *
 * @update: C. Moller - 24 Jan 2017
 *   - Moved to OneFile
 *
 * @update: C. Moller - 19 Jan to 7 Mar 2020
 *   - Total Refactor!
 *   - Add db->execRaw()
 *   - Add db->insertInto()
 *   - Add db->updateOrInsertInto()
 *   - Add db->batchUpdate()
 *   - Add db->arrayIsSingleRow()
 *   - Add db->indexList()
 *   - Add db->query()->having()
 *   - Add db->query()->orHaving()
 *   - Add db->query()->update()
 *   - Add db->query()->delete()
 *   - Remove db->query()->addExpression()
 *   - Change QueryStatement to PDOQuery
 *   - Change QueryExpression to PDOWhere
 *   - Simplify classes + Change query builder syntax!
 *   - Re-write build() methods
 *
 * ------
 * Query:
 * ------
 * $db->query('tblusers')
 * $db->query('tblusers u LEFT JOIN tbluserroles r ON r.id = u.role_id')
 *  ->select('u.id, u.desc AS bio, r.desc AS role')
 *  ->select('count(u.id) as TotalUsers')
 *  ->select('*, CONCAT(firstname," ",lastname) as name')
 *
 *  ->where('refno IS NULL')
 *  ->where('pakket_id=?', $pakket_id)
 *  ->where('id>?', $id, ['ignore'=>null])
 *  ->where('tag_id', $arrTagIDs, ['test'=>'IN'])
 *  ->where('tag_id', ['one','two','three'], ['test'=>'NOT IN'])
 *  ->where('tag_id NOT IN (?,?,?)' , ['one','two','three'], ['ignore'=>null])
 *  ->where('tag_id IN (' . implode(',', $arTagIDs) . ')') // Unsafe
 *  ->where(
 *    $db->subQuery()
 *     ->where('date1 BETWEEN (?,?)', [$minDate,$maxDate])         // Exclusive
 *     ->where('date2', [$fromDate,$toDate], ['test'=>'FROM TO'])  // Inclusive
 *     ->where('age', [$minAge,$maxAge], ['test'=>'FROM TO'])
 *     ->orWhere(is_weekend IS NOT NULL)
 *  )
 *
 *  ->where('tagCount<?', $db->subQuery('tblconfig')->getFirst('max_tags'))
 *
 *  ->orWhere('CONCAT(firstname," ",lastname) LIKE ?)', "%$nameTerm%")
 *  ->orWhere('name LIKE ?', "%$nameTerm%", ['ignore'=>[null,'']])
 *  ->orWhere("name LIKE '$nameTerm%'") // Unsafe
 *
 *  ->orderBy('date')  // Defaults to 'asc'
 *  ->orderBy('date desc, time')

 *  ->limit(100)
 *  ->limit(100, 15)
 *  ->limit($itemspp, $offset)
 *
 *  ->indexBy('id')
 *  ->indexBy('type,color')  // Resulting index format = "{type}-{color}"
 *  ->indexBy('type,color', '_')  // Resulting index format = "{type}_{color}"
 *  ->indexBy('type,color', '')  // Resulting index format = "{type}{color}"
 *
 *  ->getAll();
 *  ->getAll('id,desc');
 *  ->getAll('DISTINCT name, desc AS bio');
 *
 *  ->getFirst();
 *  ->getFirst('id,desc');
 *
 *
 * -------
 * Insert:
 * -------
 * $db->insertInto('tbl_users', $objUser1)
 * $db->insertInto('tbl_users', [$objUser1, $objUser2, ...])
 * $db->insertInto('tbl_users', $arrUser1)
 * $db->insertInto('tbl_users', [$arrUser1, $arrUser2, ...])
 *
 *
 * -------
 * Update:
 * -------
 * $db->query('tblusers')
 *   ->where('id=?', 1)
 *   ->update(['name' => 'John']);
 *
 * $db->batchUpdate('tblusers',
 *   [
 *     ['name'=>'john', 'age'=>27],
 *     ['name'=>'jill', 'age'=>29]
 *   ],
 *   [
 *     'where' => 'id=?',
 *     'only'  => 'name,age'
 *   ]
 * );
 *
 * $db->updateOrInsert('tblusers',
 *   [
 *     ['id'=>1, 'name'=>'john', 'age'=>27],
 *     ['id'=>2, 'name'=>'jill', 'age'=>29]
 *   ],
 *   [
 *     'excl'  => 'age'
 *   ]
 * );
 *
 *
 * -------
 * Delete:
 * -------
 * $db->query('tblusers')
 *   ->where('id=?', 1)
 *   ->delete();
 *
 * $db->query('tblusers')
 *   ->where('id', $arrayOfIds, ['test'=>'IN'])
 *   ->delete();
 *
 * $db->query('tblusers')
 *   ->where('age<?', 18)
 *   ->delete();
 *
 */
class Database extends PDO
{
  public $log = array();
  protected $connection = array();

  /**
   *
   * @param array|string $config
   *   Examples:
   *   ---------
   *   $config = [
   *    'DBHOST'=>'...',
   *    'DBNAME'=>'...',
   *    'DBUSER'=>'...',
   *    'DBPASS'=>'...'
   *   ];
   *   - OR -
   *   $config = __DIR__ . '/dbconfig.php;
   */
  public function __construct( $config = null )
  {
    // SAY_hello( __METHOD__ );
    if( is_string( $config ) and file_exists( $config ) ) { include( $config ); }
    elseif( $config and is_array( $config ) ) { $this->config = $config; }
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
    SAY_hello( __METHOD__ );
    return parent::beginTransaction();
  }

  public function commit()
  {
    SAY_hello( __METHOD__ );
    return parent::commit();
  }

  public function rollBack()
  {
    SAY_hello( __METHOD__ );
    return parent::rollBack();
  }

  /**
   * Raw SQL command
   *
   * NOTE: NO query builder, NO PDO placeholders, just SQL. It's your
   * responsibility to escape query params.
   *
   * Examples:
   * ---------
   * $db->execRaw( "INSERT INTO tblUsers (name,age) VALUES ('John','27')" );
   * $db->execRaw( "UPDATE `tblUsers` SET `name`='Johnny' WHERE `id`='1'" );
   * $db->execRaw( "DELETE FROM `tblUsers` WHERE `id`='1'" );
   * $db->execRaw( 'SET GLOBAL log_output = "FILE"' );
   * $db->execRaw( 'SET GLOBAL general_log_file = "nm_mysql.log"' );
   * $db->execRaw( 'SET GLOBAL general_log = "ON"' );
   *
   * @param string $sqlCommandStr
   * @return integer Number of rows affected.
   */
  public function execRaw( $sqlCommandStr )
  {
    // SAY_hello( __METHOD__ );
    $affectedRows = parent::exec( $sqlCommandStr );
    return $affectedRows;
  }

  /**
   * SQL Command with PDO placeholders
   *
   * NOTE: NO query builder, but PDO placeholders are allowed!
   *
   * @param  string  $sqlCommandStr e.g. 'UPDATE tblusers SET name=? WHERE id=?'
   * @param  array   $queryParams Array of placeholder values.
   * @return integer Number of affected rows.
   */
  public function exec( $sqlCommandStr, $queryParams = null )
  {
    // SAY_hello( __METHOD__ );
    $preparedQuery = $this->prepare( $sqlCommandStr );
    $affectedRows = $preparedQuery->execute( $queryParams );
    return $affectedRows;
  }

  /**
   * Syntax sugar. $db->execRaw() Clone.
   * @param string $sqlCommandStr
   * @return integer Number of rows affected.
   */
  public function cmd( $sqlCommandStr )
  {
    // SAY_hello( __METHOD__ );
    return $this->execRaw( $sqlCommandStr );
  }

  /**
   * Raw SQL query
   *
   * NOTE: NO query builder, NO PDO placeholders, just SQL. It's your
   * responsibility to escape query params.
   *
   * Examples:
   * ---------
   * $db->queryRaw( 'SELECT * FROM tblusers' )
   * $db->queryRaw( 'SELECT COUNT(*) AS TotalNumberOfUsers FROM tblusers' )
   * $db->queryRaw( 'SHOW COLUMNS FROM tblusers' )
   *
   * @param string $sqlQueryStr
   * @return object -*UNPREPARED*- PDOStatement object.
   */
  public function queryRaw( $sqlQueryStr )
  {
    // SAY_hello( __METHOD__ );
    return parent::query( $sqlQueryStr );
  }

  /**
   * Incremental (multi-stage) PDO query builder and results fetcher.
   *
   * This method is especially usefull when you have varying query conditions.
   * A common use case would be to generate queries for list-type pages...
   * $db->query() allows you to add various filter, sorting and pagination
   * options dynamically / incrementally while keeping your code clean
   * and readable.
   *
   * @param string $tablesExpr Required e.g 'tblusers', 'tblusers AS u'
   * @param array $options Store instance specific META data here.
   * @return PDOQuery
   */
  public function query( $tablesExpr, $options = null )
  {
    // SAY_hello( __METHOD__ );
    return new PDOQuery( $this, $tablesExpr, $options );
  }

  /**
   * Syntax sugar. The same as $db->query() with optional $tablesExpr.
   * Used to create nested queries.
   * @param string $tablesExpr Optional
   * @param array $options Optional
   * @return PDOQuery
   */
  public function subQuery( $tablesExpr, $options = null )
  {
    // SAY_hello( __METHOD__ );
    return $this->query( $tablesExpr, $options );
  }


  /**
   * Deletes ALL rows from a table.
   *
   * NOTE: Can NOT be rolled back!
   * NOTE: Requires DROP privilage!
   * NOTE: Resets Auto increment to 1
   * @param  string $tableName
   * @return boolean Success / Fail
   */
  public function truncate( $tableName = null )
  {
    // SAY_hello( __METHOD__ );
    return $this->exec( 'TRUNCATE TABLE ?', $tableName );
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
   * @param array|object $row
   * @return boolean success
   */
  public function insertInto( $tablesExpr, $row )
  {
    // SAY_hello( __METHOD__ );
    // SHOW_me( $tablesExpr, 'Batch Insert Into TablesExpr' );
    if( ! $row ) { return [ 'insert' => 0, 'failed' => 0 ]; }
    if( is_array( $row ) and ! $this->arrayIsSingleRow( $row ) )
    {
      $this->log[] = 'Multi-row = TRUE';
      $rows = $row;
    }
    else
    {
      $rows = [ $row ];
    }
    $i = 0;
    $sql = '';
    $qMarks = [];
    $colNames = [];
    $affectedRows = [ 'insert' => 0, 'failed' => 0 ];
    // SHOW_me( $rows, 'Batch Insert Into Rows', 3 );
    foreach( $rows as $r )
    {
      if( is_object($r) ) { $r = (array) $r; }
      if( $i == 0 )
      {
        foreach($r as $colName => $colValue)
        {
          $qMarks[] = '?';
          $colNames[] = $colName;
        }
        $qMarksSql = implode( ',', $qMarks );
        $colNamesSql = implode( ',', $colNames );
        $sql = "INSERT INTO {$tablesExpr} ({$colNamesSql}) VALUES ({$qMarksSql})";
        $preparedPdoStatement = $this->prepare( $sql );
        SHOW_me( $sql, 'Batch Insert Into SQL' );
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
    SHOW_me( $affectedRows, 'Batch Insert Into affectedRows' );
    return $affectedRows;
  } // end: batchInsert

  /**
   * Batch update OR insert multiple database rows in one go.
   *
   * NOTE: For UpdateOrInsert to work, the target database table MUST have
   * a PRIMARY KEY / UNIQUE constaint on at least one or more columns.
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
   * @param array|object $rows
   * @param array $options
   *    e.g. $opts = [ 'where' => 'id=?', 'excl'=>['id', 'created_at']    ]
   *    e.g. $opts = [ 'where' => 'pk1=? AND pk2=?', 'only' => ['name']   ]
   *    e.g. $opts = [ 'where' => ['pk=? AND pk2=?', 'pk1,pk1']           ]
   *    e.g. $opts = [ 'only'  => ['name', 'updated_at']                  ]
   *    See db->update()
   * @return boolean success
   */
  public function updateOrInsertInto( $tablesExpr, $rows = null, $options = null )
  {
    // SAY_hello( __METHOD__ );
    // SHOW_me( $tablesExpr, 'Batch Update or Insert TablesExpr' );
    // SHOW_me( $options, 'Batch Update or Insert Options', 10 );
    $sql = '';
    $qMarks = [];
    $setPairs = [];
    $colNames = [];
    $affectedRows = [ 'new' => 0, 'updated' => 0 ];
    if( ! $rows ) { return $affectedRows; }
    if( is_array( $rows ) and  ! $this->arrayIsSingleRow( $rows ) )
    {
      $this->log[] = 'Multi-row = TRUE';
    }
    else
    {
      $rows = [ $rows ];
    }
    // SHOW_me( $rows, 'Batch Update or Insert Rows', 5 );
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
    if( $exclude )
    {
      foreach( $firstRow as $colName => $colVal )
      {
        if( ! in_array($colName, $exclude) )
        {
          $guardedRow[$colName] = $colVal;
        }
      }
    }
    if( ! $guardedRow )
    {
      $guardedRow = $firstRow;
    }
    if( $where )
    { // UPDATE ONLY
      if( is_array( $where ) )
      { // Use explicitly specified `whereExprColNames`
        // SAY_hello( 'UPDATE ONLY - EXPLICIT WHERE PARAMS' );
        $whereExpr = $where[0];
        $whereExprColNames = explode( ',', $where[1] );
      }
      else
      { // Auto detect `whereExprColNames`, but REGEX :-/
        // SAY_hello( 'UPDATE ONLY - REGEX WHERE PARAMS' );
        $whereExpr = $where;
        $re = '/([^\s!=><]+)[\W]+\?/'; // Simple expressions only!
        preg_match_all($re, $whereExpr, $matches, PREG_PATTERN_ORDER);
        $whereExprColNames = isset( $matches[1] ) ? $matches[1] : [];
      }
      foreach( $guardedRow as $colName => $colValue ) { $updPairs[] = "$colName=?"; }
      $updPairsSql = implode( ',', $updPairs );
      $sql = "UPDATE {$tablesExpr} SET {$updPairsSql} WHERE $whereExpr;";
      // SHOW_me( json_encode( $whereExprColNames ), 'whereExprColNames' );
      // SHOW_me( $whereExpr, 'whereExpr' );
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
    SHOW_me( $sql, 'Update or Insert SQL' );
    // SHOW_me( $qMarks, 'qMarks' );
    // SHOW_me( $colNames, 'colNames' );
    // SHOW_me( $updPairs, 'updPairs' );
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
          if( in_array($colName, $only) )
          {
            $guardedRow[$colName] = $colVal;
          }
        }
      }
      if( $exclude )
      {
        foreach( $row as $colName => $colVal )
        {
          if( ! in_array($colName, $exclude) )
          {
            $guardedRow[$colName] = $colVal;
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
          function( $colName ) use ($row) { return $row[$colName]; },
          $whereExprColNames
        );
        // SHOW_me( $extraParams, 'extraParams' );
        $params = array_merge( $params, $extraParams );
        $preparedPdoStatement->execute( $params );
        $affectedRows[ 'updated' ] += $preparedPdoStatement->rowCount();
      }
      else
      {
        // SHOW_me( $guardedRow, 'guardedRow' );
        // SHOW_me( $params, 'params' );
        $preparedPdoStatement->execute( $params );
        switch( $preparedPdoStatement->rowCount() )
        {
          case 1: $affectedRows[ 'new' ]++; break;
          case 2: $affectedRows[ 'updated' ]++; break;
        }
      }
    } // end: Update rows loop
    $this->log[] = 'affectedRows: ' . print_r( $affectedRows, true );
    SHOW_me( $affectedRows, 'Batch Update or Insert affectedRows' );
    return $affectedRows;
  } // end: updateOrInsert

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
   *   $options['where'] = [ '{strWhereExpr}', '{colName1},{colName2},..' ]
   * @return boolean success
   */
  public function batchUpdate( $tablesExpr, $rows = null, $options = null)
  {
    // SAY_hello( __METHOD__ );
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
   * Detect if an ARRAY represents a
   * single DB row or a collection of rows.
   * @param array $array
   * @return boolean  yes/no
   */
  public function arrayIsSingleRow( array $array )
  {
    // SAY_hello( __METHOD__ );
    return is_scalar( reset( $array ) );
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
   * @param string $itemKeyNames Comma separated list of item key names.
   * @param array $options [ 'ignore' => [ 'keyName1' => ['-', null, ''], .. ] ]
   * @return boolean success
   */
  public function indexList( array $list, $itemKeyNames = null, $options = null )
  {
    // SAY_hello( __METHOD__ );
    if( ! $list ) { return $list; }
    $indexedList = [];
    $invalidCount = 0;
    $duplicatesCount = 0;
    $options = $options ?: [];
    $firstItem = reset( $list );
    $isObjectList = is_object( $firstItem );
    $ignoreOpt = isset( $options[ 'ignore' ] ) ? $options[ 'ignore' ] : [];
    $glue = isset( $options[ 'glue' ] ) ? $options[ 'glue' ] : '-';
    if( $ignoreOpt ) { SHOW_me( json_encode( $ignoreOpt ), 'Ignore option' ); }
    // SHOW_me( $glue, 'indexList: Index parts "glue"' );
    if( $itemKeyNames )
    {
      if( ! is_string( $itemKeyNames ) )
      {
        SAY_hello( 'indexList: ERROR - KEY NAMES PARAM MUST BE A STRING' );
        throw new Exception( 'DB::indexList() - Key names param must be a string.' );
      }
      $itemKeyNames = explode( ',', $itemKeyNames );
    }
    else
    {
      $itemKeyNames = [ 'id' ];
    }
    // SHOW_me( json_encode( $itemKeyNames ), 'itemKeyNames' );
    // Save a few CPU cycles... ;-)
    if( count( $itemKeyNames ) == 1 )
    {
      // SAY_hello( 'indexList: USE SINGLE KEY INDEX ROUTINE' );
      $itemKeyName = reset( $itemKeyNames );
      if( $ignoreOpt )
      {
        $ignoreValues = isset( $ignoreOpt[ $itemKeyName ] ) ? $ignoreOpt[ $itemKeyName ] : null;
        if( $ignoreValues and ! is_array( $ignoreValues ) ) { $ignoreValues = [ $ignoreValues ]; }
      }
      foreach( $list as $listItem )
      {
        $index = $isObjectList
          ? $listItem->{ $itemKeyName }
          : $listItem[ $itemKeyName ];
        if( $ignoreOpt and $ignoreValues and in_array( $index, $ignoreValues ) )
        {
          $invalidCount++;
          continue;
        }
        if( isset( $indexedList[ $index ] ) ) { $duplicatesCount++; }
        $indexedList[ $index ] = $listItem;
      }
    }
    else
    {
      // SAY_hello( 'indexList: USE MULTI-KEY INDEX ROUTINE' );
      foreach( $list as $listItem )
      {
        $index = '';
        foreach( $itemKeyNames as $itemKeyName )
        {
          $itemKey = $isObjectList
            ? $listItem->{ $itemKeyName }
            : $listItem[ $itemKeyName ];
          $indexPart = $index ? $glue . $itemKey : $itemKey;
          if( $ignoreOpt )
          {
            $ignoreValues = isset( $ignoreOpt[ $itemKeyName ] ) ? $ignoreOpt[ $itemKeyName ] : null;
            if( $ignoreValues )
            {
              if( ! is_array( $ignoreValues ) ) { $ignoreValues = [ $ignoreValues ]; }
              if( $ignoreValues and in_array( $indexPart, $ignoreValues ) )
              {
                // SHOW_me( $indexPart, 'Invalid indexPart' );
                // SHOW_me( $itemKeyName, 'Invalid Item Key Name' );
                // SHOW_me( $listItem, 'Invalid List item' );
                $invalidCount++;
                continue 2;
              }
            }
          }
          $index .= $indexPart;
        }
        // SHOW_me( $index, 'index' );
        if( isset( $indexedList[ $index ] ) ) { $duplicatesCount++; }
        $indexedList[ $index ] = $listItem;
      }
    }
    // SHOW_me( json_encode( [ 'IGNORED' => $invalidCount, 'DUPLICATES' => $duplicatesCount ] ), 'listIndex' );
    // SHOW_me( $invalidCount, 'INVALID list items (i.e. items without valid PKs)' );
    // SHOW_me( $duplicatesCount, 'DUPLICATE list items (i.e. items with the same PK)' );
    // SHOW_me( $indexedList, 'indexedList', 5 );
    return $indexedList;
  } // end: indexList

} // end: Database class


/**
 *
 * PDO Query Class
 *
 */
class PDOQuery
{
  public $db;
  public $error;
  public $options;

  protected $indexBy;
  protected $indexByGlue = '-';
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
    // SAY_hello( __METHOD__ );
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
    // SAY_hello( __METHOD__ );
    $this->selectExpr = $selectExpr;
    return $this;
  }

  /**
   * Append a WHERE expression using AND or OR (AND by default).
   * @param string $whereExpr WHERE expression string in PDO format.
   * @param array|str $params Param value(s) required to replace PDO placeholders.
   * @param array  $options e.g. ['glue'=>'OR', 'ignore'=>[null,'',0]]
   * @return object PDOQuery instance
   */
  public function where( $whereExpr, $params = null, $options = null )
  {
    // SAY_hello( __METHOD__ );
    $options = $options ?: [];
    $params = $params ? ( is_array( $params ) ? $params : [ $params ] ) : [];
    // Only check "ignore" on single param expressions.
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
    if( $this->whereExpressions and empty( $options[ 'glue' ] ) )
    {
      $options['glue'] = 'AND';
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
    // SAY_hello( __METHOD__ );
    $options = array_merge( $options?:[], [ 'glue' => 'OR' ] );
    return $this->where( $whereExpr, $params, $options );
  }

  public function groupBy( $groupBy )
  {
    // SAY_hello( __METHOD__ );
    $this->groupByExpr = $groupBy ? ' GROUP BY ' . $groupBy : null;
    return $this;
  }

  /**
   * Append a GROUP expression using AND or OR (AND by default).
   * @param string $havingExpr HAVING expression string in PDO format.
   * @param array|str $params Param value(s) required to replace PDO placeholders.
   * @param array  $options e.g. ['glue'=>'OR', 'ignore'=>[null,'',0]]
   * @return object PDOQuery instance
   */
  public function having( $havingExpr, $params = null, $options = null )
  {
    // SAY_hello( __METHOD__ );
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
    if( $this->havingExpressions and empty( $options[ 'glue' ] ) )
    {
      $options['glue'] = 'AND';
    }
    $this->havingExpressions[] = new PDOWhere( $havingExpr, $params, $options );
    return $this;
  }

  public function orderBy( $orderBy )
  {
    // SAY_hello( __METHOD__ );
    $this->orderByExpr = $orderBy ? ' ORDER BY ' . $orderBy : null;
    return $this;
  }

  public function limit( $itemsPerPage, $offset = 0 )
  {
    // SAY_hello( __METHOD__ );
    $this->limitExpr = " LIMIT $offset,$itemsPerPage";
    return $this;
  }

  /**
   * Set the column names to index database table query results by.
   * See: Database::indexList()
   * @param string $columnNamesStr CSV string of column names to index by.
   * @param string $glue String used to join index parts.
   * @return object PDOQuery instance
   */
  public function indexBy( $columnNamesStr, $glue = null )
  {
    // SAY_hello( __METHOD__ );
    $this->indexBy = $columnNamesStr;
    if( isset( $glue ) ) { $this->indexByGlue = $glue; }
    return $this;
  }

  public function buildSelectSql( $selectExpr = null )
  {
    // SAY_hello( __METHOD__ );
    $selectExpr = $selectExpr ?: ( $this->selectExpr ?: '*' );
    return "SELECT $selectExpr FROM {$this->tablesExpr}";
  }

  public function buildWhereSql( &$params )
  {
    // SAY_hello( __METHOD__ );
    $sql = '';
    foreach( $this->whereExpressions as $whereExpr )
    {
      $sql .= $whereExpr->build( $params );
    }
    return $sql;
  }

  public function buildHavingSql( &$params )
  {
    // SAY_hello( __METHOD__ );
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
    // SAY_hello( __METHOD__ );
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
    // SAY_hello( __METHOD__ );
    $selectSql = $this->buildSelectSql( $selectExpr );
    $condSql = $this->buildCondSql( $params );
    $sql = $selectSql.$condSql;
    $this->db->log[] = $sql;
    return $sql;
  }


  public function getAll( $selectExpr = null )
  {
    // SAY_hello( __METHOD__ );
    // NOTE: $params is passed by ref!
    $sql = $this->buildSql( $params, $selectExpr );
    $preparedPdoStatement = $this->db->prepare( $sql );
    if( $preparedPdoStatement->execute( $params ) )
    {
      // SAY_hello( 'getAll: EXEC QUERY - OK' );
      return $this->indexBy
        ? $this->db->indexList(
            $preparedPdoStatement->fetchAll( PDO::FETCH_OBJ ),
            $this->indexBy, $this->indexByGlue
          )
        : $preparedPdoStatement->fetchAll( PDO::FETCH_OBJ );
    }
    SAY_hello( 'getAll: EXEC QUERY - FAILED' );
    return [];
  }

  public function getFirst( $selectExpr = null )
  {
    // SAY_hello( __METHOD__ );
    $sql = $this->buildSql( $params, $selectExpr );
    $preparedPdoStatement = $this->db->prepare( $sql );
    if( $preparedPdoStatement->execute( $params ) )
    {
      // SAY_hello( 'getFirst: EXEC QUERY - OK' );
      return $preparedPdoStatement->fetch( PDO::FETCH_OBJ );
    }
    SAY_hello( 'getFirst: EXEC QUERY - FAILED' );
  }

  public function count()
  {
    // SAY_hello( __METHOD__ );
    $sql = $this->buildSql( $params, 'COUNT(*)' );
    $preparedPdoStatement = $this->db->queryRaw($sql);
    $count = $preparedPdoStatement->fetchColumn();
    SHOW_me( $count, 'count' );
    return $count;
  }

  /**
   * Update a selection of rows with the same data.
   * @param  array|stdClass $data
   * @return integer Number of updated rows.
   */
  public function update( $data = null )
  {
    SAY_hello( __METHOD__ );
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
    SHOW_me( $sql, 'Update SQL' );
    SHOW_me( json_encode( $params ), 'Update PDO Params' );
    SHOW_me( json_encode( $values ), 'Update Values' );
    $preparedPdoStatement = $this->db->prepare( $sql );
    $preparedPdoStatement->execute( array_merge( $values, $params ) );
    $affectedRows = $preparedPdoStatement->rowCount();
    SHOW_me( $affectedRows, 'Update affectedRows' );
    return $affectedRows;
  }

  public function delete()
  {
    SAY_hello( __METHOD__ );
    $sql = "DELETE FROM {$this->tablesExpr}";
    $sql .= $this->buildCondSql( $params );
    $this->db->log[] = $sql;
    SHOW_me( $sql, 'Delete SQL' );
    SHOW_me( json_encode( $params ), 'Delete PDO Params' );
    $preparedPdoStatement = $this->db->prepare( $sql );
    $preparedPdoStatement->execute( $params );
    $affectedRows = $preparedPdoStatement->rowCount();
    SHOW_me( $affectedRows, 'Delete affectedRows' );
    return $affectedRows;
  }

} //end: Query Statement Class


/**
 *
 * PDOQuery Where Expression Class
 *
 * @author: C. Moller
 * @date: 08 Jan 2017
 *
 * @update: C. Moller - 24 Jan 2017
 *   - Moved to OneFile
 *
 * @update: C. Moller - 19 Jan 2020
 *   - Simplyfy contructor. No more OPERATOR + GLUE params
 *   - Re-write build() method
 *
 */
class PDOWhere
{
  protected $whereExpr;
  protected $params;
  protected $glue = '';

  /*
   * @param string $whereExpr
   *   e.g. 'id=?', 'status=? AND age>=?', 'name LIKE ?', 'fieldname_only'
   * @param mixed  $params
   *   e.g. 100, [100], [1,46], '%john%', ['john']
   * @param array  $options
   *   e.g. ['ignore' => ['', 0, null], glue => 'OR', 'test' => 'IN']
   */
  public function __construct( $whereExpr, $params = null, $options = null )
  {
    // SAY_hello( __METHOD__ );
    $options = $options ?: [];
    if( isset( $options[ 'glue' ] ) )
    {
      $this->glue =  ' ' . $options[ 'glue' ] . ' ';
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
    // SAY_hello( __METHOD__ );
    if( ! $params ) { $params = []; }
    $params = array_merge( $params, $this->params );
    if( is_object( $this->whereExpr ) )
    { // I.e $this->whereExpr == instanceof PDOQuery
      $pdoQuery = $this->whereExpr;
      return $this->glue . '(' . $pdoQuery->buildWhereSql( $params ) . ')';
    }
    return $this->glue . $this->whereExpr;
  }
}
