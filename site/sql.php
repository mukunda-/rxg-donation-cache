<?php
 
// simple SQL wrapper

require_once "sql_login.php";

$g_sqldb = null;

define( 'SQLERR_DEADLOCK', 1205 );

/** ---------------------------------------------------------------------------
 * Exception thrown from ExQuery
 */
class SQLException extends Exception {
	public $code; // mysqli errno
	
	public function __construct( $errno, $error ) {	
		$code = $errno;
		parent::__construct( $error );
	}
}

/** ---------------------------------------------------------------------------
 * mysqli wrapper class
 */
class MySQLWrapper extends mysqli {

	private $mquery_has_more_results = false;

	/** -----------------------------------------------------------------------
	 * Execute a query and throw an SQLException if it fails.
	 *
	 * @param string $query SQL query to execute.
	 * @return SQL result
	 */
	public function RunQuery( $query ) {
		$result = $this->query( $query );
		if( !$result ) {
			throw new SQLException( 
				$this->errno, "SQL Error: ". $this->error );
		}
		return $result;
	}
	
	/** -----------------------------------------------------------------------
	 * Execute multiple queries and throw an SQLException if any fail.
	 *
	 * @param string $queries SQL queries to execute.
	 * @returns first result set or null if the first statement was
	 *          an update statement.
	 */
	public function MQuery( $queries ) {
		if( !$this->multi_query( $queries ) ) {
			throw new SQLException(
				$this->errno, "SQL Error: ". $this->error );
		}
		
		$result = $this->store_result();
		return $result;
	}
	
	/** -----------------------------------------------------------------------
	 * MQuery, but discard the result sets.
	 */
	public function MQueryUpdate( $queries ) {
		if( !$this->multi_query( $queries ) ) {
			throw new SQLException(
				$this->errno, "SQL Error: ". $this->error );
		} 
		$result = $this->store_result();
		$this->FlushResults();
	}
	
	/** -----------------------------------------------------------------------
	 * Fetch the next result set from an MQuery.
	 *
	 * @returns result set object or null if the statement didn't have a
	 *          result or there are no more results.
	 *
	 * @throws  SQLException if a statement failed.
	 */
	public function NextResult() {
		if( !$this->more_results() ) return null;
		$this->next_result();
		
		if( $result = $this->store_result() ) {
			return $result;
		} else {
		
			// catch error
			if( $this->field_count > 0 ) {
				throw new SQLException(
					$this->errno, "SQL Error: ". $this->error );
			}
			
			// statement was an update.
			return null;
		}
	}
	
	/** -----------------------------------------------------------------------
	 * Check if there are more result sets from the last MQuery
	 *
	 * @returns true if there are more results to be fetched from the last
	 *          multiquery.
	 */
	public function MoreResults() {
		return $this->more_results();
	}
	
	/** -----------------------------------------------------------------------
	 * Flush (discard) all results from an MQuery. 
	 * 
	 * If you don't use the results from an MQuery, this must be used to
	 * discard them before the next query.
	 * 
	 * @throws SQLException if any of the statements failed.
	 */
	public function FlushResults() {
		while( $this->MoreResults() ) {
			$this->NextResult();
		}
	}
	
	/** -----------------------------------------------------------------------
	 * Try executing a function and retrying it if any "normal" errors occur.
	 * 
	 * @param function($sql) $function Function to execute.
	 * @param int      $tries Max number of failures to allow.
	 */
	public function DoTransaction( $function, $tries = 5 ) {
	
		for( ; $tries; $tries-- ) {
			try {
				
				$function( $this );
				break;

			} catch( SQLException $e ) {
				if( $e->code == SQLERR_DEADLOCK ) {
					// try again
					continue;
				}
				
				throw $e;
			}
		}
	}
}

/** ---------------------------------------------------------------------------
 * Connect to the database or return an existing connection.
 *
 * @return MySQLWrapper instance.
 */
function GetSQL() {
	global $g_sqldb;
	if( !$g_sqldb ) {
		$g_sqldb = new MySQLWrapper( $GLOBALS["sql_addr"], $GLOBALS["sql_user"],$GLOBALS["sql_password"],$GLOBALS["sql_database"] );
	
		if( $g_sqldb->connect_errno ) {
			
			$g_sqldb = null;
			throw new SQLException( (int)$g_sqldb->connect_errno, "SQL Connection Error: ". (int)$g_sqldb->connect_error );
		}
		//$g_sqldb->reconnect = 1;

	}
	return $g_sqldb;
}

/** ---------------------------------------------------------------------------
 * Close the current SQL connection.
 *
 * Normally this is handled by the script termination.
 */
function CloseSQL() {
	global $g_sqldb;
	if( $g_sqldb ) {
		$g_sqldb->close();
		$g_sqldb = null;
	}
}

?>