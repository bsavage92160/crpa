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

// Initialize ReleveControler
require_once('../services/ReleveControler.php');
$obj=new ReleveControler();
$obj->initialize();

// Treat request
$obj->parse_request();

echo $obj->success_msg;
?>