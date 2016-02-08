<?php 
$fileInfo = array('title' => 'Amazon IPN Listener', 'fileName' => 'myAccount/amazonIPNListenerMethods.php');
$debug->newFile($fileInfo['fileName']);
/*
This file listens for IPN responses from Amazon Payments. It stores all responses in the database. Responses can be for pay requests, cancellations, changes, etc.
*/
try{
	$messageCenter = new Adrlist_MessageCenter();
	$debug->printArray($_REQUEST,'$_REQUEST');
	//Verify the response came from Amazon.
	$utils = new Amazon_IpnReturnUrlValidation_SignatureUtilsForOutbound();
	$validate = $utils->validateRequest($_POST, AUTOLINK . $_SERVER['PHP_SELF'], "POST");
	$myFile = __DIR__ . '/../CustomLogs/amazonIPNListener' . __LINE__ . '.txt';
	$fh = fopen($myFile, 'w');
	fwrite($fh,'$_POST values: ' . $debug->printArrayOutput($_POST));
	/*
	As this script is ran upon receipt of an IPN response, it will not produce results visible to the end user. We must therefore deliver messages to the end user via the message center.	
	*/
	if($validate !== true){
		$debug->printArray($validate,'$validate');
			$messageCenter->newMessage(1,1,
			'Problem with Amazon Payments transaction',
			'',
			"Could not validate the signature of the IPN response. It could be that it was not from Amazon, although it's more likely a problem with the ipn parameters used to validate. Also, verify that the logic used for this if statement matches the expected response from the $validate object." . ' On line ' . __LINE__ . '<br>' . $debug->output()
		);
	}elseif(empty($_REQUEST['operation'])){
		$messageCenter->newMessage(1,1,
			'Problem with Amazon Payments transaction',
			'',
			'$_REQUEST[\'operation\'] is empty. We must have an operation returned from Amazon before we can perform an action. On line ' . __LINE__ . '<br>' . $debug->output()
		);
	}else{
		//The possible operation values for IPN are:
		$operations = array(
			'PAY'				 => "All pay transactions.",
			'REFUND'			 => "All refund transactions.",
			'SETTLE'			 => "All settle transactions.",
			'SETTLE'			 => "All reserve transactions.",
			'MULTI_SETTLE'		 => "All multi-settle transactions.",
			'REAUTH'			 => "All transactions that required reauthorization.",
			'DEPOSIT_FUNDS'		 => "All fund deposit transactions.",
			'WITHDRAW_FUNDS'	 => "All fund withdrawal transactions.",
			'CANCEL_TRANSACTION' => "All non-user cancelled transactions.",
			'CANCEL'			 => "All non-user cancelled transactions."
		);
		//While there are two status codes returned for a pay response, statusCode and transactionStatus, only transaction Status is returned for all IPN transactions. Therefore we will use it for verification of a successful transaction. The possible transaction status values are:
		$transactionStatusArray = array(
			'CANCELLED'	=> array(
				'userMessage'=> "The transaction has been cancelled. If this result is unexpected, please try again.",
				'adminMessage'=>"The transaction was canceled."
			),
			'FAILURE'	=> array(
				'userMessage'=> "The transaction failed. Please try again. If it continues to fail you should try a different payment method or account.",
				'adminMessage'=>"The transaction failed. The API operation failed and Amazon FPS did not receive or record a transaction. You can retry the transaction only if a retriable error has been returned."
			),
			'PENDING'	=> array(
				'userMessage' => "Your payment is currently pending. Please wait while the payment finishes processing. This should only take a few seconds.",
				'adminMessage'=> "The transaction is pending."
			),
			'RESERVED'	=> array(
				'userMessage' => "A reserve has been made on your payment method.",
				'adminMessage'=> "The reserve request on the transaction succeeded. Amazon FPS reserves the purchase price against the sender's payment instrument."
			),
			'SUCCESS'	=> array(
				'userMessage' => "",
				'adminMessage'=> "The transaction succeeded. You can fulfill the order for the customer."
			)
		);
		if($_REQUEST['operation'] == 'PAY'){
			if(empty($_REQUEST['callerReference'])){
				$messageCenter->newMessage(1,1,
					'Failed Amazon Payments transaction needs attention',
					'see admin note',
					'There was a failure when an IPN response was sent from Amazon Payments. $_REQUEST[\'callerReference\'] was empty. That value is required to continue and process the payment.<br>
		<br>
		Debug follows: ' . $debug->output()
				);
			}else{
				//We know the callerReference. Get the billingOfferId, userId, and plan information.
				$planStmt = $Dbc->prepare("SELECT
	billingOffers.billingOfferId AS 'billingOfferId',
	billingOffers.offerName AS 'name',
	billingOffers.billingType AS 'type',
	billingOffers.terms AS 'terms',
	billingOffers.price AS 'price',
	billingOffers.offerLength AS 'length',
	billingOffers.period AS 'period',
	billingOffers.credits AS 'credits',
	userBillingActions.userId AS 'userId'
FROM
	amazonCBUIResponses
JOIN
	userBillingActions ON userBillingActions.userBillingActionId = amazonCBUIResponses.userBillingActionId
JOIN
	billingOffers ON billingOffers.billingOfferId = userBillingActions.billingOfferId
WHERE
	amazonCBUIResponses.callerReference = ?");
				$planParams = array($_REQUEST['callerReference']);
				$planStmt->execute($planParams);
				$planRow = $planStmt->fetch(PDO::FETCH_ASSOC);
				$debug->printArray($planRow,'$planRow');
				if(empty($planRow)){
					pdoError(__LINE__, $planStmt, $planParams, true);
					throw new Adrlist_CustomException('','No plan information was found for the pay request.');
				}
				if($_REQUEST['transactionStatus'] == 'FAILURE' || $_REQUEST['transactionStatus'] == 'CANCELLED' || $_REQUEST['transactionStatus'] == 'RESERVED'){
					$messageCenter->newMessage(
						1,
						$planRow['userId'],
						'An Amazon Payments transaction needs your attention',
						$transactionStatusArray[$_REQUEST['transactionStatus']]['userMessage'],
						$transactionStatusArray[$_REQUEST['transactionStatus']]['adminMessage'] . '
Debug follows: ' . $debug->output()
					);
				}else{
					if($_REQUEST['transactionStatus'] == 'PENDING'){
						$billingActionId = 3;
					}elseif($_REQUEST['transactionStatus'] == 'SUCCESS'){
						$billingActionId = 4;
					}
					//Add a payment billing action.
					$userBillingActionId = Adrlist_Billing::addBillingAction($planRow['userId'],$planRow['billingOfferId'],$billingActionId,1,__FILE__ . ' ' . __LINE__);
					$transactionAmount = explode(' ',$_REQUEST['transactionAmount']);
					$transactionAmount = $transactionAmount[1];
					//$transactionAmount = preg_split('/USD /', $_REQUEST['transactionAmount'], -1);
					$debug->add('$transactionAmount: ' . $transactionAmount);
					//See if the user has a record in userBilling.
					if($billingActionId == 4){
						//Payment was successful.
						//If the user currently has a plan, issue a refund.
						$calculateRefund = Adrlist_Billing::calculateRefund($planRow['userId']);
						if(is_array($calculateRefund)){
							Adrlist_Billing::amazonRefundRequest($calculateRefund['userPlanArray']['userBillingActionId'],'Pro-rated refund for ' . $calculateRefund['userPlanArray']['name'],$calculateRefund['refundAmount']);
						}else{
							$redirect = false;
						}
						if(Adrlist_Billing::getUserPlan($planRow['userId']) === false){
							//The user has no record in userBiling.
							$userBillingStmt = $Dbc->prepare("INSERT INTO
	userBilling
SET
	userId = ?,
	userBillingActionId = ?,
	billingOfferId = ?,
	dateAdded = ?");
							$userBillingParams = array($planRow['userId'],$userBillingActionId,$planRow['billingOfferId'],DATETIME);
						}else{
							//The user has a record in userBilling. Update it with the new plan info.
							$userBillingStmt = $Dbc->prepare("UPDATE
	userBilling
SET
	billingOfferId = ?,
	dateAdded = ?
WHERE
	userId = ?
");
							$userBillingParams = array($planRow['billingOfferId'],DATETIME,$planRow['userId']);
						}
						$userBillingStmt->execute($userBillingParams);
						$messageCenter->newMessage(
							1,
							$planRow['userId'],
							'Thank you for your purchase!','Thank you for your purchase. Your account has been credited according to the plan you chose.<br>
<br>
<span class="bold textMedium">Plan Details:</span>
' . ucfirst($planRow['type']) . ': ' . $planRow['name'] . '<br>
Price: ' . $planRow['price'] . '<br>
Credits: ' . $planRow['credits'] . '<br>
Billing Period: ' . $planRow['length'] . ' ' . $planRow['period'] . '<br>
The terms of the plan are available in the Billing section of <a href="' . LINKMYACCOUNT . '">My Account</a><br>
<br>
If you have any other questions please <a href="' . LINKSUPPORT . '">contact support</a>.',
					'This is where it is supposed to end.<br>
made it to line ' . __LINE__ . '<br>
<br>
Debug follows: ' . $debug->output()
						);
					}
				}
			}
		}elseif($_REQUEST['operation'] == 'REFUND'){
			if(empty($_REQUEST['transactionId'])){
				$messageCenter->newMessage(1,1,
					'Failed Amazon Payments transaction needs attention',
					'see admin note',
					'There was a failure with an IPN refund response sent from Amazon Payments. $_REQUEST[\'transactionId\'] was empty. That value is required to continue and process the payment.<br>
		<br>
		Debug follows: ' . $debug->output()
				);
			}else{
				//A refund IPN returns transactionId and transactionStatus. Get the billingOfferId and userId.
				$billingStmt = $Dbc->prepare("SELECT
	userBillingActions.userId AS 'userId',
	userBillingActions.billingOfferId AS 'billingOfferId'
FROM
	userBillingActions
JOIN
	amazonIPNListener ON amazonIPNListener.userBillingActionId = userBillingActions.userBillingActionId AND
	amazonIPNListener.transactionId = ?
WHERE
	userBillingActions.billingActionId = 4");
				$billingParams = array($_REQUEST['parentTransactionId']);
				$billingStmt->execute($billingParams);
				$billingRow = $billingStmt->fetch(PDO::FETCH_ASSOC);
				$debug->printArray($billingRow,'$billingRow');
				if(empty($billingRow)){
					pdoError(__LINE__, $billingStmt, $billingParams, true);
					throw new Adrlist_CustomException('','No billing information was found for the refund request.');
				}
				if($_REQUEST['transactionStatus'] == 'FAILURE' || $_REQUEST['transactionStatus'] == 'CANCELLED' || $_REQUEST['transactionStatus'] == 'RESERVED'){
					$messageCenter->newMessage(
						1,
						$billingRow['userId'],
						'An Amazon Payments transaction needs your attention',
						$transactionStatusArray[$_REQUEST['transactionStatus']]['userMessage'],
						$transactionStatusArray[$_REQUEST['transactionStatus']]['adminMessage'] . '
Debug follows: ' . $debug->output()
					);
				}else{
					if($_REQUEST['transactionStatus'] == 'PENDING'){
						$billingActionId = 5;
					}elseif($_REQUEST['transactionStatus'] == 'SUCCESS'){
						$billingActionId = 6;
					}
					//Add a payment billing action.
					$userBillingActionId = Adrlist_Billing::addBillingAction($billingRow['userId'],$billingRow['billingOfferId'],$billingActionId,1,__FILE__ . ' ' . __LINE__);
					if($billingActionId == 6){
						$messageCenter->newMessage(
							1,
							$billingRow['userId'],
							'Refund','A refund was made to your account in the amount of ' . $_REQUEST['transactionAmount'] . '.<br>
<br>
If you have any questions about this refund please <a href="' . LINKSUPPORT . '">contact support</a>.',
					'This is where it is supposed to end.<br>
made it to line ' . __LINE__ . '<br>
<br>
Debug follows: ' . $debug->output()
						);
					}
				}
			}
		}
		if(!empty($userBillingActionId)){
			//Make an IPN record.
			$saveIpnStmt = "INSERT INTO
	amazonIPNListener
SET
	userBillingActionId = ?,
	aDatetime = ?,
	microtime = ?,
	debug = ?";
			$saveIpnParams = array($userBillingActionId,DATETIME,MICROTIME,$debug->output());
			$ipnParameterKeys = array(
				'buyerEmail',
				'buyerName',
				'callerReference',
				'certificateUrl',
				'customerEmail',
				'customerName',
				'dateInstalled',
				'notificationType',
				'operation',
				'parentTransactionId',
				'paymentMethod',
				'paymentReason',
				'recipientEmail',
				'recipientName',
				'signature',
				'signatureMethod',
				'signatureVersion',
				'statusCode',
				'statusMessage',
				'tokenId',
				'tokenType',
				'transactionAmount',
				'transactionDate',
				'transactionId',
				'transactionStatus'
			);
			$ipnParameters = array_fill_keys($ipnParameterKeys,'');
			$responseUrl = '';
			foreach($ipnParameters as $key => $value){
				if(isset($_REQUEST[$key])){
					$responseUrl .= '&' . $key . '=' . $_REQUEST[$key];
					if($_REQUEST[$key] == 'transactionDate'){
						//Convert transactionDate to mysql datetime format: YYYY-MM-DD HH:MM:SS.
						$transactionDatetime = date('Y-m-d H:i:s', $_REQUEST['transactionDate']);
						$saveIpnStmt .= ',
	transactionDatetime = ?';
						$saveIpnParams[] = $transactionDatetime;		
					}
					$saveIpnStmt .= ',
	' . $key . ' = ?';
					$saveIpnParams[] = $_REQUEST[$key];		
				}
			}
			$responseUrl = urlencode($responseUrl);
			$responseUrl = LINKMYACCOUNT . '/amazonIPNListener.php?mode=parseReturnUrl' . $responseUrl;
			$saveIpnParams[] = $responseUrl;
			$saveIpnStmt .= ',
		responseUrl = ?';
			$debug->add('$saveIpnStmt: ' . $saveIpnStmt);
			$debug->printArray($saveIpnParams,'$saveIpnParams');
			$saveIpnStmt = $Dbc->prepare($saveIpnStmt);
			$saveIpnStmt->execute($saveIpnParams);
		}else{
			messageCenter(1,1,'Problem with Amazon Payments transaction','Did not write to the IPN table. ' . $debug->output());
		}
	}
}catch(Adrlist_CustomException $e){
	$recipientId = empty($userId) ? 1 : $userId;
	$messageCenter->newMessage(
		1,
		$recipientId,
		'Problem with Amazon Payments transaction',
		'Recently there was a transaction attempted for your account. We encountered a problem while trying to process the transaction. If payment was attempted, we cannot verify that it was successful. Please check your Amazon Payments account for a transaction by visiting <a href="https://payments.amazon.com">payments.amazon.com</a>.<br>
<br>
If you have any questions please <a href="' . LINKSUPPORT . '">contact support</a>.',
		' On line ' . __LINE__ . '<br>' . $debug->output()
	);
}catch(PDOException $e){
	$Dbc = new Adrlist_Dbc(DSN);
	$debug->add('<pre>' . $e . '</pre>');
	$recipientId = empty($userId) ? 1 : $userId;
	$messageCenter->newMessage(
		1,
		$recipientId,
		'Problem with Amazon Payments transaction','Recently there was a transaction attempted for your account. We encountered a problem while trying to process the transaction. If payment was attempted, we cannot verify that it was successful. Please check your Amazon Payments account for a transaction by visiting <a href="https://payments.amazon.com">payments.amazon.com</a>.<br>
<br>
If you have any questions please <a href="' . LINKSUPPORT . '">contact support</a>.',
		' On line ' . __LINE__ . '<br>' . $debug->output()
	);
	$myFile = __DIR__ . '/../CustomLogs/amazonIPNListenerAfterValidate' . __LINE__ . '.txt';
	if(file_exists($myFile)){
		$fh = fopen($myFile, 'w');
		fwrite($fh,$debug->output());
	}
}