<?php 
require_once('../services/AccessManager.php');
require_once('../services/Database.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access control
if( !$user->isAnim() && !$user->isAdmin() && !$user->isSuper() ) {
	echo "Acess denied !";
	return;
}

$db = Database::getInstance();
$mysqli = $db->getConnection();

$query = "SELECT 
		  (UNIX_TIMESTAMP(STR_TO_DATE(`calendrier`.NUM_JOUR, '%Y%m%d'))+4*60*60)*1000 AS J, 
		  (SELECT COUNT(`reservation`.ID_ENFANT) 
				FROM `reservation`
				WHERE `reservation`.NUM_JOUR=`calendrier`.NUM_JOUR AND `reservation`.CODE_ACT='MAT')
			AS RES_MAT, 
		  
		  (SELECT COUNT(`reservation`.ID_ENFANT) 
				FROM `reservation`
				WHERE `reservation`.NUM_JOUR=`calendrier`.NUM_JOUR AND (`reservation`.CODE_ACT='SOI' OR `reservation`.CODE_ACT='APM')) 
			AS RES_SOI, 
		  
		  (SELECT COUNT(`releve`.ID_ENFANT) 
				FROM `releve` 
				WHERE `releve`.NUM_JOUR=`calendrier`.NUM_JOUR AND `releve`.CODE_ACT='MAT') 
			AS REL_MAT, 
		  
		  (SELECT COUNT(`releve`.ID_ENFANT) 
				FROM `releve` 
				WHERE `releve`.NUM_JOUR=`calendrier`.NUM_JOUR AND (`releve`.CODE_ACT='SOI' OR `releve`.CODE_ACT='APM')) 
			AS REL_SOI 
		  
		  FROM `calendrier` 
		  WHERE `calendrier`.OUV=1 
		 ";

$stmt = $mysqli->query($query);
echo json_encode($stmt->fetch_all(MYSQLI_NUM), JSON_NUMERIC_CHECK);
?>