<?php
require_once('../services/Database.php');
require_once('../services/ParameterManager.php');

class FinancialFormControler {
	
	const FIELDS = array("ne", "na",
						 "fi011", "fi012", "fi013",
						 "fi021", "fi022", "fi023",
						 "fi031", "fi032", "fi033",
						 "fi041", "fi042", "fi043",
						 "fi051", "fi052", "fi053",
						 "fi061", "fi062", "fi063",
						 "fi071", "fi072", "fi073",
						 "fi081", "fi082", "fi083",
						 "fi091", "fi092", "fi093",
						 "fi101", "fi102", "fi103",
										   "fi113",
						 "fi121", "fi122", "fi123");
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;						// Database instance
	private $mysqli;					// Database connection
	private $familyControler;			// FamilyControler instance
	
	public $familyId		= 0;		// type: int
	public $name			= "";		// type: String
	public $qf				= "";		// type: String
	public $qf_val			= 0.0;		// type: Float
	
	public $qfYear			= 0;		// type: int
	public $qfStatus		= 0;		// type: int
	public $qfStatusLst		= array();	// type: array  ( year{int} -> status{int} )
	
	public $totalA1			= 0.0;		// type: Float
	public $totalA2			= 0.0;		// type: Float
	public $totalA3			= 0.0;		// type: Float
	
	public $totalB1			= 0.0;		// type: Float
	public $totalB2			= 0.0;		// type: Float
	public $totalB3			= 0.0;		// type: Float
	
	public $ne				= 0;		// type: Integer
	public $na				= 0;		// type: Integer
	
	public $name1			= "";		// type: String
	public $link1			= "";		// type: String
	public $prof1			= "";		// type: String
	public $tpsw1			= 0;		// type: Integer
	
	public $name2			= "";		// type: String
	public $link2			= "";		// type: String
	public $prof2			= "";		// type: String
	public $tpsw2			= 0;		// type: Integer
	
	public $name3			= "";		// type: String
	public $link3			= "";		// type: String
	public $prof3			= "";		// type: String
	public $tpsw3			= 0;		// type: Integer
	
	// 1. SALAIRES
	public $fi011			= 0.0;		// type: Float
	public $fi012			= 0.0;		// type: Float
	public $fi013			= 0.0;		// type: Float
	
	// 2. RESSOURCES NETTES 
	public $fi021			= 0.0;		// type: Float
	public $fi022			= 0.0;		// type: Float
	public $fi023			= 0.0;		// type: Float
	
	// 3. ALLOCATIONS FAMILIALES  
	public $fi031			= 0.0;		// type: Float
	public $fi032			= 0.0;		// type: Float
	public $fi033			= 0.0;		// type: Float
	
	// 4. SUPPLÉMENT FAMILIAL
	public $fi041			= 0.0;		// type: Float
	public $fi042			= 0.0;		// type: Float
	public $fi043			= 0.0;		// type: Float
	
	// 5. ALLOCATIONS ET PENSIONS
	public $fi051			= 0.0;		// type: Float
	public $fi052			= 0.0;		// type: Float
	public $fi053			= 0.0;		// type: Float
	
	// 6. TOUTES AUTRES RESSOURCES
	public $fi061			= 0.0;		// type: Float
	public $fi062			= 0.0;		// type: Float
	public $fi063			= 0.0;		// type: Float
	
	// 7. AVANTAGES EN NATURE
	public $fi071			= 0.0;		// type: Float
	public $fi072			= 0.0;		// type: Float
	public $fi073			= 0.0;		// type: Float
	
	// 8. IMPÔT SUR LES REVENUS
	public $fi081			= 0.0;		// type: Float
	public $fi082			= 0.0;		// type: Float
	public $fi083			= 0.0;		// type: Float
	
	// 9. FRAIS PROFESSIONNELS 
	public $fi091			= 0.0;		// type: Float
	public $fi092			= 0.0;		// type: Float
	public $fi093			= 0.0;		// type: Float
	
	// 10. FRAIS DE GARDE
	public $fi101			= 0.0;		// type: Float
	public $fi102			= 0.0;		// type: Float
	public $fi103			= 0.0;		// type: Float
	
	// 11. FRAIS DE SCOLARITÉ 
	public $fi111			= 0.0;		// type: Float
	public $fi112			= 0.0;		// type: Float
	public $fi113			= 0.0;		// type: Float
	
	// 12. PENSIONS ALIMENTAIRES 
	public $fi121			= 0.0;		// type: Float
	public $fi122			= 0.0;		// type: Float
	public $fi123			= 0.0;		// type: Float
	
	// 13. PIECES JOINTES
	public $favis1			= "";		// type: String
	public $favis2			= "";		// type: String
	public $frib			= "";		// type: String
	
	public $isAdmin			= false;	// type: boolean
	public $submit			= false;	// type: boolean
	public $edit			= false;	// type: boolean
	public $toReload		= false;	// type: boolean
	
	public $qfFamValid		= false;	// type: boolean
	public $qfValidation	= false;	// type: boolean

	public $msg_error		= array();	// type: array
	public $msg_success		= "";		// type: String

	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize($familyControler) {
		$this->familyControler	= $familyControler;
		$this->isAdmin			= $familyControler->isAdmin;
		$this->initializeControler($familyControler->familyId, $familyControler->isAdmin);
	}
	
	public function initializeControler($familyId) {
		$this->familyId			= $familyId;
		
		if(isset($_POST['btnSubmitQFGrid']))											$this->submit	= true;
		if(isset($_POST['qfFamValid']) && is_numeric($_POST['qfFamValid']) )			$this->qfFamValid = (intval($_POST['qfFamValid']) == 1);
		if(isset($_POST['qfValidation']) && is_numeric($_POST['qfValidation']) )		$this->qfValidation = (intval($_POST['qfValidation']) == 1);
		
		// Initialize qfYear and qfStatus
		$this->qfStatus = 0;					// Status 0 = "ouvert à la saisie"
		
		$this->qfYear = ParameterManager::getInstance()->year1;
		if(isset($_GET['y']) && is_numeric($_GET['y']))			$this->qfYear	= intval($_GET['y']);
		if(isset($_POST['y']) && is_numeric($_POST['y']))		$this->qfYear	= intval($_POST['y']);
		
		// Initialize database connection
		$this->db				= Database::getInstance();
		$this->mysqli			= $this->db->getConnection();
	}
	
	
	public function load() {
		// Load family
		if(!$this->submit)
			$this->loadFamilyQF();
		
		if ($this->familyId != 0) {
			$this->loadFamily();
			$this->loadQFStatusList();
		}
		
		// Calcul des sous-totaux et du QF
		$this->_calculateQF();
	}
	
	public function parse_request() {
		if(!$this->submit) return;
		
		// Récupération des champs non numériques + tpsw
		if(isset($_POST['name3']))		$this->name3		= $_POST['name3'];
		if(isset($_POST['link3']))		$this->link3		= $_POST['link3'];
		if(isset($_POST['favis1_']))	$this->favis1		= $_POST['favis1_'];
		if(isset($_POST['favis2_']))	$this->favis2		= $_POST['favis2_'];
		if(isset($_POST['frib_']))		$this->frib			= $_POST['frib_'];
		if(isset($_POST['tpsw1']) && is_numeric($_POST['tpsw1'])) $this->tpsw1	= intval($_POST['tpsw1']);
		if(isset($_POST['tpsw2']) && is_numeric($_POST['tpsw2'])) $this->tpsw2	= intval($_POST['tpsw2']);
		if(isset($_POST['tpsw3']) && is_numeric($_POST['tpsw3'])) $this->tpsw3	= intval($_POST['tpsw3']);
		
		// Récupération des champs numériques
		foreach( Self::FIELDS as $f ) {
			if(isset($_POST[$f])) {
				$this->$f = trim(str_replace(' ', '', $_POST[$f]));
				$this->$f = str_replace(',', '.', $this->$f);
			}
		}
		
		// Contrôle de cohérence
		$error = false;
		foreach( Self::FIELDS as $f ) {
			if(!is_numeric($this->$f)) {
				$this->msg_error[$f] = "Format non valide !";
				$error = true;
			}
		}
		
		// Contrôle des fichiers uploadés
		if( !$this->_checkUploadedFiles("favis1") )	$error = true;
		if( !$this->_checkUploadedFiles("favis2") )	$error = true;
		if( !$this->_checkUploadedFiles("frib") )	$error = true;
		
		if( $error )
			return;
		
		// Conversion des données numériques
		foreach( Self::FIELDS as $f )
			$this->$f = floatval($this->$f);
		
		// Upload des fichiers
		$f = $this->_uploadFile("favis1");
		if( $f != null )					$this->favis1	= $f;
		$f = $this->_uploadFile("favis2");
		if( $f != null )					$this->favis2	= $f;
		$f= $this->_uploadFile("frib");
		if( $f != null )					$this->frib		= $f;
		
		// Calcul des sous-totaux et du QF
		$this->_calculateQF();
		
		// Ecriture en base
		if( $this->isExistingFamilyQF() )
			$this->updateFamilyQF();
		else
			$this->insertFamilyQF();
		
		if( !$this->isAdmin ) {
			if( $this->qfFamValid ) {
				if( !isset($_POST['qfAcceptation']) ) {
					$this->msg_error['qfAcceptation'] = "Merci de bien vouloir cocher la case à cocher sur l'exactitude des informations communiquées.";
					return;
				}
				$this->updateFamilyQFSubmit();
				$this->toReload = true;
				$this->msg_success = "Grille de scolarité soumise à l'administration.";
			} else {
				$this->msg_success = "Grille de calcul mise à jour. QF = '$this->qf'";
			}
		} else {
			if( $this->qfValidation ) {
			} else {
				$this->msg_success = "Grille de calcul mise à jour. QF = '$this->qf'";
			}
		}
		
		// Desactivate edit mode
		$this->edit = false;
		if( $this->familyControler != null ) {
			$this->familyControler->msg_success = $this->msg_success;
			$this->familyControler->edit = false;
			$this->familyControler->add = false;
		}
		
	}
	
	private function _calculateQF() {
		
		// Calcul les totaux A et B
		$this->totalA1 = $this->fi011 +	$this->fi021 + $this->fi031 + $this->fi041 + $this->fi051 + $this->fi061 + $this->fi071;
		$this->totalA2 = $this->fi012 +	$this->fi022 + $this->fi032 + $this->fi042 + $this->fi052 + $this->fi062 + $this->fi072;
		$this->totalA3 = $this->fi013 +	$this->fi023 + $this->fi033 + $this->fi043 + $this->fi053 + $this->fi063 + $this->fi073;

		$this->totalB1 = $this->fi081 +	$this->fi091 + $this->fi101 + $this->fi111 + $this->fi121;
		$this->totalB2 = $this->fi082 +	$this->fi092 + $this->fi102 + $this->fi112 + $this->fi122;
		$this->totalB3 = $this->fi083 +	$this->fi093 + $this->fi103 + $this->fi113 + $this->fi123;
		
		// Calcul le nouveau QF
		if( ( $this->ne + $this->na ) > 0 ) {
			$this->qf_val = ( $this->totalA1 + $this->totalA2 + $this->totalA3 - $this->totalB1 - $this->totalB2 - $this->totalB3 ) / ( $this->ne + $this->na );
			if( $this->qf_val >= ParameterManager::getInstance()->minL )			$this->qf = "L";
			else if( $this->qf_val >= ParameterManager::getInstance()->minK )		$this->qf = "K";
			else if( $this->qf_val >= ParameterManager::getInstance()->minJ )		$this->qf = "J";
			else if( $this->qf_val >= ParameterManager::getInstance()->minI )		$this->qf = "I";
			else if( $this->qf_val >= ParameterManager::getInstance()->minH )		$this->qf = "H";
			else if( $this->qf_val >= ParameterManager::getInstance()->minG )		$this->qf = "G";
			else if( $this->qf_val >= ParameterManager::getInstance()->minF )		$this->qf = "F";
			else if( $this->qf_val >= ParameterManager::getInstance()->minE )		$this->qf = "E";
			else if( $this->qf_val >= ParameterManager::getInstance()->minD )		$this->qf = "D";
			else if( $this->qf_val >= ParameterManager::getInstance()->minC )		$this->qf = "C";
			else if( $this->qf_val >= ParameterManager::getInstance()->minB )		$this->qf = "B";
			else 																	$this->qf = "A";
		}
	}
	
	private function _checkUploadedFiles($filename) {
		$content_dir = 'upload/';
		
		if( !isset($_FILES[$filename]) )	return true;		// Pas de fichier uploadé
		$f = $_FILES[$filename];
		if( !isset($f['tmp_name']) ) {
			$this->msg_error[$filename] = "Nom de fichier introuvable";
			return false;
		}
		$tmp_file = $f['tmp_name'];
		if( !is_uploaded_file($tmp_file) )	return true;		// Pas de fichier uploadé
		
		if( !isset($f['type']) ) {
			$this->msg_error[$filename] = "Type de fichier non défini";
			return false;
		}
		if( !isset($f['size']) ) {
			$this->msg_error[$filename] = "Taille de fichier non définie";
			return false;
		}
		$size_file = $f['size'];
		if( !is_numeric($size_file) || ($size_file > (2*1024*1024)) ) {
			$this->msg_error[$filename] = "Taille de fichier limité à 2 Mo";
			return false;
		}
		
		// on vérifie maintenant l'extension
		$type_file = $f['type'];
		if( !strstr($type_file, 'jpg')	&&
		    !strstr($type_file, 'jpeg')	&&
			!strstr($type_file, 'png')	&&
			!strstr($type_file, 'pdf') ) {
				$this->msg_error[$filename] = "Type de fichier non valide ! Formats acceptés : JPG, JPEG, PNG, PDF";
				return false;
		}
		return true;
	}
	
	private function _uploadFile($filename) {
		$content_dir = '../upload/';
		if( !isset($_FILES[$filename]) )	return null;		// Pas de fichier uploadé
		$f = $_FILES[$filename];
		if( !isset($f) ) return null;
		if( !isset($f['tmp_name']) ) return null;
		if( !isset($f['name']) ) return null;
		$tmp_file = $f['tmp_name'];
		if( !is_uploaded_file($tmp_file) ) return null;
		
		$ext = pathinfo($f['name'], PATHINFO_EXTENSION);
		$name_file = strtolower(uniqid() . "." . $ext);
		move_uploaded_file($tmp_file, $content_dir . $name_file);
		$this->_saveFile($name_file);
		return $name_file;
	}
	
	private function _saveFile($filename) {
		$exist = false;
		$today = intval(date('Ymd', time()));
		$query = "SELECT * " .
				 "FROM files_upload " .
				 "WHERE FILENAME='" . $filename . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$exist = true;
			$stmt->close();
		}
		if( $exist ) {
			$query = "UPDATE files_upload SET " .
					 "OWNER_ID="		. $this->familyId	.	", " .
					 "CREATION_DATE="	. $today			.	" " .
					 "WHERE FILENAME='"	. $filename			.	"'";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
			
		} else {
			$query = "INSERT INTO files_upload(FILENAME, OWNER_ID, CREATION_DATE, TYPE) VALUES(" .
						"'"	. 	$filename			. "', "	.
								$this->familyId		. ", " .
								$today				. ", '')";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadFamily() {
		$query = "SELECT `NOM_FAMILLE`, `QF`, " .
				 "`NOM1`, `PRENOM1`, `PROF1`, " . 
				 "`NOM2`, `PRENOM2`, `PROF2` " .
				 "FROM `famille` " .
				 "WHERE `ID`=" . $this->familyId . " LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->name			= strtoupper(DBUtils::html($res[0]));
				$this->qf			= strtoupper($res[1]);
				
				$this->name1		= strtoupper(DBUtils::html($res[2]) . " " . DBUtils::html($res[3]));
				$this->prof1		= strtoupper(DBUtils::html($res[4]));
				
				$this->name2		= strtoupper(DBUtils::html($res[5]) . " " . DBUtils::html($res[6]));
				$this->prof2		= strtoupper(DBUtils::html($res[7]));
			}
			$stmt->close();
		}
	}

	private function loadQFStatusList() {
		$query = "SELECT `ANNEE`, `STATUT` " .
				 "FROM `famille_qf` " .
				 "WHERE `ID_FAMILLE`=" . $this->familyId . " " .
				 "ORDER BY `ANNEE` DESC";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		$this->qfStatusLst = array();
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM))
				$this->qfStatusLst[intval($res[0])]	=	intval($res[1]);
			$stmt->close();
		}
	}
	
	private function loadFamilyQF() {
		$query = "SELECT " .
				 "`NE`, `NA`, " .
				 "`NAME1`, `NAME2`, `NAME3`, `LINK1`, `LINK2`, `LINK3`, `PROF1`, `PROF2`, `PROF3`, `TPSW1`, `TPSW2`, `TPSW3`, "		.
				 "`FI011`, `FI012`, `FI013`, `FI021`, `FI022`, `FI023`, `FI031`, `FI032`, `FI033`, `FI041`, `FI042`, `FI043`, "		.
				 "`FI051`, `FI052`, `FI053`, `FI061`, `FI062`, `FI063`, `FI071`, `FI072`, `FI073`, `FI081`, `FI082`, `FI083`, "		.
				 "`FI091`, `FI092`, `FI093`, `FI101`, `FI102`, `FI103`, `FI111`, `FI112`, `FI113`, `FI121`, `FI122`, `FI123`,  "	.
				 "`FILE_AVIS_IMP_1`, `FILE_AVIS_IMP_2`, `FILE_RIB`, "												.
				 "`STATUT` " .
				 "FROM `famille_qf` " .
				 "WHERE `ID_FAMILLE`=" . $this->familyId . " AND `ANNEE`=" . $this->qfYear . " " .
				 "LIMIT 1";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->ne			= strtoupper($res[0]);
				$this->na			= strtoupper($res[1]);
				
				$this->name1		= strtoupper($res[2]);
				$this->name2		= strtoupper($res[3]);
				$this->name2		= strtoupper($res[4]);
				
				$this->link1		= strtoupper($res[5]);
				$this->link2		= strtoupper($res[6]);
				$this->link3		= strtoupper($res[7]);
				
				$this->prof1		= strtoupper($res[8]);
				$this->prof2		= strtoupper($res[9]);
				$this->prof3		= strtoupper($res[10]);
				
				$this->tpsw1		= intval($res[11]);
				$this->tpsw2		= intval($res[12]);
				$this->tpsw3		= intval($res[13]);
				
				$this->fi011		= floatval($res[14]);
				$this->fi012		= floatval($res[15]);
				$this->fi013		= floatval($res[16]);
				
				$this->fi021		= floatval($res[17]);
				$this->fi022		= floatval($res[18]);
				$this->fi023		= floatval($res[19]);
				
				$this->fi031		= floatval($res[20]);
				$this->fi032		= floatval($res[21]);
				$this->fi033		= floatval($res[22]);
				
				$this->fi041		= floatval($res[23]);
				$this->fi042		= floatval($res[24]);
				$this->fi043		= floatval($res[25]);
				
				$this->fi051		= floatval($res[26]);
				$this->fi052		= floatval($res[27]);
				$this->fi053		= floatval($res[28]);
				
				$this->fi061		= floatval($res[29]);
				$this->fi062		= floatval($res[30]);
				$this->fi063		= floatval($res[31]);
				
				$this->fi071		= floatval($res[32]);
				$this->fi072		= floatval($res[33]);
				$this->fi073		= floatval($res[34]);
				
				$this->fi081		= floatval($res[35]);
				$this->fi082		= floatval($res[36]);
				$this->fi083		= floatval($res[37]);
				
				$this->fi091		= floatval($res[38]);
				$this->fi092		= floatval($res[39]);
				$this->fi093		= floatval($res[40]);
				
				$this->fi101		= floatval($res[41]);
				$this->fi102		= floatval($res[42]);
				$this->fi103		= floatval($res[43]);
				
				$this->fi111		= floatval($res[44]);
				$this->fi112		= floatval($res[45]);
				$this->fi113		= floatval($res[46]);
				
				$this->fi121		= floatval($res[47]);
				$this->fi122		= floatval($res[48]);
				$this->fi123		= floatval($res[49]);
				
				$this->favis1		= $res[50];
				$this->favis2		= $res[51];
				$this->frib			= $res[52];
				
				$this->qfStatus		= intval($res[53]);
			}
			$stmt->close();
		}
	}
	
	private function isExistingFamilyQF() {
		$ret = false;
		$query = "SELECT `ID_FAMILLE` " .
				 "FROM `famille_qf`   " .
				 "WHERE `ID_FAMILLE`=" . $this->familyId . " AND `ANNEE`=" . $this->qfYear . " " .
				 " LIMIT 1";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt))
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = true;
		return $ret;
	}
	
	private function insertFamilyQF() {
		$query = "INSERT INTO `famille_qf`(" 		.
						"`ID_FAMILLE`, `ANNEE`, `STATUT`, `NE`, `NA`, " 			.
						"`NAME1`, `NAME2`, `NAME3`, `LINK1`, `LINK2`, `LINK3`, `PROF1`, `PROF2`, `PROF3`, `TPSW1`, `TPSW2`, `TPSW3`, " .
						"`FI011`, `FI012`, `FI013`, `FI021`, `FI022`, `FI023`, `FI031`, `FI032`, `FI033`, `FI041`, `FI042`, `FI043`, " .
						"`FI051`, `FI052`, `FI053`, `FI061`, `FI062`, `FI063`, `FI071`, `FI072`, `FI073`, `FI081`, `FI082`, `FI083`, " .
						"`FI091`, `FI092`, `FI093`, `FI101`, `FI102`, `FI103`, `FI111`, `FI112`, `FI113`, `FI121`, `FI122`, `FI123`, " .
						"`TOT_A`, `TOT_B`, `QF`, `FILE_AVIS_IMP_1`, `FILE_AVIS_IMP_2`, `FILE_RIB`) "	.
				 "VALUES("						.
								$this->familyId								.	", " .
								
								number_format($this->qfYear,0,".", "")		.	", " .
								number_format($this->qfStatus,0,".", "")	.	", " .
								
								number_format($this->ne,0,".", "")			.	", " .
								number_format($this->na,0,".", "")			.	", " .
				 
						"'"	.	$this->name1	.	"', " .
						"'"	.	$this->name2	.	"', " .
						"'"	.	$this->name3	.	"', " .
				 
						"'"	.	$this->link1	.	"', " .
						"'"	.	$this->link2	.	"', " .
						"'"	.	$this->link3	.	"', " .
				 
						"'"	.	$this->prof1	.	"', " .
						"'"	.	$this->prof2	.	"', " .
						"'"	.	$this->prof3	.	"', " .
				 
								$this->tpsw1	.	", " .
								$this->tpsw2	.	", " .
								$this->tpsw3	.	", " .
				 
								number_format($this->fi011,0,".", "")	.	", " .
								number_format($this->fi012,0,".", "")	.	", " .
								number_format($this->fi013,0,".", "")	.	", " .
				 
								number_format($this->fi021,0,".", "")	.	", " .
								number_format($this->fi022,0,".", "")	.	", " .
								number_format($this->fi023,0,".", "")	.	", " .
				 
								number_format($this->fi031,0,".", "")	.	", " .
								number_format($this->fi032,0,".", "")	.	", " .
								number_format($this->fi033,0,".", "")	.	", " .
				 
								number_format($this->fi041,0,".", "")	.	", " .
								number_format($this->fi042,0,".", "")	.	", " .
								number_format($this->fi043,0,".", "")	.	", " .

								number_format($this->fi051,0,".", "")	.	", " .
								number_format($this->fi052,0,".", "")	.	", " .
								number_format($this->fi053,0,".", "")	.	", " .
				 
								number_format($this->fi061,0,".", "")	.	", " .
								number_format($this->fi062,0,".", "")	.	", " .
								number_format($this->fi063,0,".", "")	.	", " .
				 
								number_format($this->fi071,0,".", "")	.	", " .
								number_format($this->fi072,0,".", "")	.	", " .
								number_format($this->fi073,0,".", "")	.	", " .
				 
								number_format($this->fi081,0,".", "")	.	", " .
								number_format($this->fi082,0,".", "")	.	", " .
								number_format($this->fi083,0,".", "")	.	", " .
				 
								number_format($this->fi091,0,".", "")	.	", " .
								number_format($this->fi092,0,".", "")	.	", " .
								number_format($this->fi093,0,".", "")	.	", " .
				 
								number_format($this->fi101,0,".", "")	.	", " .
								number_format($this->fi102,0,".", "")	.	", " .
								number_format($this->fi103,0,".", "")	.	", " .
				 
								number_format($this->fi111,0,".", "")	.	", " .
								number_format($this->fi112,0,".", "")	.	", " .
								number_format($this->fi113,0,".", "")	.	", " .
				 
								number_format($this->fi121,0,".", "")	.	", " .
								number_format($this->fi122,0,".", "")	.	", " .
								number_format($this->fi123,0,".", "")	.	", " .
				 
								number_format($this->totalA1 + $this->totalA2 + $this->totalA3,0,".", "")	.	", " .
								number_format($this->totalB1 + $this->totalB2 + $this->totalB3,0,".", "")	.	", " .
				 
						"'"	.	$this->qf			.	"', " .
						
						"'"	.	$this->favis1	.	"', " .
						"'"	.	$this->favis2	.	"', " .
						"'"	.	$this->frib		.	"')";
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateFamilyQF() {
		$query = "UPDATE `famille_qf` SET " 		.
				 
				 "`STATUT`="	.	number_format($this->qfStatus,0,".", "")	.	", " .
				 
				 "`NE`="		.	number_format($this->ne,0,".", "")	.	", " .
				 "`NA`="		.	number_format($this->na,0,".", "")	.	", " .
				 
				 "`NAME1`='"	.	$this->name1	.	"', " .
				 "`NAME2`='"	.	$this->name2	.	"', " .
				 "`NAME3`='"	.	$this->name3	.	"', " .
				 
				 "`LINK1`='"	.	$this->link1	.	"', " .
				 "`LINK2`='"	.	$this->link2	.	"', " .
				 "`LINK3`='"	.	$this->link3	.	"', " .
				 
				 "`PROF1`='"	.	$this->prof1	.	"', " .
				 "`PROF2`='"	.	$this->prof2	.	"', " .
				 "`PROF3`='"	.	$this->prof3	.	"', " .
				 
				 "`TPSW1`="	.	$this->tpsw1	.	", " .
				 "`TPSW2`="	.	$this->tpsw2	.	", " .
				 "`TPSW3`="	.	$this->tpsw3	.	", " .
				 
				 "`FI011`="	.	number_format($this->fi011,0,".", "")	.	", " .
				 "`FI012`="	.	number_format($this->fi012,0,".", "")	.	", " .
				 "`FI013`="	.	number_format($this->fi013,0,".", "")	.	", " .
				 
				 "`FI021`="	.	number_format($this->fi021,0,".", "")	.	", " .
				 "`FI022`="	.	number_format($this->fi022,0,".", "")	.	", " .
				 "`FI023`="	.	number_format($this->fi023,0,".", "")	.	", " .
				 
				 "`FI031`="	.	number_format($this->fi031,0,".", "")	.	", " .
				 "`FI032`="	.	number_format($this->fi032,0,".", "")	.	", " .
				 "`FI033`="	.	number_format($this->fi033,0,".", "")	.	", " .
				 
				 "`FI041`="	.	number_format($this->fi041,0,".", "")	.	", " .
				 "`FI042`="	.	number_format($this->fi042,0,".", "")	.	", " .
				 "`FI043`="	.	number_format($this->fi043,0,".", "")	.	", " .

				 "`FI051`="	.	number_format($this->fi051,0,".", "")	.	", " .
				 "`FI052`="	.	number_format($this->fi052,0,".", "")	.	", " .
				 "`FI053`="	.	number_format($this->fi053,0,".", "")	.	", " .
				 
				 "`FI061`="	.	number_format($this->fi061,0,".", "")	.	", " .
				 "`FI062`="	.	number_format($this->fi062,0,".", "")	.	", " .
				 "`FI063`="	.	number_format($this->fi063,0,".", "")	.	", " .
				 
				 "`FI071`="	.	number_format($this->fi071,0,".", "")	.	", " .
				 "`FI072`="	.	number_format($this->fi072,0,".", "")	.	", " .
				 "`FI073`="	.	number_format($this->fi073,0,".", "")	.	", " .
				 
				 "`FI081`="	.	number_format($this->fi081,0,".", "")	.	", " .
				 "`FI082`="	.	number_format($this->fi082,0,".", "")	.	", " .
				 "`FI083`="	.	number_format($this->fi083,0,".", "")	.	", " .
				 
				 "`FI091`="	.	number_format($this->fi091,0,".", "")	.	", " .
				 "`FI092`="	.	number_format($this->fi092,0,".", "")	.	", " .
				 "`FI093`="	.	number_format($this->fi093,0,".", "")	.	", " .
				 
				 "`FI101`="	.	number_format($this->fi101,0,".", "")	.	", " .
				 "`FI102`="	.	number_format($this->fi102,0,".", "")	.	", " .
				 "`FI103`="	.	number_format($this->fi103,0,".", "")	.	", " .
				 
				 "`FI111`="	.	number_format($this->fi111,0,".", "")	.	", " .
				 "`FI112`="	.	number_format($this->fi112,0,".", "")	.	", " .
				 "`FI113`="	.	number_format($this->fi113,0,".", "")	.	", " .
				 
				 "`FI121`="	.	number_format($this->fi121,0,".", "")	.	", " .
				 "`FI122`="	.	number_format($this->fi122,0,".", "")	.	", " .
				 "`FI123`="	.	number_format($this->fi123,0,".", "")	.	", " .
				 
				 "`TOT_A`="	.	number_format($this->totalA1 + $this->totalA2 + $this->totalA3,0,".", "")	.	", " .
				 "`TOT_B`="	.	number_format($this->totalB1 + $this->totalB2 + $this->totalB3,0,".", "")	.	", " .
				 
				 "`QF`='"		.	$this->qf		.	"', " .
				 
				 "`FILE_AVIS_IMP_1`='"	.	$this->favis1	.	"', " .
				 "`FILE_AVIS_IMP_2`='"	.	$this->favis2	.	"', " .
				 "`FILE_RIB`='"			.	$this->frib		.	"' " .

				 "WHERE `ID_FAMILLE`=" . $this->familyId . " AND `ANNEE`=" . $this->qfYear;
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateFamilyQFSubmit() {
		$this->qfStatus = 0;					
		$query = "UPDATE `famille_qf` SET `STATUT`=1 " .										// Status 1 = "Attente de validation"
				 "WHERE `ID_FAMILLE`=" . $this->familyId . " AND `ANNEE`=" . $this->qfYear;
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
}
?>