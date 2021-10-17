<?php
require_once('../services/Database.php');
require_once('../services/ParameterManager.php');

class InvoiceManager {
	
	
	/**************************************************************************
	 * Constantes
	 **************************************************************************/	
	const CODE_ACT_LBL	= array(
									"MATR" => "Surveillance Matin 8h-9h (avec réservation)",
									"MATU" => "Surveillance Matin 8h-9h (sans réservation)",
									"MATZ" => "Réservation Matin 8h-9h non honorée",
									"SOIR" => "Animation Soir 16h-18h (avec réservation)",
									"SOIU" => "Animation Soir 16h-18h (sans réservation)",
									"SOIZ" => "Réservation Soir 16h-18h non honorée",
									"MIDR" => "Surveillance Mercredi Midi 12h-13h (avec réservation)",
									"MIDU" => "Surveillance Mercredi Midi 12h-13h (sans réservation)",
									"MIDZ" => "Réservation Mercredi Midi 12h-13h non honorée",
									"REPR" => "Repas du Mercredi Midi (avec réservation)",
									"REPU" => "Repas du Mercredi Midi (sans réservation)",
									"REPZ" => "Réservation Repas Mercredi Midi non honorée",
									"APMR" => "Animation Mercredi Après-Midi (14h-18h) (avec réservation)",
									"APMU" => "Animation Mercredi Après-Midi (14h-18h) (sans réservation)",
									"APMZ" => "Réservation Mercredi Après-Midi (14h-18h) non honorée",
									"ACTR" => "Activité spécifique du Mercredi Après-Midi (14h-18h) (avec réservation)",
									"ACTU" => "Activité spécifique du Mercredi Après-Midi (14h-18h) (sans réservation)",
									"ACTZ" => "Réservation Activité Mercredi Après-Midi (14h-18h) non honorée",
									"PEN1" => "Pénalité de retard de 15 mn",
									"PEN2" => "Pénalité de retard de 30 mn",
									"PEN3" => "Pénalité de retard de 1h",
									"REMS" => "Remise sur la quantité d'unités de la période"
									);
	
	const QF_LST		= array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L");
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private static $_instance;			//The single instance
	 
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	private $periodId		= 0;		// type: int
	private $fromPeriod		= 0;		// type: int (8 digits - Ymd)
	private $toPeriod		= 0;		// type: int (8 digits - Ymd)
	private $titlePeriod	= "";		// type: String
	
	private $refQtyTable	= null;
	private $unitPriceTable	= null;
	
	public $msg_error		= "";		// type: String
	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	public static function getInstance() {
		if(!self::$_instance) { // If no instance then make one
			self::$_instance = new self();
			
			// Initialize
			self::$_instance->_initialize();
		}
		return self::$_instance;
	}

	public function generateInvoice($familyId, $withResa = false) {
		
		$this->msg_error = "";
		
		// Liste des releves
		$countActCodes = $this->_getReleveCount_ActCode($familyId);
		$countActCodes = array_merge($countActCodes, $this->_getReleveCount_Penalty($familyId));
		$countActCodes = array_merge($countActCodes, $this->_getReleveCount_UnfulfilledReservation($familyId));
		if( $withResa ) {
			$resaActCode = $this->_getReservationCount_ActCode($familyId);
			foreach( $resaActCode as $code => $qty ) {
				if( isset($countActCodes[$code]) )
					$countActCodes[$code] += $qty;
				else
					$countActCodes[$code] = $qty;
			}
		}
//		var_dump($countActCodes);

		if(empty($countActCodes)) 
			return null;

		// Initialize InvoiceEntity
		$invoice 				= new InvoiceEntity();
		$invoice->familyId		= $familyId;
		$this->_configureFamily($invoice);
		
		if( isset($this->unitPriceTable[$invoice->qf]) ) {
			$invoice->unitPrice	= $this->unitPriceTable[$invoice->qf];
		} else {
			$this->msg_error = "QF non défini pour la famille " . $invoice->familyId . " - " . $invoice->familyName ;
			return null;
		}
		$invoice->periodId		= $this->periodId;
		$invoice->title			= $this->titlePeriod;
		$invoice->fromDate		= $this->fromPeriod;
		$invoice->toDate		= $this->toPeriod;
		$invoice->createdDate	= intval(date('Ymd', time()));
		$invoice->toPayDate 	= intval(date('Ymd', time() + 3600 * 24 * ParameterManager::getInstance()->payment ));
		
//		var_dump($invoice);

		$totalUnitsQty			= 0;
		foreach( $countActCodes as $code => $qty ) {
			$item = new InvoiceItemEntity();
			$item->cod			= $code;
			$item->qty			= $qty;
			if( isset($this->refQtyTable[$code]) )
				$item->unitQty	= $this->refQtyTable[$code];
			$item->unitPrice	= round($item->unitQty * $invoice->unitPrice, 2);
			$item->amount		= round($item->qty * $item->unitPrice, 2);
//			var_dump($item);
			$invoice->amount	+= $item->amount;
			array_push($invoice->items, $item);
			
			// Traitement de la remise sur volume d'unités
			if( (substr($code, -1) == "R") || (substr($code, -1) == "U") ) {
				$c = substr($code, 0, 3) . "R";									// Nb d'unités de référence
				if( isset($this->refQtyTable[$c]) ) {
					$unitQty		= $this->refQtyTable[$c];
					$totalUnitsQty	+= $item->qty * $unitQty;
				}
			}
		}
		usort($invoice->items, "orderItemByCode");
		
		$rebateAmount = $this->_calculateVolumeRebate($totalUnitsQty, $invoice->amount);
		if( $rebateAmount > 0 ) {
			$item = new InvoiceItemEntity();
			$item->cod			= "REMS";
			$item->qty			= $totalUnitsQty;
			$item->unitPrice	= round(-1 * $rebateAmount / $totalUnitsQty, 2);
			$item->amount		= round(-1 * $rebateAmount, 2);
			$invoice->amount	+= $item->amount;
			array_push($invoice->items, $item);
		}
		
		$invoice->accountBalance	= $this->_getFamilyBalance($invoice->familyId);
		$invoice->toPayAmount		= $invoice->amount - $invoice->accountBalance;
		if( $invoice->toPayAmount < 0 )
			$invoice->toPayAmount	= 0.0;
		
		$invoice->status			= 0; 		// 0=ToPay | 1=Paid | 2=Report
		
		if( $invoice->toPayAmount == 0.0 )
			$invoice->status		= 1; 		// 0=ToPay | 1=Paid | 2=Report
		
//		var_dump($invoice);
		return $invoice;
	}
	
	public function closeInvoicePeriod() {
		
		// Step1. Génération des factures
		$msg_error = "";
		$famList = $this->_listFamilies();
		$invoices = array();
		foreach( $famList as $famId ) {
			$inv = $this->generateInvoice($famId, true);
			if( $inv == null && $this->msg_error != "" ) {
				$msg_error .= $this->msg_error . "<br/>";
			} elseif( $inv != null ) {
				array_push($invoices, $inv);
			}
		}
		$this->msg_error = $msg_error;
		if( $this->msg_error != "" )
			return;
		
		// Step2. Enregistrement des factures en DB
		foreach( $invoices as $inv ) {
			if( $inv->amount > 0) {
				$this->_insertInvoice($inv);
				$this->_insertAccountingRecord($inv->invoiceId, $inv->familyId, $inv->amount);
			}
		}
			
		// Step3. Changement de la période active
		$this->_closeCurrentInvoicePeriod();
	}
	
	/**
	 * Renvoie le Solde d'une famille
	 */
	public function getFamilyBalance($familyId) {
		return $this->_getFamilyBalance($familyId);
	}
	
	/**
	 * Renvoie le nb de facture à régler pour une famille
	 */
	public function getInvoiceNbToPay($familyId) {
		return $this->_getInvoiceNbToPay($familyId);
	}
	
	public function updatePaidAmount($invoiceId, $paidAmount) {
		return $this->_updatePaidAmount($invoiceId, $paidAmount);
	}
	
	public function insertPaymentAccountingRecord($invoiceId, $familyId, $paidAmount, $comment, $userId="") {
		return $this->_insertCreditAccountingRecord($invoiceId, $familyId, $paidAmount, $comment, $userId);
	}
	
	/**************************************************************************
	 * Private Functions
	 **************************************************************************/
	private function _initialize() {
		
		// Initialize database connection
		$this->db				= Database::getInstance();
		$this->mysqli			= $this->db->getConnection();
		
		// Load current invoicing period
		$this->_loadCurrentInvoicePeriod();
//		$this->_loadPricesTable();
		$this->refQtyTable		= ParameterManager::getInstance()->acPriceArray;
		$this->unitPriceTable	= ParameterManager::getInstance()->qfPriceArray;

//		var_dump($this);
	}
	
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function _loadCurrentInvoicePeriod() {
		$query = "SELECT `ID`, `TITRE`, `D_DEB`, `D_FIN` ".
				 "FROM `periode_facturation` " .
				 "WHERE `STATUT`=1 LIMIT 1";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->periodId		= $res[0];
				$this->titlePeriod	= $res[1];
				$this->fromPeriod	= $res[2];
				$this->toPeriod		= $res[3];
			}
		}
	}
	
	private function _getReleveCount_ActCode($familyId) {
		$countActCodes = array();
		$query = "SELECT `CODE_ACT`, `RESA`, COUNT(*) ".
				 "FROM `releve` WHERE "		.
					"`ID_FAMILLE`="			. $familyId			. " AND " .
					"`NUM_JOUR`>="			. $this->fromPeriod	. " AND " .
					"`NUM_JOUR`<="			. $this->toPeriod	. " AND " .
					"`OPT`<>1 AND `OPT`<>9 " .									// Gratuité et Réservation non honorée (traitée ci-après)
				 "GROUP BY `CODE_ACT`, `RESA`";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$codAct		= $res[0];
				$resa		= $res[1];
				$nb			= $res[2];
				$newCodAct	= $codAct;
				if( $resa == 1 )	$newCodAct .= "R";
				else				$newCodAct .= "U";
				$countActCodes[$newCodAct] = intval($nb);
			}
			$stmt->close();
		}
		return $countActCodes;
	}
	
	private function _getReleveCount_Penalty($familyId) {
		$countActCodes = array();
		$query = "SELECT `OPT`, COUNT(*) ".
				 "FROM `releve` WHERE "		.
					"`ID_FAMILLE`="			. $familyId			. " AND " .
					"`NUM_JOUR`>="			. $this->fromPeriod	. " AND " .
					"`NUM_JOUR`<="			. $this->toPeriod	. " AND " .
					"`OPT`>1 AND `OPT`<5 "	.								// Uniquement les pénalités 2 (15mn), 3 (30mn) et 4 (1h)
				 "GROUP BY `OPT`";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$penaltyLv	= $res[0];
				$nb			= $res[1];
				$newCodAct	= "PEN" . ($penaltyLv - 1);
				$countActCodes[$newCodAct] = intval($nb);
			}
			$stmt->close();
		}
		return $countActCodes;
	}
	
	private function _getReleveCount_UnfulfilledReservation($familyId) {
		$countActCodes = array();
		$query = "SELECT `CODE_ACT`, COUNT(*) ".
				 "FROM `releve` WHERE " 	.
					"`ID_FAMILLE`="			. $familyId			. " AND " .
					"`NUM_JOUR`>="			. $this->fromPeriod	. " AND " .
					"`NUM_JOUR`<="			. $this->toPeriod	. " AND " .
					"`OPT`=9 "				.									// Uniquement les réservation non honorée
				 "GROUP BY `CODE_ACT`, `RESA`";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$codAct		= $res[0];
				$nb			= $res[1];
				$newCodAct	= $codAct;
				$newCodAct .= "Z";
				$countActCodes[$newCodAct] = intval($nb);
			}
			$stmt->close();
		}
		return $countActCodes;
	}
	
	private function _getReservationCount_ActCode($familyId) {
		$maxReleveDate = 0;
		$query = "SELECT MAX(`NUM_JOUR`) ".
				 "FROM `releve` WHERE " .
					"`ID_FAMILLE`="		. $familyId			. " AND " .
					"`NUM_JOUR`>="		. $this->fromPeriod	. " AND " .
					"`NUM_JOUR`<="		. $this->toPeriod	. "";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$maxReleveDate = intval($res[0]);
			$stmt->close();
		}
		
		$countActCodes = array();
		$query = "SELECT `reservation`.`CODE_ACT`, COUNT(*) ".
				 "FROM `reservation` " .
				 "LEFT JOIN `enfant` ON `enfant`.`ID` = `reservation`.`ID_ENFANT` " .
				 "WHERE " .
					"`enfant`.`ID_FAMILLE`="		. $familyId			. " AND " .
					"`reservation`.`NUM_JOUR`>"		. $maxReleveDate	. " AND " .
					"`reservation`.`NUM_JOUR`<="	. $this->toPeriod	. " " .
				 "GROUP BY `reservation`.`CODE_ACT`";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$codAct		= $res[0];
				$nb			= $res[1];
				$newCodAct	= $codAct . "R";
				if( $codAct != "LIB" )
					$countActCodes[$newCodAct] = intval($nb);
			}
			$stmt->close();
		}
		return $countActCodes;
	}
	
	private function _calculateVolumeRebate($totalUnitsQty, $amount) {
		if( $totalUnitsQty < ParameterManager::getInstance()->threshold1 )
			return 0.0;
		elseif( $totalUnitsQty < ParameterManager::getInstance()->threshold2 )
			return $amount * ParameterManager::getInstance()->rebate1 / 100.0;
		elseif( $totalUnitsQty < ParameterManager::getInstance()->threshold3 )
			return $amount * ParameterManager::getInstance()->rebate2 / 100.0;
		elseif( $totalUnitsQty < ParameterManager::getInstance()->threshold4 )
			return $amount * ParameterManager::getInstance()->rebate3 / 100.0;
		elseif( $totalUnitsQty < ParameterManager::getInstance()->threshold5 )
			return $amount * ParameterManager::getInstance()->rebate4 / 100.0;
		else
			return $amount * ParameterManager::getInstance()->rebate5 / 100.0;
	}
	
	private function _configureFamily($invoice) {
		$query = "SELECT `NOM_FAMILLE`, " .
				 "`GENRE1`, `NOM1`, `PRENOM1`, `EMAIL1`, `ADRESSE1`, `CP1`, `VILLE1`, `REPLEG`, " .
				 "`GENRE2`, `NOM2`, `PRENOM2`, `EMAIL2`, " .
				 "`QF` " .
				 "FROM `famille` " .
				 "WHERE `ID`=" . $invoice->familyId . " LIMIT 1";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$invoice->familyName	= $res[0];
				$invoice->name1 		= strtoupper($res[1] . " " . $res[2]) . " " . ucfirst(strtolower($res[3])); 
				$invoice->adress 		= strtoupper($res[5]);
				$invoice->cp 			= strtoupper($res[6]);
				$invoice->city 			= strtoupper($res[7]);
				$invoice->mail1 		= $res[4];
				if( intval($res[8]) == 0 ) {
					$invoice->name2 		= strtoupper($res[9] . " " . $res[10]) . " " . ucfirst(strtolower($res[11])); 
					$invoice->mail2 		= $res[12];
				}
				$invoice->qf			= strtoupper($res[13]);
			}
			$stmt->close();
		}
	}
	
	private function _listFamilies() {
		$famList = array();
		$query = "SELECT `ID` FROM `famille`";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM))
				array_push($famList, intval($res[0]));
			$stmt->close();
		}
		return $famList;
	}
	
	private function _insertInvoice($invoice) {
		$query = "INSERT INTO `facture` (`TITRE`, `ID_FAMILLE`, `NOM_FAMILLE`, `NOM1`, `NOM2`, `ADRESSE`, `CP`, `VILLE`, `EMAIL1`, `EMAIL2`, `QF_FAMILLE`, " .
				 "`PRIX_UNITE`, `DATE_CREATION`, `DATE_PAIEMENT`, `DATE_LIMITE`, `ID_PERIODE`, `DATE_DEB`, `DATE_FIN`, `REMISE`, `MONTANT`, `SOLDE_FAMILLE`, `MONTANT_A_PAYER`, `MONTANT_REGLE`, `STATUT`) " .
				 "VALUES( " .
						"'" . DBUtils::toString($invoice->title)		. "', "	.
							  $invoice->familyId						. ", "	.
						"'" . DBUtils::toString($invoice->familyName)	. "', "	.
						
						"'" . DBUtils::toString($invoice->name1)		. "', "	.
						"'" . DBUtils::toString($invoice->name2)		. "', "	.
						"'" . DBUtils::toString($invoice->adress)		. "', "	.
						"'" . DBUtils::toString($invoice->cp)			. "', "	.
						"'" . DBUtils::toString($invoice->city)			. "', "	.
						"'" . DBUtils::toString($invoice->mail1)		. "', "	.
						"'" . DBUtils::toString($invoice->mail2)		. "', "	.
						
						"'" . $invoice->qf				. "', "	.
							  $invoice->unitPrice		. ", "	.
							  
							  $invoice->createdDate		. ", "	.
							  $invoice->paymentDate		. ", "	.
							  $invoice->toPayDate		. ", "	.
							  
							  $invoice->periodId		. ", "	.
							  $invoice->fromDate		. ", "	.
							  $invoice->toDate			. ", "	.
							  $invoice->rebate			. ", "	.
							  $invoice->amount			. ", "	.
							  $invoice->accountBalance	. ", "	.
							  $invoice->toPayAmount		. ", "	.
							  $invoice->paidAmount		. ", "	.
							  $invoice->status			. " "	.
						")";
//		echo "query=$query<br/>";
//		var_dump($invoice);
		$this->mysqli->query($query);
		$invoiceId = $this->mysqli->insert_id;
		$invoice->invoiceId = $invoiceId;
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
//		echo "invoiceId=$invoiceId<br/>";
		
		foreach( $invoice->items as $item ) {
			$query = "INSERT INTO `ligne_facture` (`ID_FACTURE`, `CODE`, `QTE`, `QTE_UNITE_PAR_ACT`, `PRIX_UNITAIRE`, `MONTANT`) " .
					 "VALUES( " .
								  $invoiceId		. ", "	.
							"'" . $item->cod		. "', "	.
								  $item->qty		. ", "	.
								  $item->unitQty	. ", "	.
								  $item->unitPrice	. ", "	.
								  $item->amount		. ")";
//			echo "query=$query<br/>";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function _closeCurrentInvoicePeriod() {
		$nvPeriodId = 0;
		$query = "SELECT `ID` ".
				 "FROM `periode_facturation` " .
				 "WHERE `STATUT`=2 AND `D_DEB`>" . $this->fromPeriod . " ORDER BY `D_DEB` LIMIT 1";
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if( $res = $stmt->fetch_array(MYSQLI_NUM) )
				$nvPeriodId = $res[0];
			$stmt->close();
		}
		
		$query = "UPDATE `periode_facturation` SET `STATUT`=0 WHERE `ID`=" . $this->periodId;
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
			
		if( $nvPeriodId != 0 ) {
			$query = "UPDATE `periode_facturation` SET `STATUT`=1 WHERE `ID`=" . $nvPeriodId;
//			echo "query=$query<br/>";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function _updatePaidAmount($invoiceId, $paidAmount) {
		$today = intval(date('Ymd', time()));
		$query = "UPDATE `facture` " .
				 "SET `MONTANT_REGLE`=`MONTANT_REGLE`+"	. $paidAmount	. ", " .
				     "`DATE_PAIEMENT`="					. $today		. " " .
				 "WHERE `ID`=" . $invoiceId;
//		echo "query=$query<br/>";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		
		
		$balance = -1.0;
		$query = "SELECT `MONTANT_REGLE` - `MONTANT_A_PAYER` ".
				 "FROM `facture` " .
				 "WHERE `ID`=" . $invoiceId;
//		echo "query=$query<br/>";
		$stmt= $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if( $res = $stmt->fetch_array(MYSQLI_NUM) )
				$balance = $res[0];
			$stmt->close();
		}
		
		// Mise à jour du STATUT
		if( $balance >= 0 ) {
			$query = "UPDATE `facture` " .
					 "SET `STATUT`=1 " .
					 "WHERE `ID`=" . $invoiceId;
//			echo "query=$query<br/>";
			$this->mysqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
	
	private function _insertAccountingRecord($invoiceId, $familyId, $amount, $userId="") {
		$today = intval(date('Ymd', time()));
		
		// Transferts de charge des factures non réglées
		$query = "SELECT `ID_FACTURE`, SUM(`DEBIT`) - SUM(`CREDIT`) AS `DIFF` " .
				 "FROM `ecriture` " .
				 "WHERE `ID_FAMILLE`=" . $familyId . " " .
				 "GROUP BY `ID_FACTURE` " .
				 "HAVING `DIFF` <> 0";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$inv = intval($res[0]);
				$bal = round(floatval($res[1]), 2);
				$this->_insertCreditAccountingRecord($inv, $familyId, $bal, "Transfert charges Facture $inv -> $invoiceId", $userId);
				$this->_insertDebitAccountingRecord($invoiceId, $familyId, $bal, "Transfert charges Facture $inv -> $invoiceId", $userId);
				$this->_setInvoiceToStatus2($inv);
			}
			$stmt->close();
		}
		
		// Débit de la nouvelle facture
		$this->_insertDebitAccountingRecord($invoiceId, $familyId, $amount, "Facture " . $invoiceId, $userId);
	}
	
	private function _insertDebitAccountingRecord($invoiceId, $familyId, $amount, $comment, $userId="") {
		$today = intval(date('Ymd', time()));
		$query = "INSERT INTO `ecriture` (`DATE`, `ID_FACTURE`, `ID_FAMILLE`, `CREDIT`, `DEBIT`, `COMMENTAIRE`, `LOGINID`) " .
				 "VALUES( " .
						"'" . $today 		. "', " .
						"'" . $invoiceId	. "', " .
						"'" . $familyId 	. "', '0', " .
						"'" . number_format($amount, 2, '.', '') . "', " .
						"'" . DBUtils::toString($comment)				. "', " .
						"'" . $userId									. "' " .
				 ")";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function _insertCreditAccountingRecord($invoiceId, $familyId, $amount, $comment, $userId="") {
		$today = intval(date('Ymd', time()));
		$query = "INSERT INTO `ecriture` (`DATE`, `ID_FACTURE`, `ID_FAMILLE`, `CREDIT`, `DEBIT`, `COMMENTAIRE`, `LOGINID`) " .
				 "VALUES( " .
						"'" . $today 		. "', " .
						"'" . $invoiceId	. "', " .
						"'" . $familyId 	. "', " .
						"'" . number_format($amount, 2, '.', '') . "', '0', " .
						"'" . DBUtils::toString($comment)				. "', " .
						"'" . $userId									. "' " .
				 ")";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function _setInvoiceToStatus2($invoiceId) {
		$today = intval(date('Ymd', time()));
		$query = "UPDATE `facture` SET `STATUT`=2 " .
				 "WHERE `ID`=" . $invoiceId;
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	/**
	 * Renvoie le Solde d'une famille
	 */
	private function _getFamilyBalance($familyId) {
		$balance = 0;
		$query = "SELECT COALESCE(SUM(`CREDIT`)-SUM(`DEBIT`), 0) " .
				 "FROM `ecriture` " .
				 "WHERE `ID_FAMILLE`=" . $familyId;
//		echo "query=$query<br/>";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$balance = floatval($res[0]);
			$stmt->close();
		}
		return $balance;
	}
	
	/**
	 * Renvoie le nb de facture à régler
	 */
	private function _getInvoiceNbToPay($familyId) {
		$nb = 0;
		$query = "SELECT COUNT(*) FROM `facture` " .
		         "WHERE `STATUT`=0 AND " .
				       "`ID_FAMILLE`=" . $familyId;
//		echo "query=$query<br/>";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$nb = intval($res[0]);
			$stmt->close();
		}
		return $nb;
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	public function loadInvoice($invoiceId) {
		$invoice = null;
		$query = "SELECT `TITRE`, `ID_FAMILLE`, `NOM_FAMILLE`, `QF_FAMILLE`, `PRIX_UNITE`, `DATE_CREATION`, `DATE_PAIEMENT`, `ID_PERIODE`, " .
				        "`DATE_DEB`, `DATE_FIN`, `REMISE`, `MONTANT`, `SOLDE_FAMILLE`, `MONTANT_A_PAYER`, `MONTANT_REGLE`, `STATUT` " .
				 "FROM `facture` " .
				 "WHERE `ID`=" . $invoiceId;
//		echo "query=$query<br/>";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$invoice 					= new InvoiceEntity();
				$invoice->invoiceId			= $invoiceId;
				$invoice->title				= $res[0];
				
				$invoice->familyId			= $res[1];
				$invoice->familyName		= $res[2];
				$invoice->qf				= $res[3];
				$invoice->unitPrice			= $res[4];
	
				$invoice->createdDate		= $res[5];
				$invoice->paymentDate		= $res[6];
			
				$invoice->periodId			= $res[7];
				$invoice->fromDate			= $res[8];
				$invoice->toDate			= $res[9];
	
				$invoice->rebate			= $res[10];
				$invoice->amount			= $res[11];
				$invoice->accountBalance	= $res[12];
				$invoice->toPayAmount		= $res[13];
				$invoice->paidAmount		= $res[14];
				
				$invoice->status			= $res[15];
			}
			$stmt->close();
		}
		return $invoice;
	}
}


/**************************************************************************
 * InvoiceEntity
 * 1 entité par facture : 1 facture pour 1 famille et par période de facturation
 **************************************************************************/
class InvoiceEntity {
	
	public $invoiceId		= 0;
	public $title			= "";
	
	public $familyId		= "";
	public $familyName		= "";
	public $name1			= "";
	public $name2			= "";
	public $adress			= "";
	public $cp				= "";
	public $city			= "";
	public $mail1			= "";
	public $mail2			= "";
	
	public $qf				= "";
	public $unitPrice		= 0.0;
	
	public $createdDate 	= 0;
	public $paymentDate 	= 0;
	public $toPayDate 		= 0;
		
	public $periodId		= 0;
	public $fromDate		= 0;
	public $toDate			= 0;
	
	public $rebate			= 0.0;
	public $amount			= 0.0;
	public $accountBalance	= 0.0;
	public $toPayAmount		= 0.0;
	
	public $paidAmount		= 0.0;
	public $status			= 0; 		// 0=ToPay | 1=Paid | 2=Report
	
	public $items			= array();
}

/**************************************************************************
 * InvoiceItemEntity
 * 1 entité par famille et par code sur toute la période de facturation
 **************************************************************************/
class InvoiceItemEntity {
	
	public $cod				= "";
	public $qty				= 0;
	public $unitQty			= 0.0;
	public $unitPrice		= 0.0;
	public $amount			= 0.0;
}

function orderItemByCode($a, $b) {
    return strcmp($a->cod, $b->cod);
}
?>