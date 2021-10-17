<?php
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access control
if( !$user->isAdmin() && !$user->isSuper() ) {
	echo "Acess denied on this page - Please contact your administrator";
	return;
}

if(isset($_GET['in']) && is_numeric($_GET['in']))
	$invoiceId	= intval($_GET['in']);
else
	return;

// Initialize database connection
$db		= Database::getInstance();
$mysqli	= $db->getConnection();

// Select request
$query = "SELECT `ID`, `DATE`, `ID_FAMILLE`, `ID_FACTURE`, `CREDIT`, `DEBIT`, `COMMENTAIRE`, `LOGINID` " .
         "FROM `ecriture` " .
		 "WHERE `ID_FACTURE`=$invoiceId " .
		 "ORDER BY `ID`";
$stmt = $mysqli->query($query);
$html = "";
if( is_object($stmt) ) {
	while($res = $stmt->fetch_array(MYSQLI_NUM)) {
		$html .= "<tr>";
		$html .= "<td>" . $res[0] . "</td>";
		$html .= "<td>" . $res[1] . "</td>";
		$html .= "<td>" . $res[2] . "</td>";
		$html .= "<td>" . $res[3] . "</td>";
		$html .= "<td>" . $res[4] . "</td>";
		$html .= "<td>" . $res[5] . "</td>";
		$html .= "<td>" . $res[6] . "</td>";
		$html .= "<td>" . $res[7] . "</td>";
		$html .= "</tr>";
	}
	$stmt->close();
}
?>
<div style="padding: 10px">
<table  class="table table-striped table-hover">
<tr><th>ID</th><th>DATE</th><th>ID_FAMILLE</th><th>ID_FACTURE</th><th>CREDIT</th><th>DEBIT</th><th>COMMENTAIRE</th><th>LOGINID</th></tr>
<?php echo $html; ?>
</table>
</div>