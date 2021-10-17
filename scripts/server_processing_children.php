<?php 
require_once('../services/AccessManager.php');
require_once('../services/Database.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access control
if( !$user->isAnim() && !$user->isAdmin() && !$user->isSuper() ) {
	echo "Acess denied !";
	return;
}

echo json_encode(ChildrenLstSSP::perform( $_GET ));
exit;

class ChildrenLstSSP {
	
	/**
	 * Perform the queries needed for an server-side processing requested,
	 * utilising the helper functions of this class, limit(), order() and
	 * filter() among others. The returned array is ready to be encoded as JSON
	 * in response to an SSP request, or can be modified if needed before
	 * sending back to the client.
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @return array          Server-side processing response array
	 */
	static function perform( $request ) {
	
		// Initialize database connection
		$db				= Database::getInstance();
		
		// Build the SQL query string from the request
		$order		= "ORDER BY `NOM`, `PRENOM`";
		$limit		= self::limit( $request );
		$where		= self::filter( $request );
		$whereAll	= self::filterAll( $request );
		
		// Data set length after filtering
		$resFilterLength = self::sql_exec( $db,
			"SELECT COUNT( DISTINCT `enfant`.`ID` )
			 FROM `enfant`
			 $where"
		);
		$recordsFiltered = $resFilterLength[0][0];

		// Total data set length
		$resTotalLength = self::sql_exec( $db,
			"SELECT COUNT( `enfant`.`ID` )
			 FROM `enfant`
			 $whereAll"
		);
		$recordsTotal = $resTotalLength[0][0];
		
		// Main query to actually get the data
		$data = self::sql_exec( $db,
			"SELECT `ID`, `NOM`, `PRENOM`, `ID_FAMILLE`, `DATE_NAISS_J`, `DATE_NAISS_M`, `DATE_NAISS_A`, `GENRE`, `NIVEAU`, `CLASSE`, `ACTIF`
			 FROM `enfant`
			 $where
			 $order
			 $limit"
		);

		/*
		 * Output
		 */
		return array(
			"draw"            => isset ( $request['draw'] ) ?
				intval( $request['draw'] ) :
				0,
			"recordsTotal"    => intval( $recordsTotal ),
			"recordsFiltered" => intval( $recordsFiltered ),
			"data"            => self::data_output( $data )
		);
	}
	
	/**
	 * Create the data output array for the DataTables rows
	 *
	 *  @param  array $columns Column information array
	 *  @param  array $data    Data from the SQL get
	 *  @return array          Formatted data in a row based format
	 */
	static function data_output ( $data ) {
		$out = array();

		foreach( $data as $line ) {
			$d = mktime(0, 0, 0, intval($line[5]), intval($line[4]), intval($line[6]));
			$row = array();
			$row['id'] = $line[0];										//ID
			$row['na'] = strtoupper($line[1]);							//NOM
			$row['fn'] = ucfirst(strtolower($line[2]));					//PRENOM
			$row['fa'] = $line[3];										//ID_FAMILLE
			$row['gr'] = strtoupper($line[7]);	;						//GENRE
			$row['lv'] = strtoupper($line[8]);	;						//NIVEAU
			$row['cl'] = strtoupper($line[9]);	;						//CLASSE
			$row['dt'] = date('d/m/Y', $d);								//DATE_NAISS_xx
			$row['ag'] = round((time() - $d) / 3600 / 24 / 365.25, 1);	//AGE
			$row['ac'] = $line[10];										//ACTIF
			$out[] = $row;
		}

		return $out;
	}
	
	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @return string SQL limit clause
	 */
	static function limit ( $request ) {
		$limit = '';
		if ( isset($request['start']) && $request['length'] != -1 ) {
			$limit = "LIMIT ".intval($request['start']).", ".intval($request['length']);
		}
		return $limit;
	}
	

	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @param  array $bindings Array of values for PDO bindings, used in the
	 *    sql_exec() function
	 *  @return string SQL where clause
	 */
	static function filter ( $request ) {
		$globalSearch = array();
		$columns = array("NOM", "PRENOM", "NIVEAU", "CLASSE");

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];

			foreach( $columns as $column )
				$globalSearch[] = $column  . " LIKE '%" . $str . "%'";
		}
		
		$withActive = false; $withInactive = false;
		if( isset($request['active']) && is_numeric($request['active']) )		$withActive		= (intval($request['active']) == 1);
		if( isset($request['desactive']) && is_numeric($request['desactive']) )	$withInactive	= (intval($request['desactive']) == 1);
		
		$where = '';
		
		if( $withActive && !$withInactive )
			$where .= "ACTIF=1";
		else if( !$withActive && $withInactive )
			$where .= "ACTIF=0";

		if ( count( $globalSearch ) ) {
			if ( $where !== '' ) $where .= " AND ";
			$where .= "(" . implode(" OR ", $globalSearch) . ")";
		}
			
		if ( $where !== '' )
			$where = "WHERE " . $where;
		
		return $where;
	}

	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @param  array $bindings Array of values for PDO bindings, used in the
	 *    sql_exec() function
	 *  @return string SQL where clause
	 */
	static function filterAll ( $request ) {
		$withActive = false; $withInactive = false;
		if( isset($request['active']) && is_numeric($request['active']) )		$withActive = (intval($request['active']) == 1);
		if( isset($request['desactive']) && is_numeric($request['desactive']) )	$withInactive = (intval($request['desactive']) == 1);
		
		$where = '';
		
		if( $withActive && !$withInactive )
			$where .= "`ACTIF`=1";
		else if( !$withActive && $withInactive )
			$where .= "`ACTIF`=0";

		if ( $where !== '' )
			$where = 'WHERE ' . $where;
		
		return $where;
	}
	
	/**
	 * Execute an SQL query on the database
	 *
	 * @param  resource $db  Database handler
	 * @param  array    $bindings Array of PDO binding values from bind() to be
	 *   used for safely escaping strings. Note that this can be given as the
	 *   SQL query string if no bindings are required.
	 * @param  string   $sql SQL query to execute.
	 * @return array         Result from the query (all rows)
	 */
	static function sql_exec ( $db, $sql ) {
//		echo "sql=$sql\n";
		$mysqli = $db->getConnection();
		$stmt = $mysqli->query($sql);
		if( is_object($stmt) )
			return $stmt->fetch_all(MYSQLI_NUM);
		
		return array();
	}
}