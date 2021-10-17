<?php
require_once('../services/Database.php');

class CalendarControler {
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	public $currentNoJour	= 0;		// type: int (8 digits - Ymd)
	public $currentTimestamp= 0;		// type: int (Timestamp UNIX of Date)
	public $currentDay		= 0;		// type: int (between 1 and 7)
	public $currentSem		= 0;		// type: int (between 1 and 53)
	public $currentMonth	= 0;		// type: int (between 1 and 12)
	public $currentYear		= 0;		// type: int (4 digits - Y)
	
	public $firstNoJour		= 0;		// type: int (8 digits - Ymd)
	public $firstTimestamp	= 0;		// type: int (Timestamp UNIX of Date)
	public $firstJJ			= 0;		// type: int (between 1 and 31)
	public $firstMM			= 0;		// type: int (between 1 and 12)
	public $firstYear		= 0;		// type: int (4 digit)
	
	public $lastNoJour		= 0;		// type: int (8 digits - Ymd)
	public $lastTimestamp	= 0;		// type: int (Timestamp UNIX of Date)
	public $lastJJ			= 0;		// type: int (between 1 and 31)
	public $lastMM			= 0;		// type: int (between 1 and 12)
	public $lastYear		= 0;		// type: int (4 digit)
	
	public $firstDayOfMonth	= 0;		// type: int (Timestamp UNIX of Date)
	public $fdomNoJour		= 0;		// type: int (8 digits - Ymd)
	public $lastDayOfMonth	= 0;		// type: int (Timestamp UNIX of Date)
	public $ldomNoJour		= 0;		// type: int (8 digits - Ymd)
	
	private $months			= array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
	
	public $success_msg		= "";		// type: String

	
	/**************************************************************************
	 * Constructeur
	 **************************************************************************/	
    function __construct() {
		date_default_timezone_set('UTC');
		
		// Initialize database connection
		$this->db = Database::getInstance();
		$this->mysqli = $this->db->getConnection();
    }
	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize() {
		
		// Parse request
		if(isset($_GET['wd']) && is_numeric($_GET['wd'])) {
			$this->currentNoJour	= intval($_GET['wd']);
			$d = DateTime::createFromFormat('Ymd', $this->currentNoJour);
			if($d != false) $this->currentTimestamp	= intval($d->getTimestamp());
		}
		if(isset($_POST['wd']) && is_numeric($_POST['wd'])) {
			$this->currentNoJour	= intval($_POST['wd']);
			$d = DateTime::createFromFormat('Ymd', $this->currentNoJour);
			if($d != false) $this->currentTimestamp	= intval($d->getTimestamp());
		}
		if ($this->currentTimestamp == 0) {
			$this->currentTimestamp = intval(time());
			$this->currentNoJour	= intval(date('Ymd', $this->currentTimestamp));
		}
		$this->currentSem		= intval(date('W', $this->currentTimestamp));
		$this->currentMonth		= intval(date('m', $this->currentTimestamp));
		$this->currentYear		= intval(date('Y', $this->currentTimestamp));
		$this->currentDay		= intval(date('N', $this->currentTimestamp));
		
		// Initialize the fisrt and last day of the month
		$this->firstDayOfMonth	= mktime(0, 0, 0, $this->currentMonth, 1, $this->currentYear);
		$this->fdomNoJour		= intval(date('Ymd', $this->firstDayOfMonth));
		$this->lastDayOfMonth	= mktime(0, 0, 0, $this->currentMonth + 1, 1, $this->currentYear) - 3600 * 24;
		$this->ldomNoJour		= intval(date('Ymd', $this->lastDayOfMonth));
		
		// Initialize the fisrt day of the calendar
		$firstDay 				= intval(date('N', $this->firstDayOfMonth));
		if ($firstDay <= 5) {
			$this->firstTimestamp = $this->firstDayOfMonth - 3600 * 24 * (intval(date('N', $this->firstDayOfMonth)) - 1);
		} else {
			$this->firstTimestamp = $this->firstDayOfMonth + 3600 * 24 * (8 - intval(date('N', $this->firstDayOfMonth)));
		}
		$this->firstJJ			= intval(date('d', $this->firstTimestamp));
		$this->firstMM			= intval(date('m', $this->firstTimestamp));
		$this->firstYear		= intval(date('Y', $this->firstTimestamp));
		$this->firstNoJour		= intval(date('Ymd', $this->firstTimestamp));
		
		// Initialize the last day of the calendar
		$this->lastTimestamp	= $this->lastDayOfMonth + 3600 * 24 * (7 - intval(date('N', $this->lastDayOfMonth)));;
		$this->lastJJ			= intval(date('d', $this->lastTimestamp));
		$this->lastMM			= intval(date('m', $this->lastTimestamp));
		$this->lastYear			= intval(date('Y', $this->lastTimestamp));
		$this->lastNoJour		= intval(date('Ymd', $this->lastTimestamp));
	}
	
	
	public function build_calendar() {
		$html = "";
		
		$d = intval(date('Ymd', mktime(0, 0, 0, $this->currentMonth - 1, $this->currentDay, $this->currentYear)));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $d . "'\">&lt;</button>\n";
		
		$d = intval(date('Ymd', mktime(0, 0, 0, $this->currentMonth + 1, $this->currentDay, $this->currentYear)));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $d . "'\">&gt;</button>\n";
		
		$today = intval(date('Ymd', time()));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $today . "'\">Aujourd'hui</button>\n";
		$html .= "&nbsp;&nbsp;<label>" . $this->months[$this->currentMonth - 1] . " " . intval(date('Y', $this->currentTimestamp)) . "</label>\n";
		
		$html .= "<table class=\"calendar_table\">\n";
		$html .= "<thead><tr><th></th><th>LUNDI</th><th>MARDI</th><th>MERCREDI</th><th>JEUDI</th><th>VENDREDI</th></tr></thead>\n";
		$html .= "<tbody>";
		
		for ($wDay = $this->firstTimestamp; $wDay < $this->lastTimestamp ; $wDay = $wDay + 3600 * 24) {
			$noJour  = intval(date('Ymd', $wDay));
			$noSem	 = intval(date('W', $wDay));
			$jourSem = intval(date('N', $wDay));
			$jj		 = date('d', $wDay);
			$mm		 = date('m', $wDay);
			$wkDay	 = $this->loadDay($noJour, $jourSem);
			$actif	 = $wkDay->ouv;
			$empty	 = ($wDay < $this->firstDayOfMonth) || ($wDay > $this->lastDayOfMonth);
			
			if ($jourSem == 1) $html .= "<tr><td class=\"case_num_sem\">" . $noSem . "</td>\n";
			if ($jourSem !=6 && $jourSem !=7) {
				$html .= "<td";
				$html .= " class=\"";
				if ( $empty )			$html .= "case_empty ";
				elseif (!$actif)	$html .= "case_inactive ";
				if ($noJour == $today) $html .= "today ";
				$html .= "\">";
				
				$html .= "<div class=\"case_jour\"><div>" . $jj . "/" . $mm . "</div>";
				if ( !$empty )
					$html .= "<div><i class=\"fa fa-ellipsis-v more_day\" aria-hidden=\"true\" data=\"" . $noJour . "\"></i></div>";
				$html .= "</div>\n";
		
				$html .= "<div class=\"case_act\">";
				$html .= "<div class=\"details_day\">";
				$html .= $this->buildCalendarCaseDetails($noJour, $jourSem, $wkDay->custCode, $wkDay->actLbl);
				$html .= "</div>";
				$html .= "<label";
				if ( $empty )		$html .= " class=\"case_empty\"";
				$html .= ">Ouvert &nbsp;<input type=\"checkbox\" name=\"ouv[". $noJour ."]\" ";
				if ( $actif )		$html .= " checked";
				if ( $empty )		$html .= " disabled";
				$html = $html .= "/></label>";
				$html .= "</div>\n";
				
				$html = $html . "</td>\n";
			}
			if ($jourSem == 5) $html .= "</tr>\n";
		}
		$html .= "</tbody></table>\n";
		echo $html;
	}
	
	public static function buildCalendarCaseDetails($noJour, $jourSem, $custCode, $actLbl) {
		$html = "";
		$std = false;
		$mer_lib = false;
		if( $custCode != "" ) {
			$cust = $custCode . "0000000";
			if( $jourSem == 3 ) {
				if( substr($cust, 0, 5) == "11110" )
					$std = true;
			} else {
				if( substr($cust, 0, 5) == "10001" )
					$std = true;
			}
			if( !$std ) {				
				$act = "";
				if( (substr($cust, 0, 1) == '1')					)				$act .= "Matin, ";
				if( ($jourSem == 3) && (substr($cust, 1, 1) == '1')	)				$act .= "Midi, ";
				if( ($jourSem == 3) && (substr($cust, 2, 1) == '1')	)				$act .= "Repas, ";
				if( ($jourSem == 3) && (substr($cust, 3, 1) == '1')	)				$act .= "Après-midi, ";
				if( ($jourSem != 3) && (substr($cust, 4, 1) == '1')	)				$act .= "Soir, ";
				
				if( strlen($act) > 2 )
					$html .= "Uniquement " . substr($act, 0, strlen($act)-2);
			}			
			if( ($jourSem == 3) && (substr($cust, 6, 1) == '1')	) {
				$mer_lib = true;
				if( strlen($html) > 0)		$html = "<br/>" . $html;
				$html = "<strong>Mercredi libéré</strong>" . $html;
			}
			if( ($jourSem == 3) && (substr($cust, 5, 1) == '1') && (strlen($actLbl) > 0) ) {
				if( strlen($html) > 0)		$html .= "<br/>";
				$html .= "Activité: " . $actLbl;
			}
		}
		// Réinitialisation du code dans le cas particulier où tout est à zéro !
		if( $custCode != "" ) {
			$cust = $custCode . "000000";
			if( $jourSem == 3 ) {
				if( (substr($cust, 0, 1) == '0') && (substr($cust, 1, 1) == '0') && (substr($cust, 2, 1) == '0') && (substr($cust, 3, 1) == '0') )
					$custCode = "111100";
			} else {
				if( (substr($cust, 0, 1) == '0') && (substr($cust, 4, 1) == '0') )
					$custCode = "100010";
			}
		}
		
		// Mise à blanc des valeurs standards
		$cust = $custCode . "000000";
		$std = false;
		if( $jourSem == 3 ) {
			if( substr($cust, 0, 6) == "111100" )
				$std = true;
		} else {
			if( substr($cust, 0, 6) == "100010" )
				$std = true;
		}
		if( $std & !$mer_lib )
			$custCode = "";
		
		$html .= "<input type=\"hidden\" name=\"cus[". $noJour ."]\" value=\"" . $custCode	. "\">\n";
		$html .= "<input type=\"hidden\" name=\"alb[". $noJour ."]\" value=\"" . $actLbl	. "\">\n";
		return $html;
	}
	
	public function parse_request() {
		if(!isset($_POST['submit'])) return;
		
		$this->setDaysToInactif();
		if(!isset($_POST['cus'])) return;
		foreach( $_POST['cus'] as $noJour => $value ) {
			
			$isOpen = false;
			if( isset($_POST['ouv']) )
				$isOpen = isset($_POST['ouv'][$noJour]);
			
			$custCode = "";
			if( isset($_POST['cus']) )
				if( isset($_POST['cus'][$noJour]) )
					$custCode = $_POST['cus'][$noJour];
			
			$actLbl = "";
			if( isset($_POST['alb']) )
				if( isset($_POST['alb'][$noJour]) )
					$actLbl = $_POST['alb'][$noJour];
			
			$this->updateDay($noJour, $isOpen, $custCode, $actLbl);
		}
		
		$this->success_msg = "Mise à jour enregistrée !";
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function isOpenedDay($noJour) {
		if (!is_numeric($noJour)) return false;
		$ret = false;
		$query = "SELECT `OUV` FROM `calendrier` WHERE `NUM_JOUR`=" . $noJour . " LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = $res[0] == 1;
			$stmt->close();
		}
		return $ret;
	}
	
	public function loadDay($noJour, $jourSem) {;
		$ret = new CalendarEntity();
		$query = "SELECT `OUV`, `CONFIG`, `ACTIVITE` FROM `calendrier` WHERE `NUM_JOUR`='" . $noJour . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$ret->noJour	= intval($noJour);
				$ret->ouv		= (intval($res[0]) == 1);
				$ret->custCode	= $res[1];
				$ret->actLbl	= $res[2];
				
				if( strlen($ret->custCode) != 0 ) {
					$cust = $ret->custCode . "0000000";
					if( substr($cust, 0, 1) == '1' ) $ret->ouv_mat = true;
					if( substr($cust, 1, 1) == '1' ) $ret->ouv_mid = true;
					if( substr($cust, 2, 1) == '1' ) $ret->ouv_rep = true;
					if( substr($cust, 3, 1) == '1' ) $ret->ouv_apm = true;
					if( substr($cust, 4, 1) == '1' ) $ret->ouv_soi = true;
					if( substr($cust, 5, 1) == '1' ) $ret->ouv_act = true;
					if( substr($cust, 6, 1) == '1' ) $ret->mer_lib = true;
					
				} else {
					if( $jourSem == 1 || $jourSem == 2 || $jourSem == 4 || $jourSem == 5) {
						$ret->ouv_mat = true;
						$ret->ouv_soi = true;
					} elseif( $jourSem == 3) {
						$ret->ouv_mat = true;
						$ret->ouv_mid = true;
						$ret->ouv_rep = true;
						$ret->ouv_apm = true;
						$ret->mer_lib = false;
					}
				}
			}
			$stmt->close();
		}
		return $ret;
	}
	
	private function setDaysToInactif() {
		$query = "UPDATE `calendrier` SET `OUV`=0 WHERE `NUM_JOUR`>=" . $this->fdomNoJour . " AND `NUM_JOUR`<=" . $this->ldomNoJour;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateDay($noJour, $isOpen, $custCode, $actLbl) {
		if (!is_numeric($noJour)) return;
		if ($noJour < $this->fdomNoJour) return;
		if ($noJour > $this->ldomNoJour) return;
		$query = "UPDATE `calendrier` SET " .
					"`OUV`="			. ($isOpen ? 1 : 0) . ", " .
					"`CONFIG`='"		. $custCode . "', " .
					"`ACTIVITE`='"		. $actLbl . "' " .
				 "WHERE `NUM_JOUR`="	. $noJour;
//		echo $query . '<br/>';
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
}

/**************************************************************************
 * CalendarEntity
 * 1 entité par jour du calendrier
 **************************************************************************/
class CalendarEntity {
	public $noJour			= 0;
	public $ouv				= false;
	public $custCode		= "";
	public $actLbl			= "";
	
	public $ouv_mat			= false;
	public $ouv_mid			= false;
	public $ouv_rep			= false;
	public $ouv_apm			= false;
	public $ouv_soi			= false;
	public $ouv_act			= false;
	
	public $mer_lib			= false;
}
?>