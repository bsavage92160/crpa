<?php
require_once('../services/RegistrationManager.php');

// status == 0 : Données d'activation incorrectes ; Merci de contacter l'administrateur du site
// status == 1 : Compte déjà activté ; Retour à la page d'accueil
// status == 2 : Compte à activer ; Définition du mot de passe
// status == 3 : Compte activté ; Retour à la page d'accueil
$status = 0;
$resultat = "";
$id = 0;
$dt = 0;
$key = "";

if( isset($_POST['submit']) ) {
	if( isset($_POST['id']) && is_numeric($_POST['id']) && isset($_POST['dt']) &&
		isset($_POST['key']) && isset($_POST['password1']) && isset($_POST['password2']) ) {

		$id = intval($_POST['id']);
		$dt = $_POST['dt'];
		$key = $_POST['key'];
		$pwd1 = $_POST['password1'];
		$pwd2 = $_POST['password2'];
		if( $pwd1 != $pwd2) {
			$status = 2;
			$resultat = '<div class="alert alert-warning" role="alert">Le champ &quot;Mot de passe&quot; et le champ &quot;Confirmation Mot de passe&quot; doivent &ecirc;tre identiques, veuillez recommencer l\'inscription.</div>';
		} else {
			if( RegistrationManager::activateAccess($id, $dt, $key, $pwd1) )
				$status = 3;
			else
				$status = 0;
		}
	}
	
} elseif( isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['dt']) && isset($_GET['key']) ) {
	$id = intval($_GET['id']);
	$dt = $_GET['dt'];
	$key = $_GET['key'];
	if( RegistrationManager::checkKey($id, $dt, $key) ) {
		
		// Enregistrement de la validité de l'adresse mail
		RegistrationManager::activateUserRegistration($id, $dt, $key);
		
		// Vérification de l'activation du compte
		if( RegistrationManager::checkAccessActivated($id, $dt, $key) )
			$status = 1;
		else
			$status = 2;
	} else {
		$status = 0;
	}
}

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="shortcut icon" href="../images/favicon.ico">
<link href="../dist/css/bootstrap.css" rel="stylesheet">
<link href="../dist/css/metisMenu.min.css" rel="stylesheet">
<link href="../dist/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<link href="../dist/css/sb-admin-2.css" rel="stylesheet" type="text/css">
<link href="../dist/css/app.css"	rel="stylesheet" type="text/css">
<style>
.field-icon { float: right; margin-left: -25px; margin-top: -25px; position: relative; z-index: 2; }
</style>
<title>Activiation Compte</title>
</head>
<body>
<h2 style="text-align: center;">Ecole Nouvelle Antony</h2>
<?php

if( $status == 0 ) {

?>
<div class="container">
	<div class="alert alert-danger" role="alert">
		<h4 class="alert-heading"><b>Erreur d'activation du compte !</b></h4>
		<hr>
		Les données d'activation du compte sont incorrectes. Merci de contacter l'administrateur du site.
	</div>
</div>
<?php

} elseif( $status == 1 ) {

?>
<div class="container">
	<div class="alert alert-success" role="alert">
		<h4 class="alert-heading"><b>Compte déjà activé !</b></h4>
		<hr>
		Vous pouvez vous connecter au portail avec vos identifiants.<br>&nbsp;<br>
		<button type="button" class="btn btn-primary" onclick="location.href='login.php';"><i class="glyphicon glyphicon-home"></i> Retour Accueil</button>
	</div>
</div>
<?php

} elseif( $status == 3 ) {

?>
<div class="container">
	<div class="alert alert-success" role="alert">
		<h4 class="alert-heading"><b>Compte activé !</b></h4>
		<hr>
		Vous pouvez vous connecter au portail avec vos identifiants.<br>&nbsp;<br>
		<button type="button" class="btn btn-primary" onclick="location.href='login.php';"><i class="glyphicon glyphicon-home"></i> Retour Accueil</button>
	</div>
</div>
<?php

} elseif( $status == 2 ) {

?>
<div class="container">
	<br>
	<div class="col-md-4 col-md-offset-4">
		<?php echo $resultat; ?>
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title">Activation de votre compte</h3>
			</div>
			<div class="panel-body">
				<form class="form-signin" method="post">
					<input type="hidden" name="id" value="<?php echo $id; ?>" >
					<input type="hidden" name="dt" value="<?php echo $dt; ?>" >
					<input type="hidden" name="key" value="<?php echo $key; ?>" >
					<label for="inputPassword" class="sr-only">Mot de passe</label>
					<input type="password" id="inputPassword" name="password1" class="form-control" placeholder="Mot de passe" required autofocus>
					<span toggle="#inputPassword" class="fa fa-fw fa-eye field-icon toggle-password"></span>
					<label for="inputPasswordConf" class="sr-only">Confirmation Mot de passe</label>
					<input type="password" id="inputPasswordConf" name="password2" class="form-control" placeholder="Confirmation Mot de passe" required>
					<span toggle="#inputPasswordConf" class="fa fa-fw fa-eye field-icon toggle-password"></span>
					<button class="btn btn-lg btn-primary btn-block" type="submit" name="submit">Valider</button>
					<h4 class="mt-5 mb-3 text-muted small"><i><?php echo date('d/m/Y H:i:s', time()); ?></i></h4>
				</form>
			</div>
		</div>
	</div>
</div>
<script src="../dist/js/jquery.min.js"></script>
<script>
$(".toggle-password").click(function() {
	$(this).toggleClass("fa-eye fa-eye-slash");
	var input = $($(this).attr("toggle"));
	if (input.attr("type") == "password") {
		input.attr("type", "text");
	} else {
		input.attr("type", "password");
	}
});

$(document).ready(function(){
	// Remove FreeWHA ads !!
	$('a[href*=freewebhostingarea]').closest('div').remove();
});
</script>
<?php

}

?>
</body>
</html>