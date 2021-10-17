<?php  
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

$file = basename(urlencode($_GET['stuff']));

$path = $_SERVER['PHP_SELF'];
$selfFile = basename ($path);
if( $file == $selfFile ) {
	echo "Access denied ! Please contact your administrator";
	exit;
}

// Access control
if( !canAccessToFile($user, $file) ) {
	echo "Access denied ! Please contact your administrator.";
	exit;
}

ob_start();//add this to the beginning of your code 
$filepath = dirname(__FILE__)  . DIRECTORY_SEPARATOR . $file;

if (file_exists($filepath) && is_readable($filepath) ) {
	header('Content-Description: File Transfer');
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=$file");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header("content-length=".filesize($filepath));
	header("Content-Transfer-Encoding: binary");
	while (ob_get_level()) {
		ob_end_clean();
	}
	flush();
	readfile($filepath);
}

function canAccessToFile($user, $filename) {
	$mysqli		= Database::getInstance()->getConnection();
	$exist		= false;
	$ownerId	= 0;
	$query = "SELECT OWNER_ID " .
			 "FROM files_upload " .
			 "WHERE FILENAME='" . $filename . "' LIMIT 1";
	$stmt= $mysqli->query($query);
	if (is_object($stmt)) {
		if($res = $stmt->fetch_array(MYSQLI_NUM)) {
			$exist = true;
			$ownerId = $res[0];
		}
	}
	if( !$exist ) return false;
	if( $user->isAdmin() || $user->isSuper() ) return true;
	if( $user->isAnim() ) return false;
	return $user->getFamilyId() == $ownerId;
}
?>