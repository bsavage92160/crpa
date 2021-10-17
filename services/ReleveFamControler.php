<?php
require_once('../services/Database.php');

class ReleveFamControler {
	
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
			
	public $maxRelevNoJour	= 0;		// type: int (8 digits - Ymd)
	
	public $familyId		= 0;		// type: int

	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize($familyId) {
		date_default_timezone_set('UTC');
		
		// Define the max day for the 'releve' functionnalities
		$this->maxRelevNoJour	= intval(date('Ymd', time()));
		
		// Parse request
		if(isset($_GET['wd']) && is_numeric($_GET['wd'])) {
			$this->currentNoJour	= intval($_GET['wd']);
			if( $this->currentNoJour > $this->maxRelevNoJour )
				$this->currentNoJour = $this->maxRelevNoJour;
			$d = DateTime::createFromFormat('Ymd', $this->currentNoJour);
			if($d != false) $this->currentTimestamp	= intval($d->getTimestamp());
		}
		if(isset($_POST['wd']) && is_numeric($_POST['wd'])) {
			$this->currentNoJour	= intval($_POST['wd']);
			if( $this->currentNoJour > $this->maxRelevNoJour )
				$this->currentNoJour = $this->maxRelevNoJour;
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
		
		// Initialize the fisrt day of the week (monday)
		$this->firstTimestamp	= $this->currentTimestamp - 3600 * 24 * ($this->currentDay - 1);
		$this->firstJJ			= intval(date('d', $this->firstTimestamp));
		$this->firstMM			= intval(date('m', $this->firstTimestamp));
		$this->firstYear		= intval(date('Y', $this->firstTimestamp));
		$this->firstNoJour		= intval(date('Ymd', $this->firstTimestamp));
		
		// Initialize the last day of the week (friday)
		$this->lastTimestamp	= $this->firstTimestamp + 3600 * 24 * 4;
		$this->lastJJ			= intval(date('d', $this->lastTimestamp));
		$this->lastMM			= intval(date('m', $this->lastTimestamp));
		$this->lastYear			= intval(date('Y', $this->lastTimestamp));
		$this->lastNoJour		= intval(date('Ymd', $this->lastTimestamp));
		
		// Family Id
		$this->familyId			= $familyId;
		
		// Initialize database connection
		$this->db = Database::getInstance();
		$this->mysqli = $this->db->getConnection();
	}
	
	public function build_calendar() {
		$html = "";
		
		$html .= "<div style=\"display: inline-flex;\">\n";
		
		$d = intval(date('Ymd', $this->currentTimestamp - 3600 * 24 * 7));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $d . "'\">&lt;</button>\n";
		
		$d = intval(date('Ymd', $this->currentTimestamp + 3600 * 24 * 7));
		$html .= "<button type=\"button\" class=\"btn btn-default";
		if ( $d  >  $this->maxRelevNoJour ) $html .= " disabled\" ";
		else $html .= "\" onclick=\"location.href='?wd=" . $d . "'\"";
		$html .= ">&gt;</button>\n";
		
		$today = intval(date('Ymd', time()));
		$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $today . "'\">Aujourd'hui</button>\n";
		
		$html .= "<div class=\"input-group date form_date col-md-5\" data-date=\"\" data-date-format=\"dd/mm/yyyy\" data-link-format=\"yyyymmdd\">\n";
		$html .= "<input class=\"form-control\" style=\"width: 160px; text-align: left;\" type=\"hidden\" value=\"\" readonly>\n";
		$html .= "<span style=\"border-radius: 4px; background-color: #fff;\" class=\"input-group-addon\" style=\"padding: 0px 12px 0px 12px;\"><span class=\"glyphicon glyphicon-calendar\"></span></span>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "&nbsp;&nbsp;<label>Semaine du " . date('d/m/Y', $this->firstTimestamp) . " au " . date('d/m/Y', $this->lastTimestamp) . "</label>\n";
		
		$html .= "<table id=\"releve-table\" class=\"table table-bordered table-hover table-responsive\">\n";
		$html .= "<thead><tr><th rowspan=\"2\" colspan=\"2\" >&nbsp;</td>";
		
		$colspan = array(2, 2, 5, 2, 2);
		$days = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi");
		$activeCols = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
		$numDays = array();
		$pos = 0;
		for ($i=0; $i<5; $i++) {
			$wDay = $this->firstTimestamp + 3600 * 24 * $i;
			$noJour = intval(date('Ymd', $wDay));
			$actif = $this->isOpenedDay($noJour);
			$html .= "<th colspan=\"" . $colspan[$i] . "\" ";
			$cls = "";
			if (!$actif)				$cls .= "inactive_day ";
			if ($noJour == $today)		$cls .= "today ";
			if ($cls != "")				$html .= "class=\"" . $cls . "\" ";
			$html .= ">" . $days[$i] . " " . date('d/m', $wDay) . "</th>";
			for ($j=$pos; $j<$pos+$colspan[$i]; $j++) {
				if ($actif != 1) $activeCols[$j] = 0;
				$numDays[$j] = $noJour;
			}
			$pos += $colspan[$i];
		}
		$html .= "</tr><tr>";
		$days = array("Matin", "Soir", "Matin", "Soir", "Matin", "Libéré", "Midi", "Repas", "APM", "Matin", "Soir", "Matin", "Soir");
		for ($i=0; $i<13; $i++) {
			$noJour = $numDays[$i];
			$cls = "";
			if ($activeCols[$i] != 1)	$cls .= "inactive_day ";
			if ($noJour == $today)		$cls .= "today ";
			if ($cls == "")				$html .= "<th>";
			else						$html .= "<th  class=\"" . $cls . "\">";
			$html .= $days[$i] . "</th>";
		}
		$html .= "</tr></thead>\n<tbody>\n";
		
		$codActLst = array("MAT", "SOI", "MAT", "SOI", "MAT", "LIB", "MID", "REP", "APM", "MAT", "SOI", "MAT", "SOI");
		$query = "SELECT ID, NOM, PRENOM " .
				 "FROM enfant " .
				 "WHERE ID_FAMILLE=" . $this->familyId . " " .
				 "AND ACTIF=1 " .
				 "ORDER BY NOM, PRENOM";
		$stmt = $this->mysqli->query($query);
		$lineNb =0;
		
		$subTot = array();		// [$i] | [$OptCode or 0 with resa or 5 without resa]
		for ($i=0; $i<13; $i++)
			for ($j=0; $j<7; $j++)
				$subTot[$i][$j] = 0;
		
		$nb = 0;
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$nb++;
				$childId		= $res[0];
				$childName		= strtoupper($res[1]);
				$childFirstName	= ucfirst(strtolower($res[2]));
				
				$html .= "<tr>";
				
				$html .= "<td>" . $childFirstName . "</td>";
				$html .= "<td>Présence avec réservation<br/>Présence sans réservation<br/>Réservation non honorée<br/>Autres</td>";
				
				for ($i=0; $i<13; $i++) {
					$noJour = $numDays[$i];
					
					$cls = "";
					if ($activeCols[$i] != 1)	$cls .= "inactive_day ";
					if ($noJour == $today)		$cls .= "today ";
					if ($cls == "")				$html .= "<td>";
					else						$html .= "<td  class=\"" . $cls . "\">";
					
					if ($activeCols[$i] == 1) {
						$codAct = $codActLst[$i];
						$relev = $this->getReleve($childId, $noJour, $codAct);
						if ($relev == false) {
							$html .= "-<br/>-<br/>-<br/>-";
						} else {
							$optCod = $relev->OPT;
							if( $optCod == 9) {
								$html .= "-<br/>-<br/>1<br/>";
								$subTot[$i][6]++;
							} else {
								if ($relev->RESA == 1) {
									$subTot[$i][0]++;
									$html .= "1<br/>-<br/>-<br/>";
								} else {
									$subTot[$i][5]++;
									$html .= "-<br/>1<br/>-<br/>";
								}
							}
							if( $optCod != 0 && $optCod < 5 ) $subTot[$i][$optCod]++;
							$html .= $this->getOptLabel($optCod);
						}
					}
					$html .= "</td>";
				}
				$html .= "</tr>\n";
			}
			$stmt->close();
		}
		
		if( $nb > 1) {
			$html .= "<tr class=\"total\"><td>TOTAL</td>\n";
			$html .= "<td>Présence avec réservation<br/>Présence sans réservation<br/>Réservation non honorée<br/>Gratuité<br/>Pénalité Ret.15'<br/>Pénalité Ret.30'<br/>Pénalité Ret.1h</td>";
			for ($i=0; $i<13; $i++) {
					$noJour = $numDays[$i];
					$cls = "";
					if ($activeCols[$i] != 1)	$cls .= "inactive_day ";
					if ($noJour == $today)		$cls .= "today ";
					if ($cls == "")				$html .= "<td>";
					else						$html .= "<td  class=\"" . $cls . "\">";
					if ($activeCols[$i] == 1) {
						$val = $subTot[$i][0];
						if ($val == 0)	$html .= "-";
						else			$html .= $val;
						$html .= "<br/>";
						$val = $subTot[$i][5];
						if ($val == 0)	$html .= "-";
						else			$html .= $val;
						$html .= "<br/>";
						$val = $subTot[$i][6];
						if ($val == 0)	$html .= "-";
						else			$html .= $val;
						$html .= "<br/>";
						for ($j=1; $j<5; $j++) {
							$val = $subTot[$i][$j];
							if ($val == 0)	$html .= "-";
							else			$html .= $val;
							if ($j < 5)		$html .= "<br/>";
						}
					}
					$html .= "</td>";
			}
			$html .="</tr>";
		}
		$html .= "</tbody>\n";

		$html .= "</table>\n";
		echo $html;
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
				$ret = ($res[0] == 1);
			$stmt->close();
		}
		return $ret;
	}
	
	private function getReleve($childId, $noJour, $codAct) {
		$ret = null;
		$query = "SELECT `OPT`, `RESA` ".
				 "FROM `releve` " .
				 "WHERE `ID_ENFANT`="	. $childId	. " AND " . 
					   "`NUM_JOUR`="	. $noJour	. " AND " .
					   "`CODE_ACT`="	. $codAct	. " " .
				 "LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_object())
				$ret = $res;
			$stmt->close();
		}
		return $ret;
	}
	
	private function getOptLabel($optCode) {
		if ($optCode == 1)		return "Grat.";
		else if ($optCode == 2)	return "Ret.15'";
		else if ($optCode == 3)	return "Ret.30'";
		else if ($optCode == 4)	return "Ret.1h";
		return "-";
	}
}
?>