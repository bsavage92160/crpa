<?php 
require_once('../services/AccessManager.php');
require_once('../services/Database.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access control
if( !$user->isAdmin() && !$user->isSuper() && !$user->isAnim() ) {
	echo "Acess denied on this page - Please contact your administrator";
	return;
}

// Define offset and limit
$pageno	= $_POST['pageno'];
$no_of_records_per_page = 25;
$offset	= ($pageno - 1) * $no_of_records_per_page;
$limit	= "LIMIT $offset, $no_of_records_per_page";
		
// Initialize ReleveControler
require_once('../services/ReleveControler.php');
$obj=new ReleveControler();
$obj->initialize();
$obj->build_calendar_append($limit);
?>