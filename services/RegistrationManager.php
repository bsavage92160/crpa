<?php
require_once('../services/Database.php');
require_once('../services/ParameterManager.php');

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require '../mail/Exception.php';
require '../mail/PHPMailer.php';
require '../mail/SMTP.php';

class RegistrationManager {
	
	/**
	 * Fonction utilisé lors de la création d'une famille et/ou utilisateur.
	 * Elle crée un enregistrement dans la table <tt>user_registration</tt>, puis envoie un mail d'activation du compte.
	 */
	public static function sendRegistrationRequest($loginid, $gender, $name, $firstname, $mail) {
		if( strlen(trim($loginid)) == 0 || strlen(trim($mail)) == 0 )
			return;
		$key = substr(uniqid("", true), 0, 23);
		$dt  = date('YmdHis', time());
		$id  = Self::insertUserRegistration($loginid, $dt, $mail, $key);
		$baseurl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
		$requestUri = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'],'/'.basename($_SERVER['REQUEST_URI'])));
		$activationUrl = $baseurl . $requestUri . "/ausr?id=$id&dt=$dt&key=$key";
		Self::sendRegistrationMail($loginid, $gender, strtoupper($name), ucfirst(strtolower($firstname)), $mail, $activationUrl);
	}
	
	/**
	 * Fonction utilisé lors de l'activation d'un compte : Elle permet de vérifier la cohérence du lien d'activation du compte.
	 */
	public static function checkKey($id, $dt, $key) {
		$ok = false;
		$query = "SELECT `LOGINID` FROM `user_registration` WHERE "	.
					"`ID`='"				. $id 	. "' AND "		.
					"`HORODATAGE`='"		. $dt 	. "' AND "		.
					"`REGISTRATION_KEY`='"	. $key	. "' "		.
				 "LIMIT 1";
		$stmt= Database::getInstance()->getConnection()->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ok = true;
			$stmt->close();
		}
		return $ok;
	}
	
	/**
	 * Fonction utilisé lors de l'activation d'un compte : Elle permet de vérifier si le compte est déjà activé.
	 */
	public static function checkAccessActivated($id, $dt, $key) {
		$loginid = null;
		
		// Get loginid associated to the registration request
		$query = "SELECT `LOGINID` FROM `user_registration` WHERE "	.
					"`ID`='"					. $id	. "' AND "		.
					"`HORODATAGE`='"			. $dt	. "' AND "		.
					"`REGISTRATION_KEY`='"	. $key	. "' "		.
				 "LIMIT 1";
		$stmt= Database::getInstance()->getConnection()->query($query);
		if (is_object($stmt))
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$loginid = $res[0];
		if( $loginid == null )
			return false;
		
		// Check if user is activated
		$ok = false;
		$query = "SELECT `LOGINID` FROM `user` WHERE `ACTIVE`='1' AND `LOGINID`='$loginid' LIMIT 1";
		$stmt= Database::getInstance()->getConnection()->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ok = true;
			$stmt->close();
		}
		return $ok;
	}
	
	/**
	 * Fonction utilisé lors de l'activation d'un compte : Elle permet d'activer un compte et d'enregistrer le 'primo' mot de passe du compte.
	 */
	public static function activateAccess($id, $dt, $key, $pwd) {
		$loginid = null;
		
		// Get loginid associated to the registration request
		$query = "SELECT `LOGINID` FROM `user_registration` WHERE "	.
					"`ID`='"				. $id	. "' AND "		.
					"`HORODATAGE`='"		. $dt	. "' AND "		.
					"`REGISTRATION_KEY`='"	. $key	. "' "		.
				 "LIMIT 1";
		$stmt= Database::getInstance()->getConnection()->query($query);
		if (is_object($stmt))
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$loginid = $res[0];
		if( $loginid == null )
			return false;
		 
		// Activate the user access and update password (in table <tt>user</tt>)
		$query = "UPDATE `user` SET `ACTIVE`='1', `PASSWORD`=SHA1('$pwd') WHERE `LOGINID`='$loginid'";
		Database::getInstance()->getConnection()->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		
		// Activate the mail used for the activation request
		$query = "UPDATE `user_registration` SET `ACTIVE`='1' WHERE `ID`='$id'";
		Database::getInstance()->getConnection()->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		
		return true;
	}
	
	/**
	 * Fonction utilisé lors de l'activation d'un compte : Elle permet d'enregistrer la validation de l'adresse mail associé à la demande d'activation.
	 */
	public static function activateUserRegistration($id, $dt, $key) {
		if( !Self::checkKey($id, $dt, $key)  ) return;
		
		// Activate the mail used for the activation request
		$query = "UPDATE `user_registration` SET `ACTIVE`='1' WHERE `ID`='$id'";
		Database::getInstance()->getConnection()->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	/**
	 * Fonction utilisé pour la mise à jour du mot de passe.
	 */
	public static function changePassword($loginid, $actualPwd, $pwd, $pwdConf) {
		if( strlen(trim($loginid)) == 0 )
			return "Identifiant non valide";
		
		// Vérification pwd actuel
		$ok = false;
		$query = "SELECT `LOGINID` FROM `user` WHERE `LOGINID`='$loginid' AND `PASSWORD`=SHA1('$actualPwd')";
		$stmt= Database::getInstance()->getConnection()->query($query);
		if (is_object($stmt))
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ok = true;
		if( !$ok )
			return "Le &quot;Mot de passe actuel&quot; n'est pas conforme.";
		
		// Vérification nouveau password
		if( $pwd != $pwdConf )
			return "Le champ &quot;Mot de passe&quot; et le champ &quot;Confirmation Mot de passe&quot; doivent &ecirc;tre identiques.";

		// Mise à jour du nouveau password (in table <tt>user</tt>)
		$query = "UPDATE `user` SET `ACTIVE`='1', `PASSWORD`=SHA1('$pwd') WHERE `LOGINID`='$loginid'";
		Database::getInstance()->getConnection()->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		
		return "";
	}
	
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private static function insertUserRegistration($loginid, $dt, $mail, $key) {
		$query = "INSERT INTO `user_registration` (`LOGINID`, `HORODATAGE`, `MAIL`, `REGISTRATION_KEY`, `ACTIVE`) ".
				 "VALUES ('$loginid', '$dt', '$mail', '$key', '0')";
//		echo "query=$query<br>";
		$mysqli = Database::getInstance()->getConnection();
		$mysqli->query($query);
		$id = $mysqli->insert_id;
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		return $id;
	}
	
	/**************************************************************************
	 * Private Functions - Mail functions
	 **************************************************************************/
	private static function sendRegistrationMail($loginid, $gender, $name, $firstname, $mailAdr, $activationUrl) {
		
		/* Create a new PHPMailer object. Passing TRUE to the constructor enables exceptions. */
		$mail = new PHPMailer(TRUE);

		/* Open the try/catch block. */
		try {
			
			/* Set the mail sender. */
			$mail->setFrom(ParameterManager::getInstance()->email, 'Ecole Nouvelle');

			$mail->isSMTP();
			/*$mail->Host = 'smtp.mailtrap.io';
			$mail->SMTPAuth = true;
			$mail->Username = '665cb4d268f3c7';
			$mail->Password = 'a6b7e7a09774dd';
			$mail->SMTPSecure = 'tls';
			$mail->Port = 25;*/
			
			$mail->Host			= ParameterManager::getInstance()->smtpHost;
			$mail->SMTPAuth		= true;
			$mail->Username		= ParameterManager::getInstance()->smtpUsername;
			$mail->Password		= ParameterManager::getInstance()->smtpPassword;
			$mail->SMTPSecure	= ParameterManager::getInstance()->smtpSecure;
			$mail->Port			= ParameterManager::getInstance()->smtpPort;

			$mail->CharSet		= 'UTF-8';  

			/* Add a recipient. */
			$mail->addAddress($mailAdr, $firstname . ' ' . $name);

			/* Set the subject. */
			$mail->Subject = 'Ecole Nouvelle d\'Antony - Création de votre compte';

			$mail->isHTML(true);

			/* Set the mail message body. */
			$mail->Body = '
				<html>
					<head>
						<title>Ecole Nouvelle d\'Antony - Création de votre compte</title>
					</head>
					<body>
						<span style="font-size:12px; font-family:arial,helvetica,sans-serif">
							<p>Bonjour ' . $gender . ' ' . $firstname . ' ' . $name . ',</p>
							<p>Un compte d\'accès au Portail Famille de l\'Ecole Nouvelle vient de vous être créé :
							<li>Votre identifiant : <b>' . $loginid . '</b>
							</p>
							<p>Pour l\'activer et valider votre adresse mail, veuillez cliquer sur le lien suivant : <a href="' . $activationUrl . '">' . $activationUrl . '</a>.</p>
							<p>Une fois le compte activé, vous serez invité à définir votre mot de passe.</p>
							<p>Restant à votre écoute, nous vous prions d\'agréer l\'expression de nos sincères salutations.</p>
							<p>CRPA</p>
						</span>
					</body>
				</html>';

			/* Finally send the mail. */
			$mail->send();
		} catch (Exception $e) {
			echo $e->errorMessage();
		} catch (\Exception $e){
			echo $e->getMessage();
		}
	}
}
?>