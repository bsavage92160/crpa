<?php
require_once('../services/Database.php');
require_once('../services/ParameterManager.php');
require_once('../services/CalendarControler.php');

class ReleveControler {
	
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
	public $maxResaNoJour	= 0;		// type: int (8 digits - Ymd)
	
	public $success_msg		= "";		// type: String

	
	/**************************************************************************
	 * Constante
	 **************************************************************************/
	const CODE_ACT_LST		= array("MAT", "SOI", "MAT", "SOI", "MAT", "LIB", "MID", "REP", "APM", "MAT", "SOI", "MAT", "SOI");
	const NUM_DAYS_LST		= array(0    , 0    , 1    , 1    , 2    , 2    , 2    , 2    , 2    , 3    , 3    , 4    , 4    );
	const COLOR_TABLE		= array(0=>"#ffffff", 1=>"#00ff00", 2=>"#ffff00", 3=>"#ff9900", 4=>"#ff0000");
		
		
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize() {
		date_default_timezone_set('UTC');
		
		// Define the max day for the 'releve' functionnalities
		$this->maxRelevNoJour	= intval(date('Ymd', time()));
		
		// Define the max day for the 'reservation' functionnalities
		$this->maxResaNoJour	= intval(date('Ymd', time() + 3600 * 24 * ParameterManager::getInstance()->resa ));
		
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
		
		// Initialize database connection
		$this->db = Database::getInstance();
		$this->mysqli = $this->db->getConnection();
	}
	
	/**
	 * Génération du code HTML pour :
	 * <ul>
	 *    <li> Page de pointage des présences pour les animateurs (edit-releve.php)
	 *    <li> Page de consultation du pointage/relève des présences pour les parents (print-releve.php)
	 * </ul>
	 * @param $editable	(boolean)	<tt>true</tt> pour le pointage des présences
	 * 								<tt>false</tt> pour la consultation des réservations
	 */
	public function build_calendar($editable = false, $limit = '') {
		
		$tbody = "<tbody><tr><td style=\"text-align: left\"><div style=\"height: 35px; overflow:hidden;\">\n";
		
		$tbody .= "<div style=\"display: inline-flex;\">\n";
		
		$d = intval(date('Ymd', $this->currentTimestamp - 3600 * 24 * 7));
		$tbody .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $d . "'\">&lt;</button>\n";
		
		$d = intval(date('Ymd', $this->currentTimestamp + 3600 * 24 * 7));
		$tbody .= "<button type=\"button\" class=\"btn btn-default";
		if ( ($editable && $d  >  $this->maxRelevNoJour) ||
		     (!$editable && $d >  $this->maxResaNoJour) ) $tbody .= " disabled\" ";
		else $tbody .= "\" onclick=\"location.href='?wd=" . $d . "'\"";
		$tbody .= ">&gt;</button>\n";
		
		$today = intval(date('Ymd', time()));
		$tbody .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='?wd=" . $today . "'\">Aujourd'hui</button>\n";
		
		$tbody .= "<div class=\"input-group date form_date col-md-5\" data-date=\"\" data-date-format=\"dd/mm/yyyy\" data-link-format=\"yyyymmdd\">\n";
		$tbody .= "<input class=\"form-control\" style=\"width: 160px; text-align: left;\" type=\"hidden\" value=\"\" readonly>\n";
		$tbody .= "<span style=\"border-radius: 4px; background-color: #fff;\" class=\"input-group-addon\" style=\"padding: 0px 12px 0px 12px;\"><span class=\"glyphicon glyphicon-calendar\"></span></span>\n";
		$tbody .= "</div>\n";
		$tbody .= "</div>\n";
		
		$tbody .= "&nbsp;&nbsp;<label>Semaine du " . date('d/m/Y', $this->firstTimestamp) . " au " . date('d/m/Y', $this->lastTimestamp) . "</label>\n";
		
		$tbody .= "</div>";
		$tbody .= "</td>\n";
		
		if ( $editable ) {
			$tbody .= "<td style=\"vertical-align:middle;text-align: right\">";
			$tbody .= "<div style=\"height: 35px; overflow:hidden;\"><input type=\"submit\" value=\"Enregistrer\" name=\"submit\" class=\"btn btn-success\"></div>";
			$tbody .= "</td>\n";
		}
		$tbody .= "</tr>";
		$tbody .= "</tbody>";
		
		$thead = "<tr><td>&nbsp;</td>";
		
		$colspan = array(2, 2, 5, 2, 2);
		$days = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi");
		$activeCols = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
		$openedCols = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
		$numDays = array();
		$pos = 0;
		$cal = new CalendarControler();
		
		for ($i=0; $i<5; $i++) {
			$wDay	 = $this->firstTimestamp + 3600 * 24 * $i;
			$noJour  = intval(date('Ymd', $wDay));
			$jourSem = intval(date('N', $wDay));
			$wkDay	 = $cal->loadDay($noJour, $jourSem);
			$actif	 = $wkDay->ouv;
			$openedPeriod = $this->isOpenedPeriod($noJour);
			
			$cls = "";
			if (!$actif)			$cls .= "inactive_day ";
			if (!$openedPeriod)		$cls .= "inactive_per ";
			if ($noJour == $today)	$cls .= "today ";
			if ($cls == "")			$thead .= "<td>";
			else					$thead .= "<td  class=\"" . $cls . "\">";
			
			$thead .= $days[$i] . " " . date('d/m', $wDay) . "</th>";
			
			$j=$pos;
			if( $i==0 || $i==1 || $i==3 || $i==4 ) {					// Lundi, Mardi, Jeudi, Vendredi
				if (!$actif) {
					$activeCols[$j]		= 0;
					$activeCols[$j+1]	= 0;
				} else {
					$activeCols[$j]		= ($wkDay->custCode != '') ? ($wkDay->ouv_mat ? 1 : 0) : 1;
					$activeCols[$j+1]	= ($wkDay->custCode != '') ? ($wkDay->ouv_soi ? 1 : 0) : 1;
				}
				
			} else {												// Mercredi
				if (!$actif) {
					$activeCols[$j]		= 0;
					$activeCols[$j+1]	= 0;
					$activeCols[$j+2]	= 0;
					$activeCols[$j+3]	= 0;
					$activeCols[$j+4]	= 0;
				} else {
					$activeCols[$j]		= ($wkDay->custCode != '') ? ($wkDay->ouv_mat ? 1 : 0) : 1;
					$activeCols[$j+1]	= ($wkDay->custCode != '') ? ($wkDay->mer_lib ? 1 : 0) : 0;
					$activeCols[$j+2]	= ($wkDay->custCode != '') ? ($wkDay->ouv_mid ? 1 : 0) : 1;
					$activeCols[$j+3]	= ($wkDay->custCode != '') ? ($wkDay->ouv_rep ? 1 : 0) : 1;
					$activeCols[$j+4]	= ($wkDay->custCode != '') ? ($wkDay->ouv_apm ? 1 : 0) : 1;
				}
			}
			for ($j=$pos; $j<$pos+$colspan[$i]; $j++) {
				if (!$openedPeriod)		$openedCols[$j] = 0;
				$numDays[$j] = $noJour;
			}
			$pos += $colspan[$i];
		}
		
		$thead .= "</tr><tr><td>&nbsp;</td>";
		$days = array("Matin", "Soir", "Matin", "Soir", "Matin", "Libéré", "Midi", "Repas", "APM", "Matin", "Soir", "Matin", "Soir");
		
		for ($i=0; $i<13; $i++) {
			$noJour = $numDays[$i];
			$thead .= "<td class=\"";
			if ($activeCols[$i] != 1) $thead .= "inactive_day ";
			if ($openedCols[$i] != 1) $thead .= "inactive_per ";
			if ($noJour == $today) $thead .= "today ";
			$thead .= "\">" . $days[$i] . "</td>";
		}
		$thead .= "</tr>\n";
		
		$html  = "";
		$html .= "<table style=\"white-space: nowrap; width: 100%\">\n";
		$html .= $tbody;
		$html .= "</table>\n";
		
		if( $editable ) {
			$html .= "<table class=\"tbh table\"><tbody>\n";
			$html .= $thead . "\n";
			$html .= "</tbody></table>\n";
		}
		
		$html .= "</div>\n";
		$html .= "<div style=\"position: relative; display: block;\">\n";
		$html .= "<div id=\"nv-page-wrapper\">\n";
		
		$html .= "<table id=\"releve-table\" class=\"table table-striped table-hover tbc\">\n";
		
		if( !$editable ) {
			$html .= "<thead>\n";
			$html .= $thead . "\n";
			$html .= "</thead>\n";
		}
		
		$html .= "<tbody>\n";

		$subTot = array();
		for ($i=0; $i<13; $i++)
			$subTot[$i] = 0;
		
		$html .= $this->build_calendar_lines($editable, $subTot, $activeCols, $openedCols, $numDays, $today, $limit);
		
		$html .= "</tbody>\n";
		
		if ( !$editable ) {
			$html .= "<tfoot><tr><td>TOTAL</td>\n";
			for ($i=0; $i<13; $i++) {
					$noJour = $numDays[$i];
					$cls = "";
					if ($activeCols[$i] != 1)	$cls .= "inactive_day ";
					if ($openedCols[$i] != 1)	$cls .= "inactive_per ";
					if ($noJour == $today)		$cls .= "today ";
					if ($cls == "")				$html .= "<td>";
					else						$html .= "<td  class=\"" . $cls . "\">";
					if ($activeCols[$i] == 1) {
						$val = $subTot[$i];
						if ($val == 0)	$html .= "-";
						else			$html .= $val;
					}
					$html .= "</td>";
			}
			$html .="</tr></tfoot>";
		}
		
		$html .= "</table>\n";
		echo $html;
	}
	
	/**
	 * Génération du code HTML pour le tableau construit de façon dynamique AJAX
	 */
	public function build_calendar_append($limit = '') {
		$html = "";
		$today = intval(date('Ymd', time()));
		
		$colspan = array(2, 2, 5, 2, 2);
		$activeCols = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
		$openedCols = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
		$numDays = array();
		$pos = 0;
		$cal = new CalendarControler();
		
		for ($i=0; $i<5; $i++) {
			$wDay 	 = $this->firstTimestamp + 3600 * 24 * $i;
			$noJour  = intval(date('Ymd', $wDay));
			$jourSem = intval(date('N', $wDay));
			$wkDay	 = $cal->loadDay($noJour, $jourSem);
			$actif	 = $wkDay->ouv;
			$openedPeriod = $this->isOpenedPeriod($noJour);
			$j=$pos;
			
			if( $i==0 || $i==1 || $i==3 || $i==4 ) {					// Lundi, Mardi, Jeudi, Vendredi
				if (!$actif) {
					$activeCols[$j]		= 0;
					$activeCols[$j+1]	= 0;
				} else {
					$activeCols[$j]		= ($wkDay->custCode != '') ? ($wkDay->ouv_mat ? 1 : 0) : 1;
					$activeCols[$j+1]	= ($wkDay->custCode != '') ? ($wkDay->ouv_soi ? 1 : 0) : 1;
				}
				
			} else {												// Mercredi
				if (!$actif) {
					$activeCols[$j]		= 0;
					$activeCols[$j+1]	= 0;
					$activeCols[$j+2]	= 0;
					$activeCols[$j+3]	= 0;
					$activeCols[$j+4]	= 0;
				} else {
					$activeCols[$j]		= ($wkDay->custCode != '') ? ($wkDay->ouv_mat ? 1 : 0) : 1;
					$activeCols[$j+1]	= ($wkDay->custCode != '') ? ($wkDay->mer_lib ? 1 : 0) : 0;
					$activeCols[$j+2]	= ($wkDay->custCode != '') ? ($wkDay->ouv_mid ? 1 : 0) : 1;
					$activeCols[$j+3]	= ($wkDay->custCode != '') ? ($wkDay->ouv_rep ? 1 : 0) : 1;
					$activeCols[$j+4]	= ($wkDay->custCode != '') ? ($wkDay->ouv_apm ? 1 : 0) : 1;
				}
			}
			for ($j=$pos; $j<$pos+$colspan[$i]; $j++) {
				if (!$openedPeriod)		$openedCols[$j] = 0;
				$numDays[$j] = $noJour;
			}
			
			$pos += $colspan[$i];
		}
		$subTot = array();
		$html .= $this->build_calendar_lines(true, $subTot, $activeCols, $openedCols, $numDays, $today, $limit);
		echo $html;
	}
	
	private function build_calendar_lines($editable, &$subTot, $activeCols, $openedCols, $numDays, $today, $limit) {
		$html  = "";
		$query = "SELECT ID, NOM, PRENOM 
				  FROM enfant 
				  WHERE ACTIF=1				 
				  ORDER BY NOM, PRENOM
				  $limit
				 ";
		$stmt= $this->mysqli->query($query);
		$lineNb=0;
	
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$childId		= $res[0];
				$childName		= strtoupper($res[1]);
				$childFirstName	= ucfirst(strtolower($res[2]));
				
				$html .= "<tr>\n";
				
				$html .= "<td><div><div><span>" . $childName . " " . $childFirstName . "</span></div></div></td>";
				$inputVal = "0000000000000";
				for ($i=0; $i<13; $i++) {
					$noJour = $numDays[$i];
					$cls = "";
					if ($activeCols[$i] != 1)	$cls .= "inactive_day ";
					if ($openedCols[$i] != 1)	$cls .= "inactive_per ";
					if ($noJour == $today)		$cls .= "today ";
					
					$html .= "<td";
					if ($cls != "")	$html .= " class=\"" . $cls . "\"";
					$html .= " data-idx=\"" . $i . "\"";
					$html .= ">";
					
					if ($activeCols[$i] == 1) {
						$codAct = Self::CODE_ACT_LST[$i];
						
						// Editable version based only on releve table 
						if ( $editable == true ) {
							$relev   = $this->isReleve($childId, $noJour, $codAct);
							$resa 	 = $this->isReservation($childId, $noJour, $codAct);
							$optCode = $this->getOptCode($childId, $noJour, $codAct);
							
							if( $relev && $optCode > 0)	$inputVal[$i] = '' . ($optCode + 1);
							elseif( $relev )			$inputVal[$i] = '1';
							else						$inputVal[$i] = '0';
							
							$html .= "<span class=\"ct\">";
							$html .= "<span class=\"ctb";
							if		($i==0 || $i==2 || $i==4 || $i==9  || $i==11)	$html .= " mat";
							elseif	($i==1 || $i==3 || $i==8 || $i==10 || $i==12)	$html .= " soi";
							$html .= "\">";
							$html .= "<span class=\"ctr";
							if( $resa ) $html .= " checked";
							$html .= "\"></span>";
							$html .= "<span class=\"ctm";
							if( $relev ) $html .= " checked";
							if ($i!=5 && $i!=6 && $optCode>0) $html .= " checked" . ($optCode + 1);
							$html .= "\"></span>";
							$html .= "<span class=\"cto\"";
							if ($i!=5 && $i!=6)	$html .= " style=\"background-color:" . Self::COLOR_TABLE[$optCode] . ";\"";
							$html .= "></span>";
							$html .= "</span>";
							$html .= "</span>";
						
						// Printable version based only on reservation table (and not releve table !)
						} else {
							$resa = $this->isReservation($childId, $noJour, $codAct);						
							if ($resa) {
								$subTot[$i]++;
								$html .= "1";
							}
						}
					}
					$html .= "</td>\n";										
				}
				if ( $editable == true )
					$html .= "<input type=\"hidden\" name=\"rel[" . $childId . "]\" value=\"" . $inputVal . "\" />";
				$html .= "</tr>\n";
			}
			$stmt->close();
		}
		return $html;
	}
	
	/**
	 * Parse request for 'loadres' parameters : Déclenchemnt en cliquant sur le bouton "chargement des données de réservation"
	 */
	function parse_loadres_request() {
		if( !isset($_POST['loadres']) )			return;
		if( !is_numeric($_POST['loadres']) )	return;
		if( intval($_POST['loadres']) != 1 )	return;
		if( !isset($_POST['d']) )				return;
		
		for ($i=0; $i<5; $i++) {
			if( isset($_POST['d'][$i]) ) {
				$wDay = $this->firstTimestamp + 3600 * 24 * $i;
				$noJour = intval(date('Ymd', $wDay));
				if( $noJour <= $this->maxRelevNoJour )
					if( $this->isOpenedPeriod($noJour) )
						$this->loadReservation($noJour);
			}
		}
	}
	
	/**
	 * Validation de la table de pointage des présences
	 * Appelé depuis le script Jquery <tt>server_processing_releve_form.php</tt>
	 */
	function parse_request() {
		if(!isset($_POST['rel'])) return;
		
		$activeCols = array();
		for ($i=0; $i<13; $i++) {
			$j		= Self::NUM_DAYS_LST[$i];
			$wDay = $this->firstTimestamp + 3600 * 24 * $j;
			$noJour = intval(date('Ymd', $wDay));
			$activeCols[$i] = $this->isOpenedDay($noJour);
		}
		
		foreach( $_POST['rel'] as $childId => $codActs ) {
			if( strlen($codActs) >= 13 ) {
				if( intval($codActs[7]) == 1 )	$codActs[6] = '1';   		// Si saisie d'un REPAS, on force un MIDI
				for ($i=0; $i<13; $i++) {
					if( $activeCols[$i] ) {
						$j		= Self::NUM_DAYS_LST[$i];
						$codAct	= Self::CODE_ACT_LST[$i];
						$value  = $codActs[$i];
						
						$wDay = $this->firstTimestamp + 3600 * 24 * $j;
						$noJour = intval(date('Ymd', $wDay));
						$this->deleteReleve($noJour, $childId, $codAct);

						if ($noJour <= $this->maxRelevNoJour) {
							if( is_numeric($value) ) {
								$v = intval($value);
								if( $v > 0 && $v < 6) {
									$optCode = $v - 1;
									if( $optCode < 0 ) $optCode = 0;
									$this->addReleve($noJour, $childId, $codAct, $optCode);
								}
							}
						}
					}
				}
			}
		}
		
		$this->success_msg = "Pointage enregistré !";
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

	private function isOpenedPeriod($noJour) {
		if (!is_numeric($noJour)) return false;
		$ret = false;
		$query = "SELECT `ID` FROM `periode_facturation` WHERE " .
					"(`D_DEB`  <= $noJour) AND "	.
					"(`D_FIN`  >= $noJour) AND "	.
					"(`STATUT` <> 0) "				.					// Différent du statut 0 = Clos
				 "LIMIT 1";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = true;
			$stmt->close();
		}
		return $ret;
	}
	
	private function isReleve($childId, $noJour, $codAct) {
		$ret = false;
		$query = "SELECT * FROM `releve` WHERE " .
					"`ID_ENFANT`="	. $childId	. " AND " .
					"`NUM_JOUR`="	. $noJour	. " AND " .
					"`CODE_ACT`="	. $codAct	. " AND " .
					"`OPT` <> 9 " .
				 "LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = true;
			$stmt->close();
		}
		return $ret;
	}
	
	private function isReservation($childId, $noJour, $codAct) {
		$ret = false;
		$query = "SELECT * FROM `reservation` WHERE `ID_ENFANT`=" . $childId . " AND `NUM_JOUR`=" . $noJour . " AND `CODE_ACT`='" . $codAct . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = true;
			$stmt->close();
		}
		return $ret;
	}
	
	private function getOptCode($childId, $noJour, $codAct) {
		$query = "SELECT `OPT` FROM `releve` WHERE `ID_ENFANT`=" . $childId . " AND `NUM_JOUR`=" . $noJour . " AND `CODE_ACT`='" . $codAct . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt))
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				return $res[0];
		return 0;
	}
	
	private function deleteReleveOfAllDay($noJour) {
		$query = "DELETE FROM `releve` WHERE `NUM_JOUR`=" . $noJour;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function deleteReleve($noJour, $childId, $codAct) {
		$query = "DELETE FROM `releve` WHERE `ID_ENFANT`=$childId AND `NUM_JOUR`=$noJour AND `CODE_ACT`='$codAct'";
//--		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function addReleve($noJour, $childId, $codAct, $opt=0) {
		if (!$this->isValideCodeAct($codAct)) return;
		if (!$this->isValideOptCode($opt)) return;
		
		$familyId	= 0;
		$name		= "";
		$firstname	= "";
		$age		= 0.0;
		$query = "SELECT `ID_FAMILLE`, `NOM`, `PRENOM`, `DATE_NAISS_J`, `DATE_NAISS_M`, `DATE_NAISS_A` " .
				 "FROM `enfant` WHERE `ID`=" . $childId . " LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$familyId	= $res[0];
				$name		= $res[1];
				$firstname	= $res[2];
				$birthD		= $res[3];
				$birthM		= $res[4];
				$birthY		= $res[5];
				$d = mktime(0, 0, 0, $birthM, $birthD, $birthY);
				$age = round((time() - $d) / 3600 / 24 / 365.25, 1);
			}
			$stmt->close();
		}
		
		if( $this->isReservation($childId, $noJour, $codAct) )	$resa = 1;
		else													$resa = 0;
		
		$query = "INSERT INTO `releve` " .
				 "(`ID_ENFANT`, `NUM_JOUR`, `CODE_ACT`, `OPT`, `RESA`, `ID_FAMILLE`, `NOM`, `PRENOM`, `AGE`) " .
				 "VALUES('" . $childId		. "', " .
						"'" . $noJour		. "', " .
						"'" . $codAct 		. "', " .
						"'" . $opt			. "', " .
						"'" . $resa 		. "', " .
						"'" . $familyId  	. "', " .
						"'" . $name  		. "', " .
						"'" . $firstname	. "', " .
						"'" . $age			. "' )";
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
		
	private function processUnfulfilledReservation($noJour) {
		$query = "SELECT `reservation`.`ID_ENFANT`, `reservation`.`CODE_ACT` " .
		         "FROM `reservation` " .
				 "LEFT OUTER JOIN `releve` ON `reservation`.`ID_ENFANT`=`releve`.`ID_ENFANT` AND `reservation`.`NUM_JOUR`=`releve`.`NUM_JOUR` AND `reservation`.`CODE_ACT`=`releve`.`CODE_ACT` " .
				 "WHERE `releve`.`ID_ENFANT` IS NULL AND " .
				       "`reservation`.`NUM_JOUR`=" . $noJour;
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$childId	= $res[0];
				$codAct		= $res[1];
				$this->addReleve($noJour, $childId, $codAct, 9);
			}
			$stmt->close();
		}
	}
	
	private function setOptCode($noJour, $childId, $codAct, $optCode) {
		if (!$this->isValideCodeAct($codAct)) return;
		if (!$this->isValideOptCode($optCode)) return;
		$query = "UPDATE `releve` SET `OPT`='" . $optCode . "' WHERE `ID_ENFANT`=" . $childId . " AND `NUM_JOUR`=" . $noJour . " AND `CODE_ACT`='" . $codAct . "'";
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function setDayAsReleve($noJour) {
		$query = "UPDATE `calendrier` SET `RELEVE`='1' WHERE `NUM_JOUR`=" . $noJour;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function getAllReservation($noJour) {
		$query  = "INSERT `releve` (`ID_ENFANT`, `NUM_JOUR`, `CODE_ACT`) ";
		$query .= "SELECT `ID_ENFANT`, `NUM_JOUR`, `CODE_ACT` ";
		$query .= "FROM `reservation` WHERE `NUM_JOUR`=" . $noJour;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function loadReservation($noJour) {
		$query = "SELECT `ID_ENFANT`, `CODE_ACT` FROM `reservation` WHERE `NUM_JOUR`=$noJour";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$childId	= $res[0];
				$codAct		= $res[1];
				if( !$this->isReleve($childId, $noJour, $codAct) )
					$this->addReleve($noJour, $childId, $codAct);
			}
			$stmt->close();
		}
	}

	private function isValideOptCode($optCode) {
		return ($optCode == 0) ||		// Default
		       ($optCode == 1) ||		// Grauit
			   ($optCode == 2) ||		// Retard 15'
			   ($optCode == 3) ||		// Retard 30'
			   ($optCode == 4);			// Retard 1h
	}
	
	private function isValideCodeAct($codAct) {
		return ($codAct == "MAT") ||	// Matin
		       ($codAct == "SOI") ||	// Soir
			   ($codAct == "LIB") ||	// Mercredi libéré
			   ($codAct == "MID") ||	// Midi (Mercredi)
			   ($codAct == "REP") ||	// Repas (Mercredi)
			   ($codAct == "APM");		// Après-midi (Mercredi)
	}
}
?>