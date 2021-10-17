<?php
require_once('../services/Database.php');

class User {
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $_loginId		= 0;		// type: int
	
	private $_isAdmin		= false;	// type: boolean
	private $_isSuper		= false;	// type: boolean
	private $_isAnim		= false;	// type: boolean
	private $_isFamily		= false;	// type: boolean
	private $_isActivated	= false;	// type: boolean
	private $_isActive		= false;	// type: boolean
	
	private $_familyId		= 0;		// type: int
	private $_children		= array();	// Array format : list of Id

	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	// Constructor
	public function __construct() {
	}
	
	public function load() {

	}

	public function getUserId()		{ return $this->_loginId;	}
	public function isAdmin()		{ return $this->_isAdmin;	}
	public function isSuper()		{ return $this->_isSuper;	}
	public function isAnim()		{ return $this->_isAnim;	}
	public function isFamily()		{ return $this->_isFamily;	}
	public function getFamilyId()	{ return $this->_familyId;	}
	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	public static function login($loginid, $password){
		$db = Database::getInstance();
		$mysqli = $db->getConnection();
		
		$query = "SELECT `user`.`LOGINID`, `user`.`ADMIN`, `user`.`SUPER`, `user`.`ANIM`, `user`.`FAM`, `user`.`FAM_ID`, `user`.`ACTIVE`, `famille`.`ACTIF` " .
				 "FROM `user` " .
				 "LEFT JOIN `famille` ON `famille`.`ID` = `user`.`FAM_ID` " .
				 "WHERE " .
					"`user`.`LOGINID`='"		. DBUtils::toString($loginid)	. "' AND " .
					"`user`.`PASSWORD`=SHA1('"	. DBUtils::toString($password)	. "') " .
				 "LIMIT 1";
		$stmt= $mysqli->query($query);
		if(false == $stmt){
			trigger_error("Error in query: " . mysqli_connect_error(),E_USER_ERROR);
		} else {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$user = new User();
				$user->_loginId = $res[0];
				if( isset($res[1]) && is_numeric($res[1]) ) $user->_isAdmin		= ($res[1] == 1);
				if( isset($res[2]) && is_numeric($res[2]) ) $user->_isSuper		= ($res[2] == 1);
				if( isset($res[3]) && is_numeric($res[3]) ) $user->_isAnim		= ($res[3] == 1);
				if( isset($res[4]) && is_numeric($res[4]) ) $user->_isFamily	= ($res[4] == 1);
				if( isset($res[5]) && is_numeric($res[5]) ) $user->_familyId	= $res[5];
				if( isset($res[6]) && is_numeric($res[6]) ) $user->_isActivated	= ($res[6] == 1);
				if( isset($res[7]) && is_numeric($res[7]) ) $user->_isActive	= ($res[7] == 1);

				if( (($user->_isAdmin || $user->_isSuper || $user->_isAnim) && $user->_isActivated) ||
					($user->_isFamily && $user->_isActive && $user->_isActivated) ) {
					$user->loadChildren();
					$_SESSION ['user'] = $user;
					
					LogManager::log(__CLASS__ , __FUNCTION__ , 0, 'User "' . $user->_loginId . '" is connected.');
					
					if( $user->_isFamily )
						header('location:../q/prof');
					else if( $user->_isAnim )
						header('location:../q/erlv');
					else
						header('location:../q/lchl');
				} else {
					echo "<script>alert('Compte inactivé')</script>";
					//header('location:login.php');
				}
			} else {
				echo "<script>alert('Login / Password invalide !')</script>";
				//header('location:login.php');
			}
		}
	}
	
	public static function logout(){
		session_start();
		if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
		if( isset($user) )
			LogManager::log('AccessManager', 'logout', 0, 'User "' . $user->_loginId . '" is disconnected.');
		unset($_SESSION['user']);
		unset($_SESSION['__PARAM__']);
		session_destroy();
	}
	
	public function canAccessToChild($childId) {
		return in_array($childId, $this->_children);
	}
	
	public function updateAccessToChild() {
		$this->loadChildren();
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadChildren() {
		unset($this->_children);
		$this->_children = array();
		$db = Database::getInstance();
		$mysqli = $db->getConnection();
		$query = "SELECT `ID` " .
				 "FROM `enfant` " .
				 "WHERE `ID_FAMILLE`=" . $this->_familyId . " " .
				 "AND `ACTIF`=1";
		$stmt = $mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				array_push($this->_children, $res[0]);
			}
		}
	}
}
?>