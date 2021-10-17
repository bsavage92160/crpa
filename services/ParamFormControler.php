<?php
require_once('../services/Database.php');
require_once('../services/InvoiceManager.php');
require_once('../services/ParameterManager.php');

class ParamFormControler {
	
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
	
	const LOGGERS		= array("AccessManager", "CalendarControler", "Database", "FamilyControler", "FinancialFormControler",
								"InvoiceControler", "InvoiceManager", "InvoicePeriodControler", "ParameterManager",
								"ParamFormControler", "PaymentControler", "RegistrationManager", "ReleveControler",
								"ReleveFamControler", "ReservationControler", "UserFormControler");
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	private $acPriceArray	= null;		// type: array (code ==> float value)
	private $qfPriceArray	= null;		// type: array (code ==> float value)
	public  $isPrice		= false;	// type: boolean
	
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
	public $isRebate		= false;	// type: boolean
	
	public $year1			= 0;		// type: int
	public $year2			= 0;		// type: int
	public $resa			= 0;		// type: int
	public $payment			= 0;		// type: int
	public $addr			= "";		// type: String
	public $tel				= "";		// type: String
	public $email			= "";		// type: String
	public $isParam			= false;	// type: boolean
	
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
	public $isQF			= false;	// type: boolean
	
	public $lgLoggerArray	= null;		// type: Array
	public $smtpHost		= "";		// type: String
	public $smtpUsername	= "";		// type: String
	public $smtpPassword	= "";		// type: String
	public $smtpSecure		= "";		// type: String
	public $smtpPort		= "";		// type: String
	public $isTech			= false;	// type: boolean
	
	public $organismSlug	= "";		// type: String
	public $clientId		= "";		// type: String
	public $clientSecret	= "";		// type: String
	public $isHelloasso		= false;	// type: boolean
	
	public $submit			= false;	// type: boolean

	public $msg_error		= array();	// type: array
	public $msg_success		= "";		// type: String

	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize() {

		if(isset($_POST['price'])	&& is_numeric($_POST['price']) )	$this->isPrice	= (intval($_POST['price']) == 1);
		if(isset($_POST['rebate'])	&& is_numeric($_POST['rebate']) )	$this->isRebate	= (intval($_POST['rebate']) == 1);
		if(isset($_POST['param'])	&& is_numeric($_POST['param']) )	$this->isParam	= (intval($_POST['param']) == 1);
		if(isset($_POST['qf'])		&& is_numeric($_POST['qf']) )		$this->isQF		= (intval($_POST['qf']) == 1);
		if(isset($_POST['tech'])	&& is_numeric($_POST['tech']) )		$this->isTech	= (intval($_POST['tech']) == 1);
		if(isset($_POST['hellas'])	&& is_numeric($_POST['hellas']) )	$this->isHelloasso	= (intval($_POST['hellas']) == 1);
		if(isset($_POST['submit']))										$this->submit	= true;
		
		if( !$this->isPrice && !$this->isRebate && !$this->isParam && !$this->isQF && !$this->isTech && !$this->isHelloasso )
			$this->isPrice = true;
		
		// Initialize database connection
		$this->db				= Database::getInstance();
		$this->mysqli			= $this->db->getConnection();
	}
	
	
	public function load() {
		foreach( Self::FIELDS_ALL as $f )
			$this->$f = ParameterManager::getInstance()->$f;
	}
	
	
	public function buildPriceTable() {
		$html  = "";
		$html .= "<table style=\"width:100%;\">\n";
		$html .= "<tr><td style=\"width:60%; padding:10px; vertical-align: top;\">\n";
		$html .= "<table class=\"table table-bordered table-striped table-hover table-responsive custom\">\n";
		$html .= "<thead><tr><th>Code</th><th>Intitulé</th><th>Nombre d'Unité</th></thead>\n";
		$html .= "<tbody>\n";
		
		$nb = 0;
		foreach( $this->acPriceArray as $ac => $val ) {
			$lbl = InvoiceManager::CODE_ACT_LBL[$ac];
			$html .= "<tr><td>" . $ac . "</td><td>" . $lbl . "</td><td>";
			$html .= "<input type=\"text\" class=\"form-control myinput\" name=\"ac[" . $ac . "]\" value=\"";
			if( is_numeric($val) )	$html .= number_format($val, 2, '.', ' ');
			else					$html .= $val;
			$html .= "\" ";
			if( $nb++ == 0 ) $html .= "autofocus";
			$html .= ">";
			if( isset($this->msg_error['ac_' . $ac]) ) {
				if( $this->msg_error['ac_' . $ac] <> "" )
					$html .= "<br/><small style=\"margin-top: 0px; color: #dc2a26;\">" . $this->msg_error['ac_' . $ac] . "</small>";
			}
			$html .= "</td></tr>";
		}
		
		$html .= "</tbody></table>\n";
		$html .= "</td>\n";
		$html .= "<td style=\"width:40%; padding:10px; vertical-align: top;\" >\n";
		$html .= "<table class=\"table table-bordered table-striped table-hover table-responsive custom\">\n";
		$html .= "<thead><tr><th>Quotien Familial</th><th>Tarif de l'Unité (€)</th></thead>\n";
		$html .= "<tbody>\n";
		
		foreach( $this->qfPriceArray as $qf => $val ) {
			$html .= "<tr><td>" . $qf . "</td><td>";
			$html .= "<input type=\"text\" class=\"form-control myinput\" name=\"qf[" . $qf . "]\" value=\"";
			if( is_numeric($val) )	$html .= number_format($val, 2, '.', ' ');
			else					$html .= $val;
			$html .= "\" >";
			if( isset($this->msg_error['qf_' . $qf]) ) {
				if( $this->msg_error['qf_' . $qf] <> "" )
					$html .= "<br/><small style=\"margin-top: 0px; color: #dc2a26;\">" . $this->msg_error['qf_' . $qf] . "</small>";
			}
			$html .= "</td></tr>";
		}
		$html .= "</tbody>\n</table>\n";
		$html .= "</td></tr></table>\n";

		echo $html;
	}
	
	public function parse_request() {
		if(!$this->submit) return;
		$update = false;
		
		if($this->isPrice) {
			if( !isset($_POST['ac']) ) return;
			if( !isset($_POST['qf']) ) return;
			
			// Récupération des champs numériques
			foreach( InvoiceManager::CODE_ACT_LBL as $ac => $f ) {
				if(isset($_POST['ac'][$ac])) {
					$f = trim(str_replace(' ', '', $_POST['ac'][$ac]));
					$f = str_replace(',', '.', $f);
					$this->acPriceArray[$ac] = $f;
				}
			}
			foreach( InvoiceManager::QF_LST as $qf ) {
				if(isset($_POST['qf'][$qf])) {
					$f = trim(str_replace(' ', '', $_POST['qf'][$qf]));
					$f = str_replace(',', '.', $f);
					$this->qfPriceArray[$qf] = $f;
				}
			}
			
			// Contrôle de cohérence
			$error = false;
			foreach( $this->acPriceArray as $ac => $f ) {
				if(!is_numeric($f)) {
					$this->msg_error['ac_' . $ac] = "Format non valide !";
					$error = true;
				}
			}
			foreach( $this->qfPriceArray as $qf => $f ) {
				if(!is_numeric($f)) {
					$this->msg_error['qf_' . $qf] = "Format non valide !";
					$error = true;
				}
			}
			if( $error )
				return;
			
			// Conversion des données numériques et Ecriture en base
			foreach( $this->acPriceArray as $ac => $f )
				$this->updatePrice("ac", $ac, floatval($f));
			
			foreach( $this->qfPriceArray as $qf => $f )
				$this->updatePrice("ac", $qf,  floatval($f));
			
			$this->msg_success = "Grille de tarification mise à jour !";
			$update = true;
			
		} else if($this->isRebate) {
			// Récupération des champs numériques
			foreach( Self::FIELDS_REBATE as $f ) {
				if(isset($_POST[$f])) {
					$this->$f = trim(str_replace(' ', '', $_POST[$f]));
					$this->$f = str_replace(',', '.', $this->$f);
				}
			}
			
			// Récupération des champs non numériques
			if(isset($_POST['rebateMode1']))		$this->rebateMode1		= $_POST['rebateMode1'];
			if(isset($_POST['rebateMode2']))		$this->rebateMode2		= $_POST['rebateMode2'];
			
			// Contrôle de cohérence
			$error = false;
			foreach( Self::FIELDS_REBATE as $f ) {
				if(!is_numeric($this->$f)) {
					$this->msg_error[$f] = "Format non valide !";
					$error = true;
				}
			}
			if( $this->rebateMode1 != "H" && $this->rebateMode1 != "P" ) {
					$this->msg_error['rebateMode1'] = "Format non valide !";
					$error = true;
			}
			if( $this->rebateMode2 != "E" && $this->rebateMode2 != "F" ) {
					$this->msg_error['rebateMode2'] = "Format non valide !";
					$error = true;
			}
			if( ($this->threshold1 > $this->threshold2) ||
				($this->threshold2 > $this->threshold3) ||
				($this->threshold3 > $this->threshold4) ||
				($this->threshold4 > $this->threshold5) ) {
					$this->msg_error['threshold5'] = "Veuillez saisir des seuils croissants !";
					$error = true;
			}
			if( $error )
				return;
			
			// Conversion des données numériques
			foreach( Self::FIELDS_REBATE as $f ) $this->$f = floatval($this->$f);
			
			// Ecriture en base
			$this->updateRebate();
			
			$this->msg_success = "Grille de remise mise à jour !";
			$update = true;
		
		} else if($this->isQF) {
			// Récupération des champs numériques
			foreach( Self::FIELDS_QF as $f ) {
				if(isset($_POST[$f])) {
					$this->$f = trim(str_replace(' ', '', $_POST[$f]));
					$this->$f = str_replace(',', '.', $this->$f);
				}
			}
			
			// Contrôle de cohérence
			$error = false;
			foreach( Self::FIELDS_QF as $f ) {
				if(!is_numeric($this->$f)) {
					$this->msg_error[$f] = "Format non valide !";
					$error = true;
				}
			}
			if( $error )
				return;
			
			// Conversion des données numériques
			foreach( Self::FIELDS_QF as $f ) $this->$f = floatval($this->$f);
			
			// Ecriture en base
			$this->updateQF();
			
			$this->msg_success = "Tranches de calcul des QF mises à jour !";
			$update = true;
		
		} else if($this->isParam) {
			// Récupération des champs numériques
			foreach( Self::FIELDS_PARAM as $f ) {
				if(isset($_POST[$f])) {
					$this->$f = trim(str_replace(' ', '', $_POST[$f]));
					$this->$f = str_replace(',', '.', $this->$f);
				}
			}
			
			// Récupération des champs non numériques
			if(isset($_POST['addr']))		$this->addr		= $_POST['addr'];
			if(isset($_POST['tel']))		$this->tel		= $_POST['tel'];
			if(isset($_POST['email']))		$this->email	= $_POST['email'];
						
			// Contrôle de cohérence
			$error = false;
			foreach( Self::FIELDS_PARAM as $f ) {
				if(!is_numeric($this->$f)) {
					$this->msg_error[$f] = "Format non valide !";
					$error = true;
				}
			}
			if( $error )
				return;
			
			// Conversion des données numériques
			foreach( Self::FIELDS_PARAM as $f ) $this->$f = intval($this->$f);
			
			// Ecriture en base
			$this->updateParam();
			
			$this->msg_success = "Paramètres mis à jour !";
			$update = true;
			
		} else if($this->isTech) {
			// Récupération des champs SMTP
			foreach( Self::FIELDS_SMTP as $f ) {
				if(isset($_POST[$f]))
					$this->$f = trim($_POST[$f]);
			}
			
			// Récupération des champs Loggers
			foreach( Self::LOGGERS as $f ) {
				if(isset($_POST[$f]) && is_numeric($_POST[$f]))
					$this->lgLoggerArray[$f] = intval($_POST[$f]);
			}
			
			// Ecriture en base
			$this->updateTech();
			
			$this->msg_success = "Paramètres techniques mis à jour !";
			$update = true;
			
		} else if($this->isHelloasso) {
			// Récupération des champs HelloAsso
			foreach( Self::FIELDS_HELLAS as $f ) {
				if(isset($_POST[$f]))
					$this->$f = trim($_POST[$f]);
			}
			
			// Ecriture en base
			$this->updateHelloAsso();
			
			$this->msg_success = "Paramètres HelloAsso mis à jour !";
			$update = true;
		}
		
		if( $update = true )
			ParameterManager::getInstance()->update();
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function updatePrice($typ, $code, $val){
		if( ($typ != "ac") && ($typ != "qf") ) return;
		if( ($typ == "ac") && !array_key_exists($code, InvoiceManager::CODE_ACT_LBL) ) return;
		if( ($typ == "qf") && !in_array($code, InvoiceManager::QF_LST) ) return;
		$f = floatval(str_replace(",", ".", trim(str_replace(' ', '', $val))));
		if( !is_numeric($f) ) return;
		$query = "UPDATE `param` SET `VAL_NUM`=" . $f . " " .
				 "WHERE `TYPE`='" . $typ . "' AND `PARAM`='" . $code . "'";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateQF() {
		foreach( Self::FIELDS_QF as $f ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_NUM`='" . $this->$f . "' " .
					 "WHERE `TYPE`='ad' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function updateParam() {
		foreach( Self::FIELDS_PARAM as $f ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_NUM`='" . $this->$f . "' " .
					 "WHERE `TYPE`='ad' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
		
		foreach( array("addr", "tel", "email") as $f ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_STR`='" . $this->mysqli->real_escape_string($this->$f) . "' " .
					 "WHERE `TYPE`='ad' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function updateRebate() {
		foreach( Self::FIELDS_REBATE as $f ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_NUM`='" . $this->$f . "' " .
					 "WHERE `TYPE`='rb' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
		
		foreach( array("rebateMode1", "rebateMode2") as $f ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_STR`='" . $this->mysqli->real_escape_string($this->$f) . "' " .
					 "WHERE `TYPE`='rb' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function updateTech() {
		foreach( Self::FIELDS_SMTP as $f ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_STR`='" . $this->mysqli->real_escape_string($this->$f) . "' " .
					 "WHERE `TYPE`='ml' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
		
		foreach( $this->lgLoggerArray as $f => $val ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_NUM`='" . $val . "' " .
					 "WHERE `TYPE`='lg' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function updateHelloAsso() {
		foreach( Self::FIELDS_HELLAS as $f ) {
			$query = "UPDATE `param` " .
					 "SET `VAL_STR`='" . $this->mysqli->real_escape_string($this->$f) . "' " .
					 "WHERE `TYPE`='ha' AND `PARAM`='" . $f . "'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
}
?>