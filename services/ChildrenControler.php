<?php
require_once('../services/Database.php');
require_once('../services/RegistrationManager.php');
require_once('../services/InvoiceManager.php');
require_once('../services/FinancialFormControler.php');

class ChildrenControler {
	
	/**************************************************************************
	 * Constantes
	 **************************************************************************/
	const CLASS_LIST = array("PS", "MS", "GS", "CP", "CE1", "CE2", "CM1", "CM2");
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	public $levelFilter		= "";		// type: String
	
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	public $msg_error		= "";		// type: String
	public $msg_success		= "";		// type: String
	
	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	public function initialize() {
		
		if( isset($_GET['lv']) )		$this->levelFilter = $_GET['lv'];
		
		// Initialize database connection
		$this->db				= Database::getInstance();
		$this->mysqli			= $this->db->getConnection();
	}
	
	public function build_children_table() {
		$filter = "";
		if( $this->levelFilter != "" )
			$filter = "AND UPPER(`NIVEAU`)='" . $this->levelFilter . "'";
		
		$query = "SELECT `ID`, `NOM`, `PRENOM`, `ID_FAMILLE`, `DATE_NAISS_J`, `DATE_NAISS_M`, `DATE_NAISS_A`, `GENRE`, `NIVEAU`, `CLASSE`, `ACTIF`
				  FROM `enfant`
			      WHERE `ACTIF`=1 
				  $filter
				  ORDER BY `NOM`, `PRENOM`
				 ";
		
		$html  = "";
		$stmt= $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$childId		= intval($res[0]);
				$childName		= strtoupper($res[1]);
				$childFirstName	= ucfirst(strtolower($res[2]));
				$familyId		= intval($res[3]);
				$birthday		= str_pad($res[4], 2, '0', STR_PAD_LEFT) . "/" .
								  str_pad($res[5], 2, '0', STR_PAD_LEFT) . "/" .
								  str_pad($res[6], 2, '0', STR_PAD_LEFT);
				$genre			= strtoupper($res[7]);
				$level			= strtoupper($res[8]);
				$class			= strtoupper($res[9]);
				$actif			= (intval($res[10]) == 1 ? true : false);
				
				$html .= "<tr>
							<td>$childId</td>
							<td>$childName</td>
							<td>$childFirstName</td>
							<td>$genre</td>
							<td><select name=\"lvlCh[$childId]\" value=\"$level\" class=\"lvlInput\">";
				
				foreach ( ChildrenControler::CLASS_LIST as $lvl )
					$html .= "<option value=$lvl>" . strtoupper($lvl) . "</option>";
				
				$html .= "      </select>
							</td>
							<td><input name=\"clsCh[$childId]\" type=\"text\" class=\"clsInput\" value=\"$class\"></td>
							<td>$birthday</td>
						  </tr>\n
						 ";
			}
			$stmt->close();
		}
		return $html;
	}

	public function parse_request() {
		
		// Parse lvlCh parameters
		if(isset($_POST['lvlCh']))
			foreach( $_POST['lvlCh'] as $childId => $lvl )
				$this->_updateLvlChild($childId, $lvl);
		
		// Parse clsCh parameters
		if(isset($_POST['clsCh']))
			foreach( $_POST['clsCh'] as $childId => $cls )
				$this->_updateClsChild($childId, $cls);
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function _updateLvlChild($childId, $lvl) {
		$query = "UPDATE `enfant` SET " .
				 "`NIVEAU`='"			. strtolower(DBUtils::toString($lvl))			. "' " 		.
				 "WHERE `ID`=" 			. $childId;
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function _updateClsChild($childId, $cls) {
		$query = "UPDATE `enfant` SET " .
				 "`CLASSE`='"			. strtolower(DBUtils::toString($cls))			. "' "	.
				 "WHERE `ID`=" 			. $childId;
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
}
?>