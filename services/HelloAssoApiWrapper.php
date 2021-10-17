<?php
require_once('../services/Database.php');
require_once('../services/ParameterManager.php');
require_once('../services/InvoiceManager.php');

require_once('../pear/Net/URL2.php');
require_once('../pear/PEAR/Exception.php');
require_once('../pear/HTTP/Request2.php');
require_once('../pear/HTTP/Request2/Adapter.php');
require_once('../pear/HTTP/Request2/Exception.php');
require_once('../pear/HTTP/Request2/ConnectionException.php');
require_once('../pear/HTTP/Request2/SocketWrapper.php');
require_once('../pear/HTTP/Request2/Response.php');

/**
 * Inspire from following source :
 * https://github-dotcom.gateway.web.tr/HelloAsso/checkout-sample/blob/main/Services/HelloAssoApiWrapper.php
 */
class HelloAssoApiWrapper {
	
    private $clientId		= "";
    private $clientSecret	= "";
    private $organismSlug	= "";
	private $access_token	= "";
	
    private $baseUrl		= "";
    private $returnUrl		= "";

	public function __construct() {
		
		$baseurl			= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
		$requestUri				= substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'],'/'.basename($_SERVER['REQUEST_URI'])));
		$this->baseUrl		= $baseurl . $requestUri . "/linv";
		$this->returnUrl		= $baseurl . $requestUri . "/payr";
		
		$this->organismSlug		= ParameterManager::getInstance()->organismSlug;
		$this->clientId			= ParameterManager::getInstance()->clientId;
		$this->clientSecret		= ParameterManager::getInstance()->clientSecret;
	}

	/**
 	 * Get all values posted from submitted form, store them in model then call api wrapper
	 */
	public function sendPaidInvoiceForm($invoiceId) {
		
		// Load and check invoiceId
		$invoice = InvoiceManager::getInstance()->loadInvoice($invoiceId);
		if( $invoice == null)
			return;
		
		// Intialize token
		$this->_initToken();
//		if( !$this->_initToken() )
//			return;
		
		// Set the orderItem entity for API HelloAsso Checkout
		$orderItem = new HelloAssoOrderItemEntity();
		$orderItem->id 			= $invoice->invoiceId;
		$orderItem->firstname	= $invoice->name1;
		$orderItem->lastname	= $invoice->name2;
        $orderItem->email		= $invoice->mail1;
        $orderItem->address		= $invoice->adress;
        $orderItem->zipcode		= $invoice->cp;
        $orderItem->city 		= $invoice->city;
        $orderItem->country		= 'FRANCE';
        $orderItem->amount		= $invoice->toPayAmount;
		$orderItem->familyId	= $invoice->familyId;
		
        // Call API
        $response = $this->_initCart($orderItem);
		var_dump($response);

		if( isset($response->redirectUrl) ) {
			
			// Store checkout
			$this->_saveCheckoutIntentId($response->checkoutIntentId, $invoice->invoiceId, 0);		// 0=Initial goto redirectUrl

			// then redirect to HelloAsso
			header('Location:' . $response->redirectUrl);
			exit();
			
		} elseif( isset( $response->message ) ) {
			$orderItem->error = $response->message;
			return ['form', $orderItem];
			
		} elseif( isset( $response->error ) ) {
			$orderItem->error = $response->error;
			return ['form', $orderItem];
			
		} else {
			$orderItem->error = "Une erreur inconnue s'est produite";
			return ['form', $orderItem];
		}
	}
	
	/**
	 * Call by HelloAsso after payment
	 */
	public function parseReturn($familyId) {
		$checkoutIntentId	= "";
		$type				= "";
		$code				= "";
		$error				= "";
		
		if( isset($_GET['checkoutIntentId']) )		$checkoutIntentId	= $_GET['checkoutIntentId'];
		if( isset($_GET['type']) )					$type 				= $_GET['type'];
		if( isset($_GET['code']) )					$code 				= $_GET['code'];
		if( isset($_GET['error']) )					$error 				= $_GET['error'];
		
		if( $checkoutIntentId == "" || ($type != "return" && $type != "error") )
			return;
		if( $this->_isExistingCheckoutIntentId($checkoutIntentId) == false )
			return;
		
		if( $this->_canAccessToInvoice($checkoutIntentId, $familyId) == false ) {
			echo("Access denied !! You are not authorised to access this feature !");
			return;
		}
		
		$this->_saveCheckoutIntentId($checkoutIntentId, -1, 1, $code, $error);		// 1=Waiting payment confirmation
	}

	/**
	 * Call HelloAsso API to initialize token
	 * If ok this function save the token id
	 * Else an error code
	 */
	private function _initToken() {
		$request = new HTTP_Request2();
		$request->setUrl('https://api.helloasso.com/oauth2/token'); 
		$request->setMethod(\HTTP_Request2::METHOD_POST);
		$request->setHeader(array(
			'Content-Type'	=> 'application/x-www-form-urlencoded',
		));
		$request->addPostParameter(array(
			'grant_type'	=> 'client_credentials',
			'client_id'		=> $this->clientId,
			'client_secret'	=> $this->clientSecret
		));
		
		// To Fix the SSL issue
		$request->setConfig(array(
			'ssl_verify_peer'   => FALSE,
			'ssl_verify_host'   => FALSE
		));
		
		var_dump($request);

		try
		{
			$response = $request->send();
			var_dump($response);
			
			if ($response->getStatus() == 200) {
				$token = json_decode($response->getBody());
				var_dump($token);
				$this->access_token = $token->access_token;
				var_dump($this->access_token);
				return true;
			}
			else {
				echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' . $response->getReasonPhrase();
				return false;
			}
		}
		catch(\HTTP_Request2_Exception $e) {
			echo 'Error: ' . $e->getMessage();
			return false;
		}
	}
	

	/**
	 * Call HelloAsso API to initialize checkout
	 * If ok this function return raw response
	 * Else an error code
	 */
	private function _initCart($data) {
		$request = new HTTP_Request2();
		$request->setUrl('https://api.helloasso.com/v5/organizations/' . $this->organismSlug . '/checkout-intents'); 
		$request->setMethod(\HTTP_Request2::METHOD_POST);
		$request->setHeader(array(
			'authorization'		=> 'Bearer ' . $this->access_token,
			'Content-Type'		=> 'application/json',
		));
		
		// To Fix the SSL issue
		$request->setConfig(array(
			'ssl_verify_peer'   => FALSE,
			'ssl_verify_host'   => FALSE
		));

		$body = array(	'totalAmount'		=> round($data->amount * 100), 
						'initialAmount'		=> round($data->amount * 100), 
						'itemName'			=> 'Reglement CRPA', 
						'backUrl'			=> $this->baseUrl, 
						'errorUrl'			=> $this->returnUrl . "?type=error", 
						'returnUrl'			=> $this->returnUrl . "?type=return", 
						'containsDonation'	=> false,
						
						'payer'				=> array(
							'firstName'		=> $data->firstname,
							'lastName'		=> $data->lastname,
							'email'			=> $data->email,
							'address'		=> $data->address,
							'city'			=> $data->city,
							'zipCode'		=> $data->zipcode,
							'country'		=> $data->country,
						),
						'metadata'			=> array(
							'invoiceId'		=> $data->id,
							'familyId'		=> $data->familyId,
						)
				);

		$request->setBody(json_encode($body));
		
		var_dump($request);

		try{
			$response = $request->send();
			return json_decode($response->getBody());
		
		} catch(\Exception $e){
			return json_decode('{"error":"' . $e . '"}');
		}
	}
	
	private static function _canAccessToInvoice($checkoutIntentId, $familyId) {
		
		$msqli = Database::getInstance()->getConnection();
		
		// Select request
		$query = "SELECT `helloasso`.`ID`, `helloasso`.`INVOICE_ID`, `facture`.`ID_FAMILLE` 
		          FROM `helloasso`
				  LEFT JOIN `facture` ON `helloasso`.`INVOICE_ID`=`facture`.`ID`
				  WHERE `helloasso`.`ID`=$checkoutIntentId 
				    AND `facture`.`ID_FAMILLE`=$familyId
				 ";
		echo "query=$query<br/>";
		$stmt = $mysqli->query($query);
		$authorized = false;
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$authorized = true;
			$stmt->close();
		}
		return $authorized;
	}
	
	private static function _isExistingCheckoutIntentId($checkoutIntentId) {
		
		$msqli = Database::getInstance()->getConnection();
		
		// Select request
		$query = "SELECT `ID` FROM `helloasso` WHERE `ID`=$checkoutIntentId ";
		$stmt = $mysqli->query($query);
		$existing = false;
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$existing = true;
			$stmt->close();
		}
		return $existing;
	}
	
	private static function _saveCheckoutIntentId($checkoutIntentId, $invoiceId, $status, $code="", $error="") {
		
		$msqli = Database::getInstance()->getConnection();
		
		// Check if existing checkouIntentId
		$existing = $this->_isExistingCheckoutIntentId($checkoutIntentId);
		
		// Set datetime and user
		$dt  = date('YmdHis', time());
		$user = "unknown";
		if( isset($_SESSION ['user']) ) $user = $_SESSION ['user']->getUserId();
		
		// Insert or Update request
		if( $existing ) {
			$query = "UPDATE `helloasso` " .
					 " SET `DT`='" .		$dt 								. "', " .
					      "`USER`='" .		$user								. "', " .
						  "`STATUT`=" .		$status								. ", "  .		// 0=Initial goto redirectUrl; 1=Waiting payment confirmation; 2=Succeed; 3=Error
						  "`CODE`='" .		$msqli->real_escape_string($code)	. "', " .
						  "`ERROR`='" .		$msqli->real_escape_string($error)	. "'  " .
					 "WHERE `ID`=$id";
			$msqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		} else {
			$query = "INSERT INTO `helloasso`(`ID`, `DT`, `USER`, `INVOICE_ID`, `STATUT`, `CODE`, `ERROR`) " .
					 "VALUES (" .
					 "'" . $checkoutIntentId 									. "', " .
					 "'" . $dt													. "', " .
					 "'" . $user												. "', " .
						   $invoiceId 											. ", "  .
						   $status 												. ", "  .		// 0=Initial goto redirectUrl; 1=Waiting payment confirmation; 2=Succeed; 3=Error
					 "'" . $msqli->real_escape_string($code)					. "', " .
					 "'" . $msqli->real_escape_string($error)					. "')";
			$msqli->query($query);
			LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
		}
	}
}


/**
 * Entity class to manage OrderItem with HelloAsso API
 */
class HelloAssoOrderItemEntity {
	
	public $id;
	public $firstname;
	public $lastname;
	
	public $email;
	public $address;
	public $zipcode;
	public $city;
	public $country;
	public $amount;
	
	public $familyId;

	public $error;
}
?>