<?php
require_once('../services/Database.php');
require_once('../services/InvoiceManager.php');

class ParameterManager {
	
	const OBJECT_NAME	= "__PARAM__";
	
	const FIELDS_ALL	= array("acPriceArray", "qfPriceArray",
	
								"rebateMode1", "rebateMode2",
								"threshold1", "rebate1", "threshold2", "rebate2", "threshold3", "rebate3",
								"threshold4", "rebate4", "threshold5", "rebate5",
								
								"year1", "year2", "resa", "payment", "addr", "tel", "email",
								
								"minA", "minB", "minC", "minD", "minE", "minF",
								"minG", "minH", "minI", "minJ", "minK", "minL",
								
								"lgLoggerArray", "smtpHost", "smtpUsername", "smtpPassword", "smtpSecure", "smtpPort",
								
								"organismSlug", "clientId", "clientSecret");
	
	const FIELDS_PARAM	= array("year1", "year2", "resa", "payment");
	
	const FIELDS_REBATE	= array("threshold1", "rebate1", "threshold2", "rebate2", "threshold3", "rebate3",
								"threshold4", "rebate4", "threshold5", "rebate5" );
							
	const FIELDS_QF		= array("minA", "minB", "minC", "minD", "minE", "minF",
								"minG", "minH", "minI", "minJ", "minK", "minL");
								
	const FIELDS_SMTP	= array("smtpHost", "smtpUsername", "smtpPassword", "smtpSecure", "smtpPort");
	
	const FIELDS_HELLAS	= array("organismSlug", "clientId", "clientSecret");
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private static $_instance;			//The single instance

	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	public $acPriceArray	= null;		// type: array (code ==> float value)
	public $qfPriceArray	= null;		// type: array (code ==> float value)

	public $rebateMode1		= "";		// type: String
	public $rebateMode2		= "";		// type: String
	public $threshold1		= 0.0;		// type: float
	public $rebate1			= 0.0;		// type: float
	public $threshold2		= 0.0;		// type: float
	public $rebate2			= 0.0;		// type: float
	public $threshold3		= 0.0;		// type: float
	public $rebate3			= 0.0;		// type: float
	public $threshold4		= 0.0;		// type: float
	public $rebate4			= 0.0;		// type: float
	public $threshold5		= 0.0;		// type: float
	public $rebate5			= 0.0;		// type: float
	
	public $year1			= 0;		// type: int
	public $year2			= 0;		// type: int
	public $resa			= 0;		// type: int
	public $payment			= 0;		// type: int
	public $addr			= "";		// type: String
	public $tel				= "";		// type: String
	public $email			= "";		// type: String
	
	public $minA			= 0.0;		// type: float
	public $minB			= 0.0;		// type: float
	public $minC			= 0.0;		// type: float
	public $minD			= 0.0;		// type: float
	public $minE			= 0.0;		// type: float
	public $minF			= 0.0;		// type: float
	public $minG			= 0.0;		// type: float
	public $minH			= 0.0;		// type: float
	public $minI			= 0.0;		// type: float
	public $minJ			= 0.0;		// type: float
	public $minK			= 0.0;		// type: float
	public $minL			= 0.0;		// type: float

	public $lgLoggerArray	= null;		// type: Array
	public $smtpHost		= "";		// type: String
	public $smtpUsername	= "";		// type: String
	public $smtpPassword	= "";		// type: String
	public $smtpSecure		= "";		// type: String
	public $smtpPort		= "";		// type: String
	
	public $organismSlug	= "";		// type: String
	public $clientId		= "";		// type: String
	public $clientSecret	= "";		// type: String
	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	public static function getInstance() {
		if(!Self::$_instance) {							// If no instance then check if existing in Session
			
			if( isset($_SESSION [Self::OBJECT_NAME]) ) {
				Self::$_instance = new self();
				foreach( Self::FIELDS_ALL as $f ) {
					if( isset($_SESSION [Self::OBJECT_NAME . $f]) )
						Self::$_instance->$f = $_SESSION [Self::OBJECT_NAME . $f];
				}
			
			} else {									// Create new one
				Self::$_instance = new self();
				Self::$_instance->update();
			}
		}
		return Self::$_instance;
	}
	
	public function update() {
		
		// Initialize database connection
		$this->db			= Database::getInstance();
		$this->mysqli		= self::$_instance->db->getConnection();
		
		// Load data
		$this->loadPrices();
		$this->loadParams();
		
		// Sereliaze the single instance in SESSION
		$_SESSION [Self::OBJECT_NAME]	= 1;
		foreach( Self::FIELDS_ALL as $f )
			$_SESSION [Self::OBJECT_NAME . $f] = $this->$f;
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadPrices() {
		$this->acPriceArray = array();
		foreach( InvoiceManager::CODE_ACT_LBL as $codeAct => $lbl )
			$this->acPriceArray[$codeAct] = $this->getUnitPrice("ac", $codeAct);
		
		$this->qfPriceArray = array();
		foreach( InvoiceManager::QF_LST as $qf )
			$this->qfPriceArray[$qf] = $this->getUnitPrice("qf", $qf);
	}
	
	private function getUnitPrice($typ, $code) {
		$ret = 0.0;
		$query = "SELECT `VAL_NUM` FROM `param` ".
				 "WHERE `TYPE`='" . $typ . "' AND `PARAM`='" . $code . "' " .
				 "LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = floatval($res[0]);
			$stmt->close();
		}
		return $ret;
	}
	
	private function loadParams() {
		
		//----------------- Champs onglet 'Paramètres'  -----------------------
		foreach( Self::FIELDS_PARAM as $f ) {
			$query = "SELECT `VAL_NUM` " .
					 "FROM `param` " .
					 "WHERE `TYPE`='ad' AND `PARAM`='" . $f . "' LIMIT 1";
			$stmt= $this->mysqli->query($query);
			if (is_object($stmt)) {
				if($res = $stmt->fetch_array(MYSQLI_NUM))
					$this->$f = intval($res[0]);
				$stmt->close();
			}
		}
		
		foreach( array("addr", "tel", "email") as $f ) {
			$query = "SELECT `VAL_STR` " .
					 "FROM `param` " .
					 "WHERE `TYPE`='ad' AND `PARAM`='" . $f . "' LIMIT 1";
			$stmt= $this->mysqli->query($query);
			if (is_object($stmt)) {
				if($res = $stmt->fetch_array(MYSQLI_NUM))
					$this->$f = $res[0];
				$stmt->close();
			}
		}
		
		//----------------- Champs onglet 'Grille QF '  -----------------------
		foreach( Self::FIELDS_QF as $f ) {
			$query = "SELECT `VAL_NUM` " .
					 "FROM `param` " .
					 "WHERE `TYPE`='ad' AND `PARAM`='" . $f . "' LIMIT 1";
			$stmt= $this->mysqli->query($query);
			if (is_object($stmt)) {
				if($res = $stmt->fetch_array(MYSQLI_NUM))
					$this->$f = floatval($res[0]);
				$stmt->close();
			}
		}
		
		//----------------- Champs onglet 'Remise'  -----------------------
		foreach( Self::FIELDS_REBATE as $f ) {
			$query = "SELECT `VAL_NUM` " .
					 "FROM `param` " .
					 "WHERE `TYPE`='rb' AND `PARAM`='" . $f . "' LIMIT 1";
			$stmt= $this->mysqli->query($query);
			if (is_object($stmt)) {
				if($res = $stmt->fetch_array(MYSQLI_NUM))
					$this->$f = intval($res[0]);
				$stmt->close();
			}
		}
		
		foreach( array("rebateMode1", "rebateMode2") as $f ) {
			$query = "SELECT `VAL_STR` " .
					 "FROM `param` " .
					 "WHERE `TYPE`='rb' AND `PARAM`='" . $f . "' LIMIT 1";
			$stmt= $this->mysqli->query($query);
			if (is_object($stmt)) {
				if($res = $stmt->fetch_array(MYSQLI_NUM))
					$this->$f = $res[0];
				$stmt->close();
			}
		}
		
		//----------------- Champs onglet 'Technique'  -----------------------
		foreach( Self::FIELDS_SMTP as $f ) {
			$query = "SELECT `VAL_STR` " .
					 "FROM `param` " .
					 "WHERE `TYPE`='ml' AND `PARAM`='" . $f . "' LIMIT 1";
			$stmt= $this->mysqli->query($query);
			if (is_object($stmt)) {
				if($res = $stmt->fetch_array(MYSQLI_NUM))
					$this->$f = $res[0];
				$stmt->close();
			}
		}
		
		$this->lgLoggerArray = array();
		$query = "SELECT `PARAM`, `VAL_NUM` " .
				 "FROM `param` " .
				 "WHERE `TYPE`='lg'";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM))
				$this->lgLoggerArray[$res[0]] = intval($res[1]);
			$stmt->close();
		}
		
		
		//----------------- Champs onglet 'HelloAsso'  -----------------------
		foreach( Self::FIELDS_HELLAS as $f ) {
			$query = "SELECT `VAL_STR` " .
					 "FROM `param` " .
					 "WHERE `TYPE`='ha' AND `PARAM`='" . $f . "' LIMIT 1";
			$stmt= $this->mysqli->query($query);
			if (is_object($stmt)) {
				if($res = $stmt->fetch_array(MYSQLI_NUM))
					$this->$f = $res[0];
				$stmt->close();
			}
		}
	}
}
?>