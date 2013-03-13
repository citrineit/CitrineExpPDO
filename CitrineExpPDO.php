<?php
/**
* Citrine Magic PDO Database Class
*
* @category Database Access
* @package PDO
* @author Citrine.it
* @copyright Copyright (c) 2013
* @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
* @version 1
**/
define('DELETE', 'Goodnight, Sweet Prince');

class CitrineExpPDO {

  /**
	 * Statement being prepared
	 *
	 * @var string
	 */
	public $prepared_statement;

	/**
	 * Last query
	 *
	 * @var string
	 */
	public $last_query;

	/**
	 * Number of results
	 *
	 * @var int|string
	 */
	public $num_rows;
	
	/**
	 * Most recent error message
	 *
	 * @var string
	 */
	protected $error_message;
	
	/**
	 * PDO Instance
	 *
	 * @var object
	 */
	protected $_pdo;
	
	/**
	 * Prepared Statement
	 *
	 * @var string
	 */
	protected $prepared;
	
	/**
	 * Query values
	 *
	 * @var array
	 */
	private $_where_values;

	/**
	 * Create PDO connection
	 *
	 * @param string $server	The database server you will use
	 * @param string $username	The username you will use to log into database
	 * @param string $password	The password to your database login
	 * @param string $database	The database database you are using
	 * @param string $driver	The database driver to use, default 'mysql'
	 * @param string $override	Override the DSN statement to construct PDO
	 *
	 */
	public function CitrinePDO ( $server, $username, $password, $database, $dsn = 'mysql', $override = false ) {
	
		if($override)
			$_dsn = $override;
		else
			$_dsn = $dsn . ':host=' . $server . ';dbname=' . $database;
	
		try {
		
			$this->_pdo = new PDO($_dsn, $username, $password);
			$this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			
		} catch ( PDOException $e ) {
		
			echo 'Failed to connect: (' . $e->getCode() . ') ' . $e->getMessage();
			
		}
		
		$this->_log_debug = false;
		
	}
	
	
	/**
	 * Select Query
	 *
	 * @param string $table		Name of table to get
	 * @param string $select	A string of columns to get
	 * @param array  $where		An array, column => value for WHERE, use % in val for LIKE
	 * @param string $orderby	A string of what to order by, 'my_col ASC'
	 * @param string $limit		A string of limit and offset, '1' or '1,15'
	 *
	 */
	public function select ( $table, $select = '*', $where = false, $orderby = false, $limit = false ) {
	
		// Clean up select
		if ( $select != '*' )
			$select = ' ' . implode(', ', explode( ",",$select ) ) . ' ';
	
		$this->prepared_statement = 'SELECT ' . $select . ' FROM ' . $table . ' ';
		
		// Where?
		if ( $where )
			$this->prepared_statement .= $this->_formatWhere($where);

		if ( $orderby ) 
			$this->prepared_statement .= ' ORDER BY ' . $orderby;

		if ( $limit ) 
			$this->prepared_statement .= ' LIMIT ' . $limit;
		

		return $this->_commit();
		
	}
	
	/**
	 * Select Single Query
	 *
	 * Wrapper for $this->select();
	 *
	 * @param string $table		Name of table to get
	 * @param string $select	A string of columns to get
	 * @param array  $where		An array, column => value for WHERE, use % in val for LIKE
	 * @param string $orderby	A string of what to order by, 'my_col ASC'
	 *
	 */
	public function single ( $table, $select = '*', $where = false, $orderby = false ) {
	
		$this->select($tables, $select, $where, $orderby, 1);
		
	}
	
	
	/**
	 * Insert query
	 *
	 * @param string $table
	 * @param array $insertion 	array('column' => 'value')
	 *
	 */
	public function insert ( $table, $insertion ) {
	
		$columns = array_keys($insertion);
		
		for ( $i = 0; $i < count($insertion); $i++)
			$values[] = '?';
		
		$this->prepared_statement = "INSERT INTO $table  ('" . implode(", ", $columns) . "') VALUES ('" . implode("', '", $values) . "')";
		
		$this->_where_values = array_values($insertion);

		return $this->_commit();
	
	}
	
	
	/**
	 * Update query
	 *
	 * @param string $table table name 
	 * @param array $data 	col => value pairs
	 * @param array $where 	col => value pairs
	 */
	public function update ( $table, $data, $where ) {
	
		$this->prepared_statement = 'UPDATE ' . $table . ' SET ';
		$values_sequence = array();
		
		foreach ( $data as $col => $val ) {
			$this->prepared_statement .= ' ' . $col . ' = ?, ';
			$values_sequence[] = $val;
		}

		$this->prepared_statement = substr($this->prepared_statement,0,-2);
		$this->prepared_statement .= $this->_formatWhere($where);
		
		$this->_where_values = array_merge($values_sequence, $this->_where_values);
		
		return $this->_commit();
		
	}
	
	
	/**
	 * Delete query
	 *
	 * @param string $table	Name of the table
	 * @param array $where	Array column => value
	 *
	 */
	public function delete( $table, $where ) {
		
		$this->prepared_statement = 'DELETE FROM ' . $table . ' ';
		$this->prepared_statement .= $this->_formatWhere($where);
		
		return $this->_commit();
		
	}
	
	/**
	 * Custom query
	 *
	 * @param string $table	Name of the table
	 * @param array $where	Array column => value
	 *
	 */
	public function custom( $sql ) {
		
		$this->prepared_statement = $sql;
		
		return $this->_commit();
		
	}
	
	/**
	 * Prepared Custom
	 *
	 * @param string $table	Name of the table
	 * @param array $where	Array column => value
	 *
	 */
	public function prep( $sql ) {
		
		$this->prepared_statement = $sql;
		
		return $this->_pdo->prepare( $this->prepared_statement );	
		
	}
	
	
	/**
	 * Format WHERE clause
	 *
	 * @param array $where 
	 * @return string formatted WHERE
	 *
	 */
	private function _formatWhere ( $where ) {

		$where_string = ' WHERE ';
		$boolean_connectors = array('');
		$where_clauses = array();
		$not_first = false;
	
		foreach ( $where as $column => $value ) {
			
			if ( $not_first ) {
			
				if ( strpos( $column, 'OR ' ) === 0 ) {
					$column = str_replace('OR ', '', $column);
					$boolean_connectors[] = 'OR';
				} else {
					$boolean_connectors[] = 'AND';
				}
	
			} else {
				$not_first = true;
			}
			
			if ( strpos( $value, '%' ) !== false ) {
				$where_clauses[] = ' ' . $column . ' LIKE ? ';
			} else if ( strpos( $value, '< ' ) !== false ) {
				$value = str_replace('< ', '', $value);
				$where_clauses[] = ' ' . $column . ' < ? ';
			} else if ( strpos( $value, '> ' ) !== false ) {
				$value = str_replace('> ', '', $value);
				$where_clauses[] = ' ' . $column . ' > ? ';
			} else if ( strpos( $value, '!=' ) !== false ) {
				$value = str_replace('!=', '', $value);
				$where_clauses[] = ' ' . $column . ' != ? ';
			} else if ( strpos( $value, '<=' ) !== false ) {
				$value = str_replace('<=', '', $value);
				$where_clauses[] = ' ' . $column . ' <= ? ';
			} else if ( strpos( $value, '>=' ) !== false ) {
				$value = str_replace('>=', '', $value);
				$where_clauses[] = ' ' . $column . ' >= ? ';
			} else {
				$where_clauses[] = ' ' . $column . ' = ? ';
			}
			
			$this->_where_values[] = $value;
			
		}
		
		for ( $i = 0; $i < count($where_clauses); $i++ )
			$where_string .= $boolean_connectors[$i] . ' ' . $where_clauses[$i] . ' ';
			
		return $where_string;
	
	}
	 
	 
	public function __call ( $name , $arguments ) {
		
		$table = $name;
		
		// Just grab everything and be done
		if ( empty( $arguments ) )
			return $this->select( $table );
		
		// Only one submitted, either select or insert
		if ( count($arguments) == 1 ) {

			if ($arguments[0] != 'Goodnight, Sweet Prince')
				return $this->select( $table, $arguments[0] );
			else if ( is_array($arguments[0]) )
				return $this->insert( $table, $arguments[0] );
		
		}

		if ( count($arguments) == 2 ) {
		
			if ( $arguments[0] == 'Goodnight, Sweet Prince' )
				return $this->delete( $table, $arguments[1] );
			else if ( is_string($arguments[0]) )
				return $this->select( $table, $arguments[0], $arguments[1] );
			else if ( is_array($arguments[0]))
				return $this->update( $table, $arguments[0], $arguments[1] );
		
		}
		
		if ( count($arguments) == 3 )
			return $this->select( $table, $arguments[0], $arguments[1], $arguments[2] );
		
		if ( count($arguments) == 4 )
			return $this->select( $table, $arguments[0], $arguments[1], $arguments[2], $arguments[3] );
			
		return false;
		
	}
	
	
	/**
	 * Begin Query
	 *
	 * Note that this will reuse the PDOStatement object if
	 * the same query is being built againt.
	 *
	 * @return bool|int|array|object result of the query
	 */
	private function _commit () {
	
		if ($this->prepared != $this->prepared_statement)
			$prepared = $this->_pdo->prepare( $this->prepared_statement );
		else
			$prepared = $this->prepared;
		
		if ( $prepared && $prepared->execute( $this->_where_values ) ) {
	
			$this->last_query = $prepared->queryString;

			// If INSERT
			if ( strpos( $this->last_query, 'INSERT') === 0  )
				return $this->_pdo->lastInsertId();
				
			
			// If DELETE or UPDATE
			if ( strpos( $this->last_query, 'DELETE') === 0 
			&& $prepared->rowCount() )
				return $prepared->rowCount();
			else if ( strpos( $this->last_query, 'UPDATE') === 0 
			&& $prepared->rowCount() )
				return $prepared->rowCount();
			else if ( strpos( $this->last_query, 'UPDATE') === 0 
			&& strpos( $this->last_query, 'DELETE') == 0 )
				return true;
			
			// If SELECT
			if ( strpos( $this->last_query, 'SELECT') === 0 
			&& $prepared->rowCount() > 1 )
				return $prepared->fetchAll( PDO::FETCH_OBJ );
			else if ( $prepared->rowCount() < 2 )
				return $prepared->fetch( PDO::FETCH_OBJ );
				
			
			return $prepared;
		
		} else {

			$this->error_message = $this->_pdo->errorInfo();
			return false;
			
		}
	
	}
	
	/**
	 * Print debug message
	 */
	public function debug () {
		
		print_r($this->error_message);
		
	}
	
	/**
	 * Log debug message
	 */
	public function debug_log () {
	
		$message = 'Error at ' . date(DATE_ISO8601) . ': ';
		$message .= $this->last_query . PHP_EOL . 'Error Message: ' . $this->error_message;
		$message .= PHP_EOL . '_____________________________________________' . PHP_EOL;

		$database_errors = fopen('database_errors.log', 'a+');
		fwrite($database_errors, $message);
		fclose($database_errors);
		
	}
	
}
