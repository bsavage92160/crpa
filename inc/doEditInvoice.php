<?php
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Initialize InvoiceControler
require_once('../services/InvoiceControler.php');
$obj=new InvoiceControler();
$obj->initialize($user->getFamilyId());

// Access control
if( $user->isFamily() ) {
	 if( !$obj->checkAccesstoInvoice() ) {
		 echo("Access denied !! Please contact your administrator");
		 return;
	 }
}

// Load invoice data
$obj->loadInvoiceDetails();
?>
<div style="padding: 10px">
<?php echo $obj->build_DetailInvoiceTable(); ?>
</div>