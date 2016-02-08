<?php /*
This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2014.
*/
$fileInfo = array('fileName' => 'includes/paymentAuthorizationMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'parseReturnUrl'){
	parseReturnUrl();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function parseReturnUrl(){
	 /*
	The user has authorized recurring payments at Amazon. Parse the url returned from Amazon CBUI. This will validate that the return Url came from Amazon. There are several checks performed here:
	1. The return url must have a query string with the following parameters:
		tokenID
		signatureMethod
		status
		signatureVersion
		signature
		certificateUrl
		expiry
		callerReference
	2. The signature of the returnUrl must be verified.
	3. The callerReference is a foreign key in the database. If it doesn't match existing records, this function will fail.
	We will check the tokenId and callerReference fields against the database to see if an entry with matching values already exists.
	Be aware that if this function is called via AJAX the query string will have to be passed via javascript.
	
	After validating the return url we make a pay request.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		$messageCenter = new Adrlist_MessageCenter();
		$success = MODE =='parseReturnUrl' ? true : $success;//We set success to true here because a failure below will change it to false.
		$errorMessage = 'We were unable to get a proper response from the payment processor.  No payments or charges have been made. Please return to <a href="' . LINKMYACCOUNT . '">My Account</a> and try again.<br>
<br>
If the problem persists please <a href="' . LINKSUPPORT . '">contact support</a>.';
		if(empty($_POST['returnUrl'])){
			throw new Adrlist_CustomException($errorMessage,'$_POST[\'returnUrl\'] is empty.');
		}else{
			$goodStatusCodes = array(
				'SA' => 'Success status for the ABT payment method.',
				'SB' => 'Success status for the ACH (bank account) payment method.',
				'SC' => 'Success status for the credit card payment method.'
			);
			$badStatusCodes = array(
				'SE' => 'System error.',
				'A'  => 'Buyer abandoned the pipeline.',
				'CE' => 'Specifies a caller exception.',
				'PE' => 'Payment Method Mismatch Error: Specifies that the buyer does not have payment method that you have requested.',
				'NP' => 'There are four cases where the NP status is returned:
1. The payment instruction installation was not allowed on the sender\'s account, because the sender\'s email account is not verified
2. The sender and the recipient are the same
3. The recipient account is a personal account, and therefore cannot
accept credit card payments
4. A user error occurred because the pipeline was cancelled and then
restarted',
				'NM' => 'You are not registered as a third-party caller to make this transaction. Contact Amazon Payments for more information.',
);
			$urlParts = parse_url($_POST['returnUrl']);
			$debug->printArray($urlParts,'$urlParts');
			parse_str($urlParts['query'],$queryArray);//Convert the url parameters into an associative array.
			$debug->printArray($queryArray,'$queryArray');
			if(empty($queryArray['callerReference'])){
				throw new Adrlist_CustomException($errorMessage,'$queryArray[\'callerReference\'] is empty.');
			}
			$utils = new Amazon_IpnReturnUrlValidation_SignatureUtilsForOutbound();
			$validate = $utils->validateRequest($queryArray, AUTOLINK . $_SERVER['PHP_SELF'], "GET");
			if($validate !== true){
				//Verify the signature of the payment processor.
				throw new Adrlist_CustomException($errorMessage,'Could not validate the signature of the payment processor for the return url. This is probably due to an error with the url parameters.');
			}elseif(empty($urlParts['query'])){
				//We must have a query from the url.
				throw new Adrlist_CustomException($errorMessage,'There was no query string returned from the payment processor.');
			}elseif(empty($queryArray['callerReference']) || !preg_match("/^\d+$/",$queryArray['callerReference'])){
				//Check the query for callerReference. Check callerReference against a regular expression.
				throw new Adrlist_CustomException($errorMessage,'There was a problem with $queryArray[\'callerReference\']: ' . $queryArray['callerReference']);
			}elseif(!array_key_exists($queryArray['status'],$goodStatusCodes)){
				throw new Adrlist_CustomException($errorMessage,'No good status codes were returned. ' . $queryArray['status'] . ': ' . $badStatusCodes[$queryArray['status']]);
			}else{
				//Get the billingOfferId.
				$billingOfferStmt = $Dbc->prepare("SELECT
	billingOfferId AS 'billingOfferId'
FROM
	userBillingActions
WHERE
	userBillingActions.userBillingActionId = ?");
				$billingOfferStmt->execute(array($queryArray['callerReference']));
				$billingOfferRow = $billingOfferStmt->fetch(PDO::FETCH_ASSOC);
				if(empty($billingOfferRow)){
					throw new Adrlist_CustomException($errorMessage,'No billingOfferId was returned. $queryArray[\'status\']: ' . $queryArray['status']);
				}
				//Add a billing action. The recurring payment authorization was successful.
				$userBillingActionId = Adrlist_Billing::addBillingAction($_SESSION['userId'],$billingOfferRow['billingOfferId'],2,1,__FILE__ . ' ' . __LINE__);
				//See if the request has already been inserted.
				$responseCheckStmt = $Dbc->prepare("SELECT
	amazonCBUIResponseId AS 'amazonCBUIResponseId'
FROM
	amazonCBUIResponses
WHERE
	callerReference = ? AND
	tokenId = ?");
				$responseCheckStmt->execute(array($queryArray['callerReference'],$queryArray['tokenID']));
				$responseRow = $responseCheckStmt->fetch(PDO::FETCH_ASSOC);
				if(empty($responseRow)){
					//There is no matching response, so insert the new response to the database. 
					//Convert expiry to Mysql date (YYYY-MM-DD) format. Both the original format and the converted format will be stored in the database.
					$expiryParts = explode('/',$queryArray['expiry']);
					if(preg_match('/\d{2}/',$expiryParts[0]) && preg_match('/\d{4}/',$expiryParts[1])){
						$expiryDatetime = $expiryParts[1] . '-' . $expiryParts[0] . '-01';
					}else{
						$expiryDatetime = '0000-00-00';
					}
					$amazonCBUIResponseStmt = "INSERT INTO
	amazonCBUIResponses
SET
	userBillingActionId = ?,
	callerReference = ?,
	certificateUrl = ?,
	aDatetime = ?,
	expiry = ?,
	expiryDate = ?,
	fullUrl = ?,
	signature = ?,
	signatureMethod = ?,
	signatureVersion = ?,
	aStatus = ?,
	tokenId = ?";
					$amazonCBUIResponseParams = array(
						$userBillingActionId,
						$queryArray['callerReference'],
						$queryArray['certificateUrl'],
						DATETIME,
						$queryArray['expiry'],
						$expiryDatetime,
						$_SERVER['REQUEST_URI'],
						$queryArray['signature'],
						$queryArray['signatureMethod'],
						$queryArray['signatureVersion'],
						$queryArray['status'],
						$queryArray['tokenID']
					);
					$debug->add('$amazonCBUIResponseStmt: ' . $amazonCBUIResponseStmt);
					$debug->printArray($amazonCBUIResponseParams,'$amazonCBUIResponseParams');
					$amazonCBUIResponseStmt = $Dbc->prepare($amazonCBUIResponseStmt);
					$amazonCBUIResponseStmt->execute($amazonCBUIResponseParams);
				}
				//Make a payment request.
				if(Adrlist_Billing::amazonPayRequest($queryArray['callerReference']) !== true){
					$success = false;
					throw new Adrlist_CustomException('','Adrlist_Billing::amazonPayRequest returned false.');
				}
				$returnThis['successUrl'] = LINKMYACCOUNT;
			}
		}
	}catch(Adrlist_CustomException $e){
		$success = false;
		$debug->add('<pre>' . $e . '</pre>');
		error(__LINE__,' ','');
		$messageCenter->newMessage(1,1,
			'Problem with Amazon Payments transaction',
			'',
			$debug->output()
		);
	}catch(PDOException $e){
		$success = false;
		$debug->add('<pre>' . $e . '</pre>');
		error(__LINE__,'','');
		$messageCenter->newMessage(1,1,
			'Problem with Amazon Payments transaction',
			'',
			$debug->output()
		);
	}
	if(MODE == 'parseReturnUrl'){
		$debug->add('$success: ' . $success);
		returnData();
	}
 }