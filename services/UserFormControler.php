<?php
require_once('../services/Database.php');
require_once('../services/RegistrationManager.php');

class UserFormControler {
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	public $userId			= "";		// type: String
	public $name			= "";		// type: String
	public $firstname		= "";		// type: String
	public $email			= "";		// type: String
	
	public $admin			= false;	// type: boolean
	public $anim			= false;	// type: boolean
	public $family			= false;	// type: boolean
		
	public $activated		= false;	// type: boolean
	public $validmail		= false;	// type: boolean

	public $isAdmin			= false;	// type: boolean
	public $isOwner			= false;	// type: boolean
	public $submit			= false;	// type: boolean
	public $edit			= false;	// type: boolean
	public $add				= false;	// type: boolean
	public $del				= false;	// type: boolean
	public $msg_error		= "";		// type: String
	public $msg_success		= "";		// type: String
	
	public $back			= "";		// type: String
	public $backParam		= "";		// type: String
	public $backUrl			= "";		// type: String
	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize($currentUserId, $isAdmin) {
		
		if(isset($_GET['id']))											$this->userId		= $_GET['id'];
		if(isset($_POST['id']))											$this->userId		= $_POST['id'];
		
		if(isset($_GET['edit']) && is_numeric($_GET['edit']))			$this->edit			= (intval($_GET['edit']) == 1);
		if(isset($_POST['edit']) && is_numeric($_POST['edit']))			$this->edit			= (intval($_POST['edit']) == 1);
		if(isset($_GET['add']) && is_numeric($_GET['add'])) 			$this->add			= (intval($_GET['add']) == 1);
		if(isset($_POST['add']) && is_numeric($_POST['add'])) 			$this->add			= (intval($_POST['add']) == 1);
		if(isset($_POST['delete']) && is_numeric($_POST['delete'])) 	$this->del			= (intval($_POST['delete']) == 1);
		
		if(isset($_GET['bk']))											$this->back  		= $_GET['bk'];
		if(isset($_POST['bk']))											$this->back  		= $_POST['bk'];
		if(isset($_GET['bkparam']))										$this->backParam  	= urlencode($_GET['bkparam']);
		if(isset($_POST['bkparam']))									$this->backParam  	= urlencode($_POST['bkparam']);
		if($this->back == "lstus")										$this->backUrl		= "../q/lusr?id=" . $this->userId;
		
		if($this->backUrl != "")										$this->backUrl 	   .= "&" . urldecode($this->backParam);
		
		$this->submit = isset($_POST['btnSubmit']);
		if( $this->del ) $this->submit = true;
		
		if(!isset($_POST['id']) && !isset($_GET['id']) && !$this->add)	$this->userId		= $currentUserId;
		
		// Initialize database connection
		$this->db				= Database::getInstance();
		$this->mysqli			= $this->db->getConnection();
		
		// Set if owner profile
		$this->isOwner			= ($this->userId == $currentUserId);
		$this->isAdmin			= $isAdmin;
	}
	
	public function load() {
		if( !$this->submit )
			$this->loadUser();
	}
	
	public function parse_request() {
		
		if(isset($_POST['cancel'])) {		
			// Desactivate edit mode
			$this->add = false;
			$this->edit = false;
			return;
		}
		
		if( !$this->submit ) return;
		
		if(isset($_POST['pseudo']))		$this->userId		= $_POST['pseudo'];
		if(isset($_POST['name']))		$this->name			= $_POST['name'];
		if(isset($_POST['firstname']))	$this->firstname	= $_POST['firstname'];
		if(isset($_POST['email']))		$this->email		= $_POST['email'];
		if(isset($_POST['anim']))		$this->anim			= true;
		if(isset($_POST['admin']))		$this->admin		= true;
		
		$this->userId = str_replace(' ', '', $this->userId);
		
		// Vérifie la validité des champs
		if( $this->add ) {
			if( strlen(trim($this->userId)) == 0 ) {
				$this->msg_error = "Pseudo '$this->userId' non correct !";
				return;
			}
			if( $this->checkPseudoExisting($this->userId) ) {
				$this->msg_error = "Pseudo '$this->userId' déjà existant !";
				return;
			}
		}
		if( strlen(trim($this->name)) == 0 ) {
			$this->msg_error = "Le champs <tt>Nom</tt> ne peut être vide !";
			return;
		}
		if( strlen(trim($this->firstname)) == 0 ) {
			$this->msg_error = "Le champs <tt>Prenom</tt> ne peut être vide !";
			return;
		}
		if( strlen(trim($this->email)) == 0 ) {
			$this->msg_error = "Le champs <tt>Mail d'accès</tt> ne peut être vide !";
			return;
		}
		if( !filter_var($this->email, FILTER_VALIDATE_EMAIL) ) {
			$this->msg_error = "Adresse mail '$this->email' non valide !";
			return;
		}
		
		// Mise à jour du record
		if( $this->add && $this->isAdmin) {
			$this->insertUser();
			RegistrationManager::sendRegistrationRequest($this->userId, "", $this->name, $this->firstname, $this->email);
			
		} elseif( $this->del && $this->isAdmin) {
			$this->deleteUser();
			
		} elseif( $this->edit ) {
			if( $this->isAdmin)
				$this->updateUserAdmin();
			else
				$this->updateUser();
		}
				
		// Message de succès
		if( $this->add && $this->isAdmin) {
			$this->msg_success = "Utilisateur '$this->userId' créé !";
		
		} elseif( $this->del && $this->isAdmin) {
			$this->msg_success = "Utilisateur '$this->userId' supprimé !";
			$this->userId = "";
		
		} elseif( $this->edit ) {
			$this->msg_success = "Utilisateur '$this->userId' mis à jour !";
		}
		
		// Mise à jour du mot de passe
		if( $this->isOwner ) {
			if( isset($_POST['pwd0']) && isset($_POST['pwd1']) && isset($_POST['pwd2']) &&
				strlen(trim($_POST['pwd0'])) > 0 && strlen(trim($_POST['pwd1'])) > 0 && strlen(trim($_POST['pwd2'])) > 0 ) {
				$msg = RegistrationManager::changePassword($this->userId, $_POST['pwd0'], $_POST['pwd1'], $_POST['pwd2']);			
				if( $msg == "" ) {
					$this->msg_success .= "&nbsp;Mot de passe mis à jour !";
				} else {
					$this->msg_success = "";
					$this->msg_error = $msg;
					return;
				}
			}
		}
		
		// Desactivate edit mode
		$this->add = false;
		$this->edit = false;
		$this->submit = false;
	}
	
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadUser() {
		$query = "SELECT `NOM`, `PRENOM`, `MAIL`, `ADMIN`, `ANIM`, `FAM`, `ACTIVE` " .
		         "FROM `user` " .
				 "WHERE `LOGINID`='" . $this->userId . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->name			= strtoupper(DBUtils::html($res[0]));
				$this->firstname	= ucfirst(strtolower(DBUtils::html($res[1])));
				$this->email		= DBUtils::html($res[2]);
				
				$this->admin		= (intval($res[3]) == 1);
				$this->anim			= (intval($res[4]) == 1);
				$this->family		= (intval($res[5]) == 1);
				
				$this->activated	= (intval($res[6]) == 1);
			}
			$stmt->close();
		}
		
		$this->loadValidMails(); // Specific functions for <tt>validmail</tt>
	}
	
	private function loadValidMails() {
		$this->validmail = false;
		$query = "SELECT `ID` FROM `user_registration` WHERE `LOGINID`='$this->userId' AND `MAIL`='$this->email' AND `ACTIVE`=1 LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$this->validmail = true;
			$stmt->close();
		}
	}
	
	private function checkPseudoExisting($pseudo) {
		$existing = false;
		$query = "SELECT `LOGINID` FROM `user` " .
				 "WHERE `LOGINID`='" . DBUtils::toString($this->userId) . "' LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$existing = true;
			$stmt->close();
		}
		return $existing;
	}
	
	private function insertUser() {
		$query = "INSERT INTO `user` (`LOGINID`, `PASSWORD`, `NOM`, `PRENOM`, `MAIL`, `ADMIN`, `ANIM`, `FAM`, `FAM_ID`, `ACTIVE`, `SUPER`) ".
		         "VALUES (" .
					"'"		. strtolower(DBUtils::toString($this->userId))		. "'," .
					"'************',"											.
					
					"'"		. strtolower(DBUtils::toString($this->name))		. "'," .
					"'"		. strtolower(DBUtils::toString($this->firstname))	. "'," .
					"'"		. strtolower(DBUtils::toString($this->email))		. "'," .
					
					"'"		. intval($this->admin)								. "'," .
					"'"		. intval($this->anim)								. "'," .
					
					"'0', NULL, '1', '0')";
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateUser() {
		$query = "UPDATE `user` SET " .
				 
				 "`NOM`='"		. strtolower(DBUtils::toString($this->name))		. "'," .
				 "`PRENOM`='"	. strtolower(DBUtils::toString($this->firstname))	. "'," .
				 "`MAIL`='"		. strtolower(DBUtils::toString($this->email))		. "' " .
				 
				 "WHERE `LOGINID`='" . $this->userId . "'";
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function updateUserAdmin() {
		$query = "UPDATE `user` SET " .
				 
				 "`NOM`='"		. strtolower(DBUtils::toString($this->name))		. "'," .
				 "`PRENOM`='"	. strtolower(DBUtils::toString($this->firstname))	. "'," .
				 "`MAIL`='"		. strtolower(DBUtils::toString($this->email))		. "'," .
				 
				 "`ADMIN`='"	. intval($this->admin)								. "'," .
				 "`ANIM`='"		. intval($this->anim)								. "' " .
				 
				 "WHERE `LOGINID`='" . $this->userId . "'";
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function deleteUser() {
		$query = "DELETE FROM `user` WHERE `LOGINID`='" . $this->userId . "'";
//		echo "query=$query<br>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
}
?>