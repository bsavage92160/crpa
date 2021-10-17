<?php
require_once('../services/Database.php');
require_once('../services/RegistrationManager.php');
require_once('../services/InvoiceManager.php');
require_once('../services/FinancialFormControler.php');

class FamilyControler {
	
	/**************************************************************************
	 * Constantes
	 **************************************************************************/
	const CLASS_LIST = array("ps", "ms", "gs", "cp", "ce1", "ce2", "cm1", "cm2");
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	public $familyId		= 0;		// type: int
	public $name			= "";		// type: String
	public $qf				= "";		// type: String
	public $active			= false;	// type: Boolean
	public $balance			= 0.0;		// type: Float
	
	public $gender1			= "M";		// type: String
	public $name1			= "";		// type: String
	public $firstname1		= "";		// type: String
	public $email1			= "";		// type: String
	public $link1			= "P";		// type: String
	public $prof1			= "";		// type: String
	public $phone11			= 0;		// type: int
	public $phone12			= 0;		// type: int
	public $address1		= "";		// type: String
	public $cp1				= "";		// type: String
	public $city1			= "";		// type: String
	
	public $only1LegalRp	= false;	// type: boolean
	public $gender2			= "MME";	// type: String
	public $name2			= "";		// type: String
	public $firstname2		= "";		// type: String
	public $email2			= "";		// type: String
	public $link2			= "M";		// type: String
	public $prof2			= "";		// type: String
	public $phone21			= 0;		// type: int
	public $phone22			= 0;		// type: int
	public $address2		= "";		// type: String
	public $cp2				= "";		// type: String
	public $city2			= "";		// type: String
	
	public $children		= array();
	
	public $pseudo			= "";		// type: String
	public $activated		= false;	// type: boolean
	public $validmail1		= false;	// type: boolean
	public $validmail2		= false;	// type: boolean
	
	public $qfgrid			= null;		// type: FinancialFormControler
	
	public $isAdmin			= false;	// type: boolean
	public $isGeneral		= false;	// type: boolean
	public $isFinance		= false;	// type: boolean
	public $isQFGrid		= false;	// type: boolean
	public $isAccess		= false;	// type: boolean
	public $submit			= false;	// type: boolean
	public $edit			= false;	// type: boolean
	public $add				= false;	// type: boolean
	public $msg_error		= "";		// type: String
	public $msg_success		= "";		// type: String
	
	public $back			= "";		// type: String
	public $backParam		= "";		// type: String
	public $backUrl			= "";		// type: String
	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize($isAdmin = false) {
		
		if(isset($_GET['fa']) && is_numeric($_GET['fa']))				$this->familyId		= intval($_GET['fa']);
		if(isset($_POST['fa']) && is_numeric($_POST['fa']))				$this->familyId		= intval($_POST['fa']);
		
		if(isset($_GET['edit']) && is_numeric($_GET['edit']))			$this->edit			= (intval($_GET['edit']) == 1);
		if(isset($_POST['edit']) && is_numeric($_POST['edit']))			$this->edit			= (intval($_POST['edit']) == 1);
		if(isset($_GET['add']) && is_numeric($_GET['add'])) 			$this->add			= (intval($_GET['add']) == 1);
		if(isset($_POST['add']) && is_numeric($_POST['add'])) 			$this->add			= (intval($_POST['add']) == 1);
		
		if(isset($_POST['bk']))											$this->back  		= $_POST['bk'];
		if(isset($_POST['bkparam']))									$this->backParam  	= $_POST['bkparam'];
		if($this->back == "lstch")										$this->backUrl		= "../q/lchl";
		else if($this->back == "lstfa")									$this->backUrl		= "../q/lfam";
		
		if(isset($_POST['general'])	&& is_numeric($_POST['general']) )	$this->isGeneral	= (intval($_POST['general']) == 1);
		if(isset($_POST['finance'])	&& is_numeric($_POST['finance']) )	$this->isFinance	= (intval($_POST['finance']) == 1);
		if(isset($_POST['access'])	&& is_numeric($_POST['access']) )	$this->isAccess		= (intval($_POST['access']) == 1);
		if(isset($_POST['qfgrid'])	&& is_numeric($_POST['qfgrid']) )	$this->isQFGrid		= (intval($_POST['qfgrid']) == 1);
		
		if( !$this->isGeneral && !$this->isFinance && !$this->isQFGrid && !$this->isAccess) {
			if( $isAdmin )
				$this->isFinance = true;
			else
				$this->isGeneral = true;
		}
		if( $this->add ) $this->isGeneral = true;
		$this->submit		= isset($_POST['submit']);
		
		// Initialize QFGrid
		$this->qfgrid			= new FinancialFormControler();
		$this->qfgrid->initialize($this);
		
		// Initialize database connection
		$this->db				= Database::getInstance();
		$this->mysqli			= $this->db->getConnection();
		
		// Set if admin profile
		$this->isAdmin			= $isAdmin;
	}
	
	public function setFamilyId($familyId) {
		$this->familyId	= $familyId;
		$this->qfgrid->familyId = $familyId;
	}
	
	
	public function load() {
		
		// Load family
		if ($this->familyId != 0) {
			if( !$this->submit || ($this->submit && $this->isFinance) ) {
				$this->loadFamily();
				$this->loadChildren();
			}
			if( !$this->submit || ($this->submit && $this->isGeneral) ) {
				$this->loadFinance();
			}
			$this->balance = InvoiceManager::getInstance()->getFamilyBalance($this->familyId);
		}
		
		// Load QFGrid
		$this->qfgrid->load();
	}
	
	public function parse_request() {
		if(isset($_POST['cancel'])) {		
			// Desactivate edit mode
			$this->add = false;
			$this->edit = false;
			return;
		}
		if( $this->isFinance )
			return $this->parse_request_finance();
		if(isset($_POST['btnReactivate']))
			return $this->parse_request_reactivate();
		if(isset($_POST['btnSubmitAccess']))
			return $this->parse_request_access();
		if(isset($_POST['btnSubmitQFGrid']))
			return $this->qfgrid->parse_request();
		
		if(!isset($_POST['btnSubmitUpdate']) && !isset($_POST['btnSubmitCreate'])) return;
		
		if(isset($_POST['faname']))		$this->name				= $_POST['faname'];
		if(isset($_POST['pseudo']))		$this->pseudo			= $_POST['pseudo'];
		
		if(isset($_POST['gender1']))	$this->gender1			= $_POST['gender1'];
		if(isset($_POST['name1']))		$this->name1			= $_POST['name1'];
		if(isset($_POST['firstname1']))	$this->firstname1		= $_POST['firstname1'];
		if(isset($_POST['email1']))		$this->email1			= $_POST['email1'];
		if(isset($_POST['link1']))		$this->link1			= $_POST['link1'];
		if(isset($_POST['prof1']))		$this->prof1			= $_POST['prof1'];
		if(isset($_POST['phone11']))	$this->phone11			= $_POST['phone11'];
		if(isset($_POST['phone12']))	$this->phone12			= $_POST['phone12'];
		if(isset($_POST['address1']))	$this->address1			= $_POST['address1'];
		if(isset($_POST['cp1']))		$this->cp1				= $_POST['cp1'];
		if(isset($_POST['city1']))		$this->city1			= $_POST['city1'];
		
		$this->only1LegalRp = isset($_POST['only1LegalRepresentative']);
		
		if(isset($_POST['gender2']))	$this->gender2			= $_POST['gender2'];
		if(isset($_POST['name2']))		$this->name2			= $_POST['name2'];
		if(isset($_POST['firstname2']))	$this->firstname2		= $_POST['firstname2'];
		if(isset($_POST['email2']))		$this->email2			= $_POST['email2'];
		if(isset($_POST['link2']))		$this->link2			= $_POST['link2'];
		if(isset($_POST['prof2']))		$this->prof2			= $_POST['prof2'];
		if(isset($_POST['phone21']))	$this->phone21			= $_POST['phone21'];
		if(isset($_POST['phone22']))	$this->phone22			= $_POST['phone22'];
		if(isset($_POST['address2']))	$this->address2			= $_POST['address2'];
		if(isset($_POST['cp2']))		$this->cp2				= $_POST['cp2'];
		if(isset($_POST['city2']))		$this->city2			= $_POST['city2'];
		
		// Parse children parameters
		if(isset($_POST['child'])) {
			unset($this->children);
			$this->children = array();
			
			// Update children
			if(isset($_POST['child'][0])) {
				foreach( $_POST['child'][0] as $childId => $child ) {
					$chId			= $child['id'];
					$chName			= strtolower($child['name']);
					$chFirstname	= strtolower($child['firstname']);
					$chGender		= $child['gender'];
					$chBirth		= $child['bth'];
					$lvl			= strtolower($child['lev']);
					$cls			= strtolower($child['cl']);
					$active			= isset($child['active']);
					
					$nChild = array();
					$nChild['id']				= $chId;
					$nChild['name']				= $chName;
					$nChild['firstname']		= $chFirstname;
					$nChild['gender']			= $chGender;
					$nChild['birthD']			= substr($chBirth, -2);
					$nChild['birthM']			= substr($chBirth, 4, 2);
					$nChild['birthY']			= substr($chBirth, 0, 4);
					$nChild['level']			= $lvl;
					$nChild['class']			= $cls;
					$nChild['active']			= $active;
					array_push($this->children, $nChild);
				}
			}
			
			// Insert new children
			if( $this->isAdmin ) {
				if(isset($_POST['child'][1])) {
					foreach( $_POST['child'][1] as $child ) {
						$chName			= strtolower($child['name']);
						$chFirstname	= strtolower($child['firstname']);
						if( (trim($chName) <> "") && (trim($chFirstname) <> "") ) {					
							$chGender		= $child['gender'];
							$chBirth		= $child['bth'];
							$lvl			= strtolower($child['lev']);
							$cls			= strtolower($child['cl']);
							$active			= isset($child['active']);
							
							$nChild = array();
							$nChild['name']				= substr($chName, 0, 20);
							$nChild['firstname']		= substr($chFirstname, 0, 20);
							$nChild['gender']			= $chGender;
							$nChild['birthD']			= substr($chBirth, -2);
							$nChild['birthM']			= substr($chBirth, 4, 2);
							$nChild['birthY']			= substr($chBirth, 0, 4);
							$nChild['level']			= $lvl;
							$nChild['class']			= $cls;
							$nChild['active']			= $active;
							array_push($this->children, $nChild);
						}
					}
				}
			}			
		}
		
		// Vérifie la validité des champs
		if( $this->add ) {
			if( strlen(trim($this->pseudo)) == 0 ) {
				$this->msg_error = "Pseudo '$this->pseudo' non correct !";
				return;
			}
			if( $this->checkPseudoExisting($this->pseudo) ) {
				$this->msg_error = "Pseudo '$this->pseudo' déjà existant !";
				return;
			}
			if( strlen(trim($this->name)) == 0 ) {
				$this->msg_error = "Nom de famille '$this->name' non correct !";
				return;
			}
			if( $this->checkFamilyExisting($this->name) ) {
				$this->msg_error = "Nom de famille '$this->name' déjà existant !";
				return;
			}
		}
		if( $this->gender1 != "M" && $this->gender1 != "MME" ) {
			$this->msg_error = "Genre '$this->gender1' non valide !";
			return;
		}
		$this->name1		= substr($this->name1, 0, 20);
		if( strlen(trim($this->name1)) == 0 ) {
			$this->msg_error = "Nom du représentant légal 1 incorrect !";
			return;
		}
		$this->firstname1	= substr($this->firstname1, 0, 20);
		if( strlen(trim($this->firstname1)) == 0 ) {
			$this->msg_error = "Prénom du représentant légal 1 incorrect !";
			return;
		}
		$this->email1		= substr($this->email1, 0, 50);
		if( !filter_var($this->email1, FILTER_VALIDATE_EMAIL) ) {
			$this->msg_error = "Adresse mail '$this->email1' non valide !";
			return;
		}
		if( $this->link1 != "P" && $this->link1 != "M" ) {
			$this->msg_error = "Lien de parenté '$this->link1' non valide !";
			return;
		}
		$this->prof1		= substr($this->prof1, 0, 50);
		$this->phone11		= str_replace(' ', '', $this->phone11);
		if( !$this->add && (!is_numeric($this->phone11) || ($this->phone11 < 100000000) || ($this->phone11 > 999999999)) ) {
			$this->msg_error = "Téléphone '$this->phone11' non valide !";
			return;
		}
		$this->phone11		= substr('0000000000' . $this->phone11, -10);
		$this->phone12		= str_replace(' ', '', $this->phone12);
		if( !$this->add && (!is_numeric($this->phone12) || ($this->phone12 < 100000000) || ($this->phone12 > 999999999)) ) {
			$this->msg_error = "Téléphone '$this->phone12' non valide !";
			return;
		}
		$this->phone12		= substr('0000000000' . $this->phone12, -10);
		$this->address1		= substr($this->address1, 0, 128);
		$this->city1		= substr($this->city1, 0, 20);
		$this->cp1			= str_replace(' ', '', $this->cp1);
		if( !$this->add && (!is_numeric($this->cp1) || ($this->cp1 < 0) || ($this->cp1 > 99999)) ) {
			$this->msg_error = "Code postal '$this->cp1' non valide !";
			return;
		}
		
		// Vérifie la validité des champs
		if( !$this->only1LegalRp ) {
			if( $this->gender2 != "M" && $this->gender2 != "MME" ) {
				$this->msg_error = "Genre '$this->gender2' non valide !";
				return;
			}
			$this->name2		= substr($this->name2, 0, 20);
			if( strlen(trim($this->name2)) == 0 ) {
				$this->msg_error = "Nom du représentant légal 2 incorrect !";
				return;
			}
			$this->firstname2	= substr($this->firstname2, 0, 20);
			if( strlen(trim($this->firstname2)) == 0 ) {
				$this->msg_error = "Prénom du représentant légal 2 incorrect !";
				return;
			}
			$this->email2		= substr($this->email2, 0, 50);
			if( !filter_var($this->email2, FILTER_VALIDATE_EMAIL) ) {
				$this->msg_error = "Adresse mail '$this->email2' non valide !";
				return;
			}
			if( $this->link2 != "P" && $this->link2 != "M" ) {
				$this->msg_error = "Lien de parenté '$this->link2' non valide !";
				return;
			}
			$this->prof2		= substr($this->prof2, 0, 50);
			$this->phone21		= str_replace(' ', '', $this->phone21);
			if( !$this->add && (!is_numeric($this->phone21) || ($this->phone21 < 100000000) || ($this->phone21 > 999999999)) ) {
				$this->msg_error = "Téléphone '$this->phone21' non valide !";
				return;
			}
			$this->phone21		= substr('0000000000' . $this->phone21, -10);
			$this->phone22		= str_replace(' ', '', $this->phone22);
			if( !$this->add && (!is_numeric($this->phone22) || ($this->phone22 < 100000000) || ($this->phone22 > 999999999)) ) {
				$this->msg_error = "Téléphone '$this->phone22' non valide !";
				return;
			}
			$this->phone22		= substr('0000000000' . $this->phone22, -10);
			$this->address2		= substr($this->address2, 0, 128);
			$this->city2		= substr($this->city2, 0, 20);
			$this->cp2			= str_replace(' ', '', $this->cp2);
			if( !$this->add && (!is_numeric($this->cp2) || ($this->cp2 < 0) || ($this->cp2 > 99999)) ) {
				$this->msg_error = "Code postal '$this->cp2' non valide !";
				return;
			}
		}
		
		// // Vérifie la validité des champs de children parameters
		foreach( $this->children as $child ) {
			$chName 		= $child['name'];
			$chFirstname	= $child['firstname'];
			$chGender		= $child['gender'];
			$chBirthD		= $child['birthD'];
			$chBirthM		= $child['birthM'];
			$chBirthY		= $child['birthY'];
			$lvl			= $child['level'];
			$cls			= $child['class'];
			
			if( isset($child['id']) ) {
				$childId = intval($child['id']);
				if( !is_numeric($childId) ) {
					$this->msg_error = "Id '$childId' non valide !";
					return;
				}
			}
			if( !is_numeric($chBirthD) || ($chBirthD < 0) || ($chBirthD > 31) ) {
				$this->msg_error = "Jour '$chBirthD' non valide !";
				return;
			}
			if( !is_numeric($chBirthM) || ($chBirthM < 0) || ($chBirthM > 12) ) {
				$this->msg_error = "Mois '$chBirthM' non valide !";
				return;
			}
			if( $chGender != "G" && $chGender != "F" ) {
				$this->msg_error = "Genre '$chGender' non valide !";
				return;
			}
			if( !is_numeric($chBirthY) || ($chBirthY < 2010) || ($chBirthY > 2050) ) {
				$this->msg_error = "Année '$chBirthY' non valide !";
				return;
			}
			$d = mktime(0, 0, 0, $chBirthM, $chBirthD, $chBirthY);
			if( $d == false ) {
				$this->msg_error = "Date  '" . $chBirthD . "/". $chBirthM . "/" . $chBirthY . "' non valide !";
				return;
			}
			if( !in_array(strtolower($lvl), Self::CLASS_LIST) ) {
				$this->msg_error = "Niveau '$lvl' non valide !";
				return;
			}
		}
		
		// Mise à jour du record
		if( $this->add && $this->isAdmin) {
			$this->insertFamily();
			$this->insertFamilyUser();
			RegistrationManager::sendRegistrationRequest($this->pseudo, $this->gender1, $this->name1, $this->firstname1, $this->email1);
			RegistrationManager::sendRegistrationRequest($this->pseudo, $this->gender2, $this->name2, $this->firstname2, $this->email2);
		} else {
			$this->updateFamily();
		}
		
		foreach( $this->children as $child ) {
			$chName 		= $child['name'];
			$chFirstname	= $child['firstname'];
			$chGender		= $child['gender'];
			$chBirthD		= $child['birthD'];
			$chBirthM		= $child['birthM'];
			$chBirthY		= $child['birthY'];
			$lvl			= $child['level'];
			$cls			= $child['class'];
			$active			= $child['active'];
			
			if( isset($child['id']) ) {
				$childId = intval($child['id']);
					if( (trim($chName) == "") || (trim($chFirstname) == "") ) {
						if( $this->isAdmin )
							$this->deleteChild($childId);
					} else {
						$this->updateChild($childId,
										   $chName,
										   $chFirstname,
										   $chGender,
										   $chBirthD,
										   $chBirthM,
										   $chBirthY,
										   $lvl,
										   $cls,
										   $active || !$this->isAdmin);
					}
				
			} else if( $this->isAdmin ) {
				$this->insertChild($chName,
								   $chFirstname,
								   $chGender,
								   $chBirthD,
								   $chBirthM,
								   $chBirthY,
								   $lvl,
								   $cls,
								   $active);
			}
		}
		
		// Message de succès
		if( $this->add )
			$this->msg_success = "Famille '$this->name' créée !";
		elseif( $this->edit )
			$this->msg_success = "Famille '$this->name' mise à jour !";
		
		// Desactivate edit mode
		$this->add = false;
		$this->edit = false;
	}
	
	private function parse_request_finance() {
		if(!isset($_POST['btnSubmitFinance'])) return;
		
		if(isset($_POST['qf']))
			$this->qf	= strtoupper($_POST['qf']);
		$this->active	= isset($_POST['active']);
		
		// Cas particulier du QF
		if( $this->isAdmin && !in_array($this->qf, InvoiceManager::QF_LST) ) {
			$this->msg_error = "QF '$this->qf' non valide !";
			return;
		}
		
		// Mise à jour du record
		if( $this->isAdmin )
			$this->updateFamilyFinance();
		
		// Desactivate edit mode
		$this->add = false;
		$this->edit = false;
	}
	
	private function parse_request_reactivate() {
		if(!isset($_POST['btnReactivate']))  return;
		RegistrationManager::sendRegistrationRequest($this->pseudo, $this->gender1, $this->name1, $this->firstname1, $this->email1);
		RegistrationManager::sendRegistrationRequest($this->pseudo, $this->gender2, $this->name2, $this->firstname2, $this->email2);
		$this->msg_success = "Nouvelle demande d'activation du compte envoyée !";
	}
	
	private function parse_request_access() {
		if( !isset($_POST['btnSubmitAccess']) ) return;
		
		if( $this->isAdmin ) {
			if (!isset($_POST['pseudo']) ) return;
			
			$this->pseudo = $_POST['pseudo'];
			
			// Vérifie la validité des champs
			if( strlen(trim($this->pseudo)) == 0 ) {
				$this->msg_error = "Pseudo '$this->pseudo' non correct !";
				return;
			}
			if( $this->checkPseudoExisting($this->pseudo) ) {
				$this->msg_error = "Pseudo '$this->pseudo' déjà existant !";
				return;
			}
			
			// Mise à jour du pseudo (avec désactivation du compte)
			
			$this->updateFamilyUser();
			$this->msg_success = "Pseudo mis à jour !";
		
		} else {
			if (!isset($_POST['pwd0']) ||!isset($_POST['pwd1']) || !isset($_POST['pwd2']) ) return;
			$msg = RegistrationManager::changePassword($this->pseudo, $_POST['pwd0'], $_POST['pwd1'], $_POST['pwd2']);			
			if( $msg == "" ) {
				$this->msg_success = "Mot de passe mis à jour !";
			} else {
				$this->msg_error = $msg;
				return;
			}
		}
		
		// Desactivate edit mode
		$this->add = false;
		$this->edit = false;
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadFamily() {
		$query = "SELECT `famille`.`NOM_FAMILLE`, `famille`.`QF`, " .
				 "`famille`.`GENRE1`, `famille`.`NOM1`, `famille`.`PRENOM1`, `famille`.`EMAIL1`, `famille`.`LIEN1`, `famille`.`PROF1`, " .
				 "`famille`.`TEL11`, `famille`.`TEL12`, `famille`.`ADRESSE1`, `famille`.`CP1`, `famille`.`VILLE1`, " . 
				 "`famille`.`REPLEG`, " .
				 "`famille`.`GENRE2`, `famille`.`NOM2`, `famille`.`PRENOM2`, `famille`.`EMAIL2`, `famille`.`LIEN2`, `famille`.`PROF2`, " .
				 "`famille`.`TEL21`, `famille`.`TEL22`, `famille`.`ADRESSE2`, `famille`.`CP2`, `famille`.`VILLE2`, " .
				 "`user`.`LOGINID`, `user`.`ACTIVE`  " .
				 "FROM `famille` " .
				 "LEFT JOIN `user` ON `user`.`FAM_ID` = `famille`.`ID` " .
				 "WHERE `famille`.`ID`=" . $this->familyId . " LIMIT 1";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->name			= strtoupper(DBUtils::html($res[0]));
				$this->qf			= strtoupper($res[1]);
				
				$this->gender1		= strtoupper($res[2]);
				$this->name1		= strtoupper(DBUtils::html($res[3]));
				$this->firstname1	= strtoupper(DBUtils::html($res[4]));
				$this->email1		= DBUtils::html($res[5]);
				$this->link1		= strtoupper($res[6]);
				$this->prof1		= strtoupper(DBUtils::html($res[7]));
				$this->phone11		= strtoupper(DBUtils::html($res[8]));
				$this->phone12		= strtoupper(DBUtils::html($res[9]));
				$this->address1		= strtoupper(DBUtils::html($res[10]));
				$this->cp1			= strtoupper(DBUtils::html($res[11]));
				$this->city1		= strtoupper(DBUtils::html($res[12]));
				
				$this->only1LegalRp	= (intval($res[13]) == 1);
				
				$this->gender2		= strtoupper($res[14]);
				$this->name2		= strtoupper(DBUtils::html($res[15]));
				$this->firstname2	= strtoupper(DBUtils::html($res[16]));
				$this->email2		= DBUtils::html($res[17]);
				$this->link2		= strtoupper($res[18]);
				$this->prof2		= strtoupper(DBUtils::html($res[19]));
				$this->phone21		= strtoupper(DBUtils::html($res[20]));
				$this->phone22		= strtoupper(DBUtils::html($res[21]));
				$this->address2		= strtoupper(DBUtils::html($res[22]));
				$this->cp2			= strtoupper(DBUtils::html($res[23]));
				$this->city2		= strtoupper(DBUtils::html($res[24]));
				
				$this->pseudo		= DBUtils::html($res[25]);
				$this->activated	= (intval($res[26]) == 1);
				
				$this->phone11		= substr('0000000000' . $this->phone11, -10);
				$this->phone12		= substr('0000000000' . $this->phone12, -10);
				$this->phone21		= substr('0000000000' . $this->phone21, -10);
				$this->phone22		= substr('0000000000' . $this->phone22, -10);
			}
			$stmt->close();
		}
		
		$this->loadValidMails(); // Specific functions for <tt>validmail1</tt> and <tt>validmail2</tt>
	}
	
	private function loadValidMails() {
		$this->validmail1 = false;
		$query = "SELECT `ID` FROM `user_registration` WHERE `LOGINID`='$this->pseudo' AND `MAIL`='$this->email1' AND `ACTIVE`=1 LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$this->validmail1 = true;
			$stmt->close();
		}
		
		$this->validmail2 = false;
		$query = "SELECT `ID` FROM `user_registration` WHERE `LOGINID`='$this->pseudo' AND `MAIL`='$this->email2' AND `ACTIVE`=1 LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$this->validmail2 = true;
			$stmt->close();
		}
	}
	
	private function loadFinance() {
		$query = "SELECT `QF`, `ACTIF` " .
				 "FROM `famille` " .
				 "WHERE `ID`=" . $this->familyId . " LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->qf			= strtoupper($res[0]);
				$this->active		= (intval($res[1]) == 1);
			}
			$stmt->close();
		}
	}
	
	private function checkFamilyExisting($faname) {
		$existing = false;
		$query = "SELECT `ID` FROM `famille` " .
				 "WHERE `NOM_FAMILLE`='" . strtolower(DBUtils::toString($this->name)) . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$existing = true;
			$stmt->close();
		}
		return $existing;
	}
	
	private function checkPseudoExisting($pseudo) {
		$existing = false;
		$query = "SELECT `LOGINID` FROM `user` " .
				 "WHERE `LOGINID`='" . DBUtils::toString($this->pseudo) . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$existing = true;
			$stmt->close();
		}
		return $existing;
	}
	
	private function insertFamily() {
		$query = "INSERT INTO `famille` (`NOM_FAMILLE`, ".
		         "`GENRE1`, `NOM1`, `PRENOM1`, `EMAIL1`, `LIEN1`, `PROF1`, `TEL11`, `TEL12`, `ADRESSE1`, `CP1`, `VILLE1`, `REPLEG`, " .
		         "`GENRE2`, `NOM2`, `PRENOM2`, `EMAIL2`, `LIEN2`, `PROF2`, `TEL21`, `TEL22`, `ADRESSE2`, `CP2`, `VILLE2`, " .
				 "`ACTIF`) VALUES (" .
					"'"		. strtolower(DBUtils::toString($this->name))		. "'," .
					
					"'"		. strtolower(DBUtils::toString($this->gender1))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->name1))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->firstname1))	. "'," .
					"'"		. strtolower(DBUtils::toString($this->email1))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->link1))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->prof1))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->phone11))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->phone12))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->address1))	. "'," .
					"'"		. strtolower(DBUtils::toString($this->cp1))			. "'," .
					"'"		. strtolower(DBUtils::toString($this->city1))		. "'," .
				 
					"'"		. ($this->only1LegalRp ? 1 : 0)						. "'," .
					
					"'"		. strtolower(DBUtils::toString($this->gender2))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->name2))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->firstname2))	. "'," .
					"'"		. strtolower(DBUtils::toString($this->email2))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->link2))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->prof2))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->phone21))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->phone22))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->address2))	. "'," .
					"'"		. strtolower(DBUtils::toString($this->cp2))			. "'," .
					"'"		. strtolower(DBUtils::toString($this->city2))		. "'," .
					"'1')";
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		$this->familyId = $this->mysqli->insert_id;
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function insertFamilyUser() {
		$query = "INSERT INTO `user` (`LOGINID`, `PASSWORD`, `ADMIN`, `SUPER`, `ANIM`, `FAM`, `ACTIVE`, `FAM_ID`) ".
				 "VALUES (" .
					"'"		. strtolower(DBUtils::toString($this->pseudo))		. "'," .
					"'************', '0', '0', '0', '1', '0', "			.
					"'"		. $this->familyId		. "')";
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateFamilyUser() {
		$query = "UPDATE `user` SET `LOGINID`='" . strtolower(DBUtils::toString($this->pseudo)) . "', ACTIVE=0 " .
				 "WHERE `FAM_ID`='" . $this->familyId . "'";
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateFamily() {
		$query = "UPDATE `famille` SET " .
				 
				 "`GENRE1`='"		. strtolower(DBUtils::toString($this->gender1))		. "'," .
				 "`NOM1`='"		. strtolower(DBUtils::toString($this->name1))		. "'," .
				 "`PRENOM1`='"	. strtolower(DBUtils::toString($this->firstname1))	. "'," .
				 "`EMAIL1`='"		. strtolower(DBUtils::toString($this->email1))		. "'," .
				 "`LIEN1`='"		. strtolower(DBUtils::toString($this->link1))		. "'," .
				 "`PROF1`='"		. strtolower(DBUtils::toString($this->prof1))		. "'," .
				 "`TEL11`='"		. strtolower(DBUtils::toString($this->phone11))		. "'," .
				 "`TEL12`='"		. strtolower(DBUtils::toString($this->phone12))		. "'," .
				 "`ADRESSE1`='"	. strtolower(DBUtils::toString($this->address1))	. "'," .
				 "`CP1`='"		. strtolower(DBUtils::toString($this->cp1))			. "'," .
				 "`VILLE1`='"		. strtolower(DBUtils::toString($this->city1))		. "'," .
				 
				 "`REPLEG`='"		. ($this->only1LegalRp ? 1 : 0)						. "'," .
				 
				 "`GENRE2`='"		. strtolower(DBUtils::toString($this->gender2))		. "'," .
				 "`NOM2`='"		. strtolower(DBUtils::toString($this->name2))		. "'," .
				 "`PRENOM2`='"	. strtolower(DBUtils::toString($this->firstname2))	. "'," .
				 "`EMAIL2`='"		. strtolower(DBUtils::toString($this->email2))		. "'," .
				 "`LIEN2`='"		. strtolower(DBUtils::toString($this->link2))		. "'," .
				 "`PROF2`='"		. strtolower(DBUtils::toString($this->prof2))		. "'," .
				 "`TEL21`='"		. strtolower(DBUtils::toString($this->phone21))		. "'," .
				 "`TEL22`='"		. strtolower(DBUtils::toString($this->phone22))		. "'," .
				 "`ADRESSE2`='"	. strtolower(DBUtils::toString($this->address2))	. "'," .
				 "`CP2`='"		. strtolower(DBUtils::toString($this->cp2))			. "'," .
				 "`VILLE2`='"		. strtolower(DBUtils::toString($this->city2))		. "' " .
				 
				 "WHERE `ID`=" . $this->familyId;
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateFamilyFinance() {
		$act = 0;
		if( $this->active ) $act = 1;
		$query = "UPDATE `famille` SET " .
				 "`QF`='"		. strtoupper(DBUtils::toString($this->qf))	. "', " .
				 "`ACTIF`='"	. $act										. "' " .
				 "WHERE `ID`="	. $this->familyId;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		
		if( !$this->active ) {
			$query = "UPDATE `enfant` SET `ACTIF`='0' " .
					 "WHERE `ID_FAMILLE`=" . $this->familyId;
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function updateChild($childId, $chName, $chFirstname, $chGender, $chBirthD, $chBirthM, $chBirthY, $lvl, $cls, $active) {
		$act = 0;
		if( $active ) $act = 1;
		$query = "UPDATE `enfant` SET " .
				 
				 "`NOM`='"				. strtolower(DBUtils::toString($chName))		. "',"	.
				 "`PRENOM`='"			. strtolower(DBUtils::toString($chFirstname))	. "',"	.
				 "`GENRE`='"			. strtolower(DBUtils::toString($chGender))	. "',"	.
				 "`DATE_NAISS_J`="		. strtolower(DBUtils::toString($chBirthD))		. ","	.
				 "`DATE_NAISS_M`="		. strtolower(DBUtils::toString($chBirthM))		. ","	.
				 "`DATE_NAISS_A`="		. strtolower(DBUtils::toString($chBirthY))		. ","	.
				 "`NIVEAU`='"			. strtolower(DBUtils::toString($lvl))			. "'," 		.
				 "`CLASSE`='"			. strtolower(DBUtils::toString($cls))			. "', "	.
				 "`ACTIF`='"			. $act											. "' "	.
				 				 
				 "WHERE `ID`=" 			. $childId;
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function deleteChild($childId) {
		$query = "DELETE FROM `enfant` " .				 				 
				 "WHERE `ID`=" 			. $childId;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function insertChild($chName, $chFirstname, $chGender, $chBirthD, $chBirthM, $chBirthY, $lvl, $cls, $active) {
		$act = 0;
		if( $active ) $act = 1;
		$query = "INSERT INTO `enfant` (`ID_FAMILLE`, `NOM`, `PRENOM`, `GENRE`, `DATE_NAISS_J`, `DATE_NAISS_M`, `DATE_NAISS_A`, `NIVEAU`, `CLASSE`, `ACTIF`) " .
				 "VALUES(" .
						  $this->familyId								. ","	.
				 "'"	. strtolower(DBUtils::toString($chName))		. "',"		.
				 "'"	. strtolower(DBUtils::toString($chFirstname))	. "',"		.
				 "'"	. strtolower(DBUtils::toString($chGender))	. "',"		.
						  strtolower(DBUtils::toString($chBirthD))		. ","		.
						  strtolower(DBUtils::toString($chBirthM))		. "," 		.
						  strtolower(DBUtils::toString($chBirthY))		. "," 		.
				 "'"	. strtolower(DBUtils::toString($lvl))			. "'," 		.
				 "'"	. strtolower(DBUtils::toString($cls))			. "'," 		.
				 "'"	. $act											. "')"		;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}	
	
	private function loadChildren() {
		unset($this->children);
		$this->children = array();
		$query = "SELECT `ID`, `NOM`, `PRENOM`, `GENRE`, `DATE_NAISS_J`, `DATE_NAISS_M`, `DATE_NAISS_A`, `NIVEAU`, `CLASSE`, `ACTIF` " .
				 "FROM `enfant` " .
				 "WHERE `ID_FAMILLE`=" . $this->familyId . " ";
		if( !$this->isAdmin )
			$query .= "AND `ACTIF`=1 ";
		$query .= "ORDER BY `ID`";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$child = array();
				$child['id']		= intval($res[0]);
				$child['name']		= strtoupper($res[1]);
				$child['firstname']	= strtoupper($res[2]);
				$child['gender']	= strtoupper($res[3]);
				$child['birthD']	= intval($res[4]);
				$child['birthM']	= intval($res[5]);
				$child['birthY']	= intval($res[6]);
				$child['level']		= strtoupper($res[7]);
				$child['class']		= strtoupper($res[8]);
				$child['active']	= (intval($res[9]) == 1);
				array_push($this->children, $child);
			}
			$stmt->close();
		}
	}
}
?>