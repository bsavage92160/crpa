<?php 
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access Family Item
require_once('../services/FamilyControler.php');
$obj=new FamilyControler();
$obj->initialize( $user->isAdmin() || $user->isSuper() );

// Access control
if( !$user->isAdmin() && !$user->isSuper() && !$user->isAnim() ) {
	$obj->setFamilyId($user->getFamilyId());
	$obj->add = false;
}
	
// Load data of this page
$obj->load();

// Parse request
$obj->parse_request();

echo json_encode(array(
			"msg_success"   => $obj->msg_success,
			"msg_error"    	=> $obj->msg_error)
		);
?>