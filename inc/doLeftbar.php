<?php

$metisMenu1 = array( "Mes informations",
					 "user",
					 "../q/prof",															// profile.php
					 null );

$metisMenu1b = array( "Mes informations",
					 "user",
					 "../q/eusr",
					 null );																// edit-user.php

$metisMenu2 = array( "Activités CLAE", 
					 "calendar",
					 "#",
					 array(	"Réservation / Modification"	=> "../q/ersa",					// edit-reservation.php
							"Relevé de présence"			=> "../q/prel",					// print-releve.php
							"Factures"						=> "../q/linv")		);			// list-invoice.php
							

$metisMenu2b = array( "Activités CLAE", 
					 "calendar",
					 "#",
					 array(	"Réservation / Modification"	=> "../q/ersa")	);				// edit-reservation.php
													
$metisMenu3 = array( "Administration CLAE",
					 "cog",
					 "#",
					 array(	"Liste des enfants"				=> "../q/lchl",					// list-children.php
							"Edition des réservations"		=> "../q/pres",					// print-reservation.php
							"Pointage des présences"		=> "../q/erlv",					// edit-releve.php
							"Calendrier CLAE"				=> "../q/ecal",					// edit-calendar.php
							"Statistiques de fréquentation"	=> "stats.php")
					);
														
$metisMenu4 = array( "Administration du portail",
					 "equalizer",
					 "#", 
					 array(	"Liste des familles"			=> "../q/lfam",					// list-family.php
							"Facturation"					=> "../q/einp", 				// edit-invoice-period.php
							"Paiement"						=> "../q/epay",					// edit-payment.php
							"Gestion des accès"				=> "../q/lusr", 				// list-user.php
							"Paramètres"					=> "../q/parm")					// param.php
					);

$metisMenu5 = array( "Logout",
					 "log-out",
					 "../q/logo",															// logout.php
					 null);

$metisMenu = array();
if( $user->isFamily() || $user->isSuper() )
	$metisMenu[] = $metisMenu1;

if( !$user->isFamily() || $user->isSuper() )
	$metisMenu[] = $metisMenu1b;

if( $user->isFamily() || $user->isSuper() )
	$metisMenu[] = $metisMenu2;

if( $user->isAdmin() )
	$metisMenu[] = $metisMenu2b;

if( $user->isAdmin() || $user->isAnim() || $user->isSuper() )
	$metisMenu[] = $metisMenu3;

if( $user->isAdmin() || $user->isSuper() )
	$metisMenu[] = $metisMenu4;

$metisMenu[] = $metisMenu5;

?>
<nav class="hidden-print" style="margin-bottom: 0">
	<div class="navbar-header">
		<a class="navbar-brand" href="../index.php">Ecole Nouvelle Antony</a>
	</div>
	<div class="navbar-default sidebar" role="navigation">
		<div class="sidebar-nav navbar-collapse">
			<ul class="nav" id="side-menu">
<?php
$filename = basename($_SERVER['PHP_SELF']);
$html = "";
foreach( $metisMenu as $level1 ) {
	$selected = false;
	$level2Lst =  $level1[3];
	$html1  = "";
	$html1 .= "					<a href=\"" .  $level1[2] . "\"><i class=\"glyphicon glyphicon-" .  $level1[1] . " fa-fw\"></i> " . $level1[0];
	if( $level2Lst != null )
		$html1 .= "<span class=\"fa arrow\"></span>";
	$html1 .= "</a>\n";
	if( $level2Lst != null ) {
		$html2 = "";
		foreach( $level2Lst as $level2 => $url ) {
			$selected = $selected || ($filename == $url);
			$html2 .= "						<li><a href=\"" . $url . "\" ";
			if( $filename == $url )
				$html2 .= "class=\"active\"";
			$html2 .= ">" . $level2 . "</a></li>\n";
		}
		if( $selected )
			$html1 .= "					<ul class=\"nav nav-second-level collapse in\">\n";
		else
			$html1 .= "					<ul class=\"nav nav-second-level collapse\">\n";
		$html1 .= $html2;
		$html1 .= "					</ul>\n";
	}
	if( $selected )
		$html .= "				<li class=\"active\">\n";
	else
		$html .= "				<li>\n";
	$html .= $html1;
	$html .= "				</li>\n";
	
}
echo $html;
?>
			</ul>
		</div>
	</div>
</nav>