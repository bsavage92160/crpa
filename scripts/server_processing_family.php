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

echo json_encode(FamilyLstSSP::perform( $_GET ));
exit;

class FamilyLstSSP {
	
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
		//$order		= "ORDER BY `famille`.ID ";
		$limit		= self::limit( $request );
		$order		= self::order( $request );
		$where		= self::filter( $request );
		$whereAll	= self::filterAll( $request );
		
		// Data set length after filtering
		$resFilterLength = self::sql_exec( $db,
			"SELECT COUNT( DISTINCT `famille`.ID )
			 FROM `famille`
			 LEFT JOIN `enfant` ON `famille`.ID=`enfant`.ID_FAMILLE
			 $where"
		);
		$recordsFiltered = $resFilterLength[0][0];

		// Total data set length
		$resTotalLength = self::sql_exec( $db,
			"SELECT COUNT( `famille`.ID )
			 FROM `famille`
			 $whereAll"
		);
		$recordsTotal = $resTotalLength[0][0];
		
		// Main query to actually get the data
		$data = self::sql_exec( $db,
			"SELECT `famille`.ID, `famille`.NOM_FAMILLE, `famille`.QF, `famille`.ACTIF,
			        `famille`.NOM1, `famille`.PRENOM1, `famille`.NOM2, `famille`.PRENOM2,
					GROUP_CONCAT(
						DISTINCT CONCAT(UCASE(MID(`enfant`.PRENOM,1,1)), LCASE(MID(`enfant`.PRENOM,2)), ' <small>(', LCASE(`enfant`.CLASSE), ')</small>')
                        ORDER BY `enfant`.PRENOM
						SEPARATOR ', '
					) AS ENFANTS,
					(SELECT COALESCE(SUM(`ecriture`.CREDIT)-SUM(`ecriture`.DEBIT), 0) 
					 FROM `ecriture` WHERE `ecriture`.ID_FAMILLE=`famille`.ID) AS BALANCE
			 FROM `famille`
			 LEFT JOIN `enfant` ON `famille`.ID=`enfant`.ID_FAMILLE
			 WHERE `famille`.ID IN (SELECT * FROM (
					SELECT distinct `famille`.ID
					FROM `famille`
					LEFT JOIN `enfant` ON `famille`.ID=`enfant`.ID_FAMILLE
					$where) tmp_tab) 
			 GROUP BY `famille`.ID 
			 $order
			 $limit
			"
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
			$row = array();
			$row['id'] = $line[0];																			//ID
			$row['na'] = strtoupper($line[1]);																//NOM_FAMILLE
			$row['qf'] = $line[2];																			//QF
			$row['ac'] = $line[3];																			//ACTIF
			$row['f1'] = strtoupper($line[4]) . ' ' . ucfirst(strtolower($line[5]));						//NOM1 + PRENOM 1
			$row['f2'] = strtoupper($line[6]) . ' ' . ucfirst(strtolower($line[7]));						//NOM2 + PRENOM 2
			$row['ch'] = $line[8];																			//ENFANTS
			$row['bl'] = $line[9];																			//BALANCE
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
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @return string SQL order by clause
	 */
	static function order ( $request ) {
		$columns = array("id" => "`famille`.ID", "na" => "`famille`.NOM_FAMILLE", "qf" => "`famille`.QF");

		$order = '';
		
		if ( isset($request['order']) && count($request['order']) ) {
			$orderBy = array();
			
			// Convert the column index into the column data property
			$columnIdx = intval($request['order'][0]['column']);
			$requestColumn = $request['columns'][$columnIdx];
			if( isset($requestColumn['data']) && isset($columns[ $requestColumn['data'] ]) ) {
				$column = $columns[ $requestColumn['data'] ];
				if ( $requestColumn['orderable'] == 'true' ) {
					$dir = $request['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
					$order = 'ORDER BY ' . $column . ' ' . $dir;
				}
			}
		}
		
		return $order;
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
		$columns = array("`famille`.ID",	"`famille`.NOM_FAMILLE",	"`famille`.QF",		"`famille`.ACTIF",
                     	 "`famille`.NOM1",	"`famille`.PRENOM1",		"`famille`.NOM2",	"`famille`.PRENOM2",
						 "`enfant`.NOM",	"`enfant`.PRENOM",			"`enfant`.CLASSE");

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];

			foreach( $columns as $column )
				$globalSearch[] = $column  . " LIKE '%" . $str . "%'";
		}
		
		$withActive = false; $withInactive = false;
		if( isset($request['active']) && is_numeric($request['active']) )		$withActive = (intval($request['active']) == 1);
		if( isset($request['desactive']) && is_numeric($request['desactive']) )	$withInactive = (intval($request['desactive']) == 1);
		
		$where = '';
		
		if( $withActive && !$withInactive )
			$where .= "`famille`.ACTIF=1";
		else if( !$withActive && $withInactive )
			$where .= "`famille`.ACTIF=0";

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
			$where .= "`famille`.ACTIF=1";
		else if( !$withActive && $withInactive )
			$where .= "`famille`.ACTIF=0";

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