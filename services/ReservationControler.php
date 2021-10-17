<?php
require_once('../services/Database.php');
require_once('../services/ParameterManager.php');
require_once('../services/CalendarControler.php');

class ReservationControler {
	
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
	
	public $maxResaNoJour	= 0;		// type: int (8 digits - Ymd)
	
	public $isAdmin			= false;	// type: boolean
	public $familyId		= 0;		// type: int
	public $currentChildId	= 0;		// type: int
	
	private $months			= array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
	
	public $success_msg		= "";		// type: String

	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize($isAdmin, $familyId) {
		date_default_timezone_set('UTC');
		
		$this->isAdmin = $isAdmin;
		$this->familyId			= $familyId;
		
		// Parse request : wd parameter
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
		
		// Define the max day for the 'reservation' functionnalities
		$this->maxResaNoJour	= intval(date('Ymd', time() + 3600 * 24 * ParameterManager::getInstance()->resa));
		
		// Initialize database connection
		$this->db				= Database::getInstance();
		$this->mysqli			= $this->db->getConnection();
		
		// Parse request : Current child Id
		$this->currentChildId = 0;
		if(isset($_GET['ch']) && is_numeric($_GET['ch']))		$this->currentChildId	= intval($_GET['ch']);
		if(isset($_POST['ch']) && is_numeric($_POST['ch']))		$this->currentChildId	= intval($_POST['ch']);
		
		if( ($this->currentChildId == 0) ||
		    !is_numeric($this->currentChildId) )
					$this->currentChildId	= $this->getDefaultChildId();
	}
	
	public function prebuild_calendar() {
		$html  = "";
		$html .= "<form method=\"post\">";
		$html .= "<div class=\"form-group\" style=\"margin-top: 10px;\"><select name=\"ch\" class=\"form-control\" id=\"formCtlChild\">\n";
		
		$query = "SELECT ID, NOM, PRENOM FROM enfant WHERE ";
		if( !$this->isAdmin ) $query .= "ID_FAMILLE=" . $this->familyId . " AND ";
		$query .= "ACTIF=1";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$id		= $res[0];
				$nom	= strtoupper($res[1]);
				$prenom	= ucfirst(strtolower($res[2]));
				$html .= "<option value=\"" . $id . "\" ";
				if( $id == $this->currentChildId ) $html .= "selected";
				$html .= ">" . $nom . " " . $prenom . "</option>";
			}
		}
		$html .= "</select></div>\n";
		$html .= "</form>\n";
		echo  $html;
	}
	
	public function build_calendar() {
		$html = "";
				
		$html .= "<input type=\"hidden\" name=\"ch\" value=\"" . $this->currentChildId . "\" />\n";
		$html .= "<input type=\"hidden\" name=\"wd\" value=\"" . $this->currentNoJour . "\" />\n";

		$html .= "<table style=\"white-space: nowrap; width: 100%; margin: 0;\">\n";
		$html .= "<tr><td>\n";
				
		$d = intval(date('Ymd', mktime(0, 0, 0, $this->currentMonth - 1, $this->currentDay, $this->currentYear)));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $d . "&ch=" . $this->currentChildId . "'\">&lt;</button>\n";
		
		$d = intval(date('Ymd', mktime(0, 0, 0, $this->currentMonth + 1, $this->currentDay, $this->currentYear)));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $d . "&ch=" . $this->currentChildId . "'\">&gt;</button>\n";
		
		$today = intval(date('Ymd', time()));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $today . "&ch=" . $this->currentChildId . "'\">Aujourd'hui</button>\n";
		$html .= "&nbsp;&nbsp;<label>" . $this->months[$this->currentMonth - 1] . " " . intval(date('Y', $this->currentTimestamp)) . "</label>\n";
		
		$html .= "</td>\n";
		$html .= "<td style=\"vertical-align:middle;text-align: right;\">";
		$html .= "<input type=\"submit\" value=\"Enregistrer\" name=\"submit\" class=\"btn btn-success\"></td>\n";
		$html .= "</table>\n";
		
		$html .= "<table class=\"calendar_table\">\n";
		$html .= "<thead><tr><th></th><th>LUNDI</th><th>MARDI</th><th>MERCREDI</th><th>JEUDI</th><th>VENDREDI</th></tr></thead>\n";
		$html .= "<tbody>";
		
		$codActLst		= array (
							1=>array("MAT", "SOI"),									// Lundi
							2=>array("MAT", "SOI"),									// Mardi
							3=>array("MAT", "LIB", "MID", "REP", "APM", "ACT" ),	// Mercredi
							4=>array("MAT", "SOI"),									// Jeudi
							5=>array("MAT", "SOI") );								// Vendredi
		
		$codTiles		= array(
							"MAT"=>"Matin",
							"SOI"=>"Soir",
							"MID"=>"Midi",
							"REP"=>"Repas",
							"APM"=>"APM",
							"ACT"=>"Activités",
							"LIB"=>"Libéré");
		
		$cssActLst		= array(
							"MAT"=>"chk_mat",
							"SOI"=>"chk_soi",
							"MID"=>"chk_mid",
							"REP"=>"chk_rep",
							"APM"=>"chk_apm",
							"ACT"=>"chk_act",
							"LIB"=>"chk_lib");
							
		$titleActLst	= array(
							"MAT"=>"Surveillance matin",
							"SOI"=>"Présence",
							"MID"=>"Surveillance midi",
							"REP"=>"Repas commandé",
							"APM"=>"Présence",
							"ACT"=>"Présence",
							"LIB"=>"Matinée libéré" );
		
		$cal = new CalendarControler();
		
		for ($wDay = $this->firstTimestamp; $wDay < $this->lastTimestamp ; $wDay = $wDay + 3600 * 24) {
			$noJour  = intval(date('Ymd', $wDay));
			$noSem	 = intval(date('W', $wDay));
			$jourSem = intval(date('N', $wDay));
			$jj		 = date('d', $wDay);
			$mm		 = date('m', $wDay);
			
			$wkDay	 = $cal->loadDay($noJour, $jourSem);
			$actif	 = $wkDay->ouv;
			if ($noJour < $this->maxResaNoJour) $actif = false;
			$empty	 = ($wDay < $this->firstDayOfMonth) || ($wDay > $this->lastDayOfMonth);
			
			if ($jourSem == 1) $html .= "<tr><td class=\"case_num_sem\">" . $noSem . "</td>\n";
			if ($jourSem !=6 && $jourSem !=7) {
				$html .= "<td";
				$html .= " class=\"";
				if ($empty)				$html .= " case_empty";
				elseif (!$actif)		$html .= " case_inactive";
				if ($noJour == $today) 	$html .= " today";
				$html .= "\"";
				$html .= "><div class=\"case_jour\"><div>" . $jj . "/" . $mm . "</div></div>\n";
				$html .= "<div class=\"case_act\">";
				
				for( $i=0; $i<count($codActLst[$jourSem]); $i++ ) {
					$codAct		= $codActLst[$jourSem][$i];
					$codTile	= $codTiles[$codAct];
					$resa		= $this->isReservation($this->currentChildId, $noJour, $codAct);
					$cssChk		= $cssActLst[$codAct];
					$title		= $titleActLst[$codAct];
					
					$isOpen		= true;
					if( $codAct == "MAT" ) 			$isOpen = $wkDay->ouv_mat;
					elseif( $codAct == "MID" ) 		$isOpen = $wkDay->ouv_mid;
					elseif( $codAct == "REP" ) 		$isOpen = $wkDay->ouv_rep;
					elseif( $codAct == "APM" ) 		$isOpen = $wkDay->ouv_apm;
					elseif( $codAct == "SOI" ) 		$isOpen = $wkDay->ouv_soi;
					elseif( $codAct == "ACT" ) 		$isOpen = $wkDay->ouv_act;
					elseif( $codAct == "LIB" ) 		$isOpen = $wkDay->mer_lib;
					
					if( !$actif )  					$isOpen	= false;
					
					if( ($codAct == "ACT") && $isOpen )	$codTile = $wkDay->actLbl;
					
					$html .= "<label ";
					if ($empty || !$actif || !$isOpen) $html .= " class=\"case_empty\"";
					$html .= " title=\"" . $title . "\" ";
					$html .= ">" . $codTile . "&nbsp;<input type=\"checkbox\" name=\"resa[". $noJour ."][" . $codAct . "]\" ";
					if ($resa) $html .= "checked ";
					if ($empty || !$actif || !$isOpen) $html .= "disabled ";
					else $html .= "class=\"" . $cssChk . "\" ";
					$html = $html . "/></label>&nbsp;";
				}
				$html = $html . "</div>\n";
				$html = $html . "</td>\n";
			}
			if ($jourSem == 5) $html .= "</tr>\n";
		}
		$html .= "</tbody></table>\n";
		echo $html;
	}
	
	public function build_same_choice_forms() {
		$html  = "";
		$query = "SELECT `ID`, `NOM`, `PRENOM` FROM `enfant` WHERE " .
		         "`ID_FAMILLE`=(SELECT `ID_FAMILLE` FROM `enfant` WHERE `ID`='" . $this->currentChildId . "') AND " .
				 "`ACTIF`='1' AND " .
				 "`ID`<>'" . $this->currentChildId . "'";
		$stmt = $this->mysqli->query($query);
		$nb = 0;
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$id		= $res[0];
				$nom	= strtoupper($res[1]);
				$prenom	= ucfirst(strtolower($res[2]));
				if( $nb++ > 0)
					$html .= ", ";
				$html .= "&nbsp;<label><input type=\"checkbox\" name=\"same-choice[" . $id . "]\" />&nbsp;" . $prenom . "</label>";
			}
		}
		if( $html == "")	return "";
		$html = "<div class=\"same_choice\"><strong>Effectuer la même saisie pour :</strong>" . $html . "</div>";
		echo  $html;
	}
	
	public function parse_request() {
		$this->parse_request_resa();
	}
	
	private function parse_request_resa() {
		if(!isset($_POST['submit'])) return;
		$this->deleteCurrentReservations($this->currentChildId);
		if(!isset($_POST['resa'])) return;
		foreach( $_POST['resa'] as $noJour => $codActs ) {
			if ($noJour >= $this->maxResaNoJour) {
				if( isset($codActs["REP"]) )
					$codActs["MID"] = "on";		// Si saisie d'un REPAS, on force la réservation du MIDI
				foreach( $codActs as $codAct => $value ) {
					$this->addReservation($this->currentChildId, $noJour, $codAct);
				}
			}
		}
		
		// Traitement spécifique pour frères et soeurs
		if( isset($_POST['same-choice']) ) {
			foreach( $_POST['same-choice'] as $noChild => $chk ) {
				if( $this->isSameFamily($this->currentChildId, $noChild) ) {
					$this->deleteCurrentReservations($noChild);
					foreach( $_POST['resa'] as $noJour => $codActs ) {
						if ($noJour >= $this->maxResaNoJour) {
							if( isset($codActs["REP"]) )
								$codActs["MID"] = "on";		// Si saisie d'un REPAS, on force la réservation du MIDI
							foreach( $codActs as $codAct => $value ) {
								$this->addReservation($noChild, $noJour, $codAct);
							}
						}
					}
				}
			}
		}
		$this->success_msg = "Demande de réservation enregistrée !";
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function isReservation($childId, $noJour, $codAct) {
		$ret = false;
		$query = "SELECT * FROM `reservation` WHERE `ID_ENFANT`=" . $childId . " AND `NUM_JOUR`=" . $noJour . " AND `CODE_ACT`='" . $codAct . "' LIMIT 1";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = true;
			$stmt->close();
		}
		return $ret;

	}
	
	public function getDefaultChildId() {
		$ret = -1;
		$query = "SELECT `ID` FROM `enfant` WHERE ";
		if( !$this->isAdmin) $query .= "`ID_FAMILLE`=" . $this->familyId . " AND ";
		$query .= "`ACTIF`=1 ORDER BY ID LIMIT 1";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = $res[0];
			$stmt->close();
		}
		return $ret;
	}
	
	private function deleteCurrentReservations($childId) {
		$query = "DELETE FROM `reservation` " .
				 "WHERE `ID_ENFANT`="	. $childId				. " AND " .
				 "`NUM_JOUR` >= "		. $this->maxResaNoJour	. " AND " .
				 "`NUM_JOUR` >= "		. $this->fdomNoJour		. " AND " .
				 "`NUM_JOUR` <= "		. $this->ldomNoJour		. "";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function addReservation($childId, $noJour, $codAct) {
		if (!$this->isValideCodeAct($codAct)) return;
		$query = "INSERT INTO `reservation` (`ID_ENFANT`, `NUM_JOUR`, `CODE_ACT`) VALUES(" . $childId . ", " . $noJour . ", '" . $codAct . "')";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function isValideCodeAct($codAct) {
		return ($codAct == "MAT") ||	// Matin
		       ($codAct == "SOI") ||	// Soir
			   ($codAct == "MID") ||	// Midi (Mercredi)
			   ($codAct == "REP") ||	// Repas (Mercredi)
			   ($codAct == "APM") ||	// Après-midi (Mercredi)
			   ($codAct == "ACT") ||	// Activité (Mercredi)
			   ($codAct == "LIB");		// Mercredi libéré
	}
	
	private function isSameFamily($childId1, $childId2) {
		$ret = false;
		$query = "SELECT (SELECT `ID_FAMILLE` FROM `enfant` WHERE `ID`=$childId1), (SELECT `ID_FAMILLE` FROM `enfant` WHERE `ID`=$childId2)";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = ($res[0] == $res[1]);
			$stmt->close();
		}
		return $ret;
	}
}
?>