<?php
class Adrlist_Billing{
	/**
	 * Billing methods.
	 *
	 * For performing billing actions like payment authorization, payment, cancel, refund, etc.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 *
	*/
	
	//Properties.
	
	public function __construct(){
	}
	
	/**
	 * Add a billing action.
	 *
	 * This is used to reference billing transactions and is particularly helpful with complex transcations like moving from one plan to the next where an account credit is present. A billing action should be added when on of the billing actions, as listed in the billingActions table, is completed.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	int	$userId				Record the action for this user.
	 * @param	int	$billingOfferId		The billing offer id, referring to a plan or promotion.
	 * @param	int	$billingActionId	A reference to a billing action like Payment, Cancel, Refund, etc. See the billingActions table.
	 * @param	int	$vendor				The id of the vendor involved with the action.
	 * @param	string	$message		A message to aid in understanding the what and why of the action.
	 *
	 * @return	int	The userBillingActionId.
	*/
	public static function addBillingAction($userId,$billingOfferId,$billingActionId,$vendorId,$message = ''){
		global $debug, $Dbc;
		try{
			if(empty($userId) || !is_numeric($userId)){
				throw new Adrlist_CustomException('','$userId is empty or not numeric: ' . $userId);
			}elseif(empty($billingOfferId) || !is_numeric($billingOfferId)){
				throw new Adrlist_CustomException('','$billingOfferId is empty or not numeric: ' . $billingOfferId);
			}elseif(empty($billingActionId) || !is_numeric($billingActionId)){
				throw new Adrlist_CustomException('','$billingActionId is empty or not numeric: ' . $billingActionId);
			}elseif(empty($vendorId) || !is_numeric($vendorId)){
				throw new Adrlist_CustomException('','$vendorId is empty or not numeric: ' . $vendorId);
			}
			$insertStmt = $Dbc->prepare("INSERT INTO
	userBillingActions
SET
	userId = ?,
	billingOfferId = ?,
	billingActionId = ?,
	vendorId = ?,
	message = ?,
	billingDatetime = ?");
			$insertParams = array($userId,$billingOfferId,$billingActionId,$vendorId,$message,DATETIME);
			$insertStmt->execute($insertParams);
			return $Dbc->lastInsertId();
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
	}
	
	/**
	 * Send a request for recurring payment authorization to Amazon.
	 *
	 * This function creates a userBillingActionId and initiates the payment request process using Amazon FPS CBUI (Co-branded User Interface).
	 * 1. Establishes the recurring payment pipeline with the AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY.
	 * 2. Sets mandatory parameters to be sent to Amazon like callerReference, PAYMENT_RETURN_URL, transactionAmount, recurringPeriod (1 Day(s), Month(s)), and paymentReason (the name of the plan, i.e. Project Monthly).
	 * 3. The parameters are url-encoded to be sent to Amazon.
	 * 4. The request is saved in the database.
	 * This uses the Amazon API to perform the heavy lifting.
	 * After the user authorizes payment, Amazon sends the user back to ADRList.com.
	 * There are three more steps to actually recieve payment - validate the return response, make a pay request, and capture the IPN response.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	int	$userId				Request authorization for this user.
	 * @param	int	$billingOfferId		The billing offer, for example Project Monthly. Refers to the billingOffers table.
	 *
	 * @returns	string	The return url supplied to Amazon, otherwise false.
	*/
	public static function amazonAuthorization($userId,$billingOfferId){
		global $debug, $Dbc;
		try{
			if(empty($userId)){
				throw new Adrlist_CustomException('','$userId is empty.');
			}elseif(!is_numeric($userId)){
				throw new Adrlist_CustomException('','$userId is not numeric.');
			}elseif(empty($billingOfferId)){
				throw new Adrlist_CustomException('','$billingOfferId is empty.');
			}elseif(!is_numeric($billingOfferId)){
				throw new Adrlist_CustomException('','$billingOfferId is not numeric.');
			}
			$Dbc->beginTransaction();
			//Get the plan info.
			$planInfo = self::getPlanInfo($billingOfferId);
			if(empty($planInfo)){
				throw new Adrlist_CustomException('','$planInfo is empty.');
			}elseif($planInfo['endDate'] && strtotime($planInfo['endDate']) < TIMESTAMP){
				//If the endDate is not null and has expired.
				throw new Adrlist_CustomException('The plan you selected has expired. Please select a different plan.','The endDate for billingOfferId ' . $billingOfferId . 'has expired.');
			}
			//Add a billing action.
			$callerReference = self::addBillingAction($userId,$billingOfferId,1,1,__FILE__ . ' ' . __LINE__);
			/*Start an entry in the database.
			$cbuiRequestsStmt = $Dbc->prepare("INSERT INTO
	amazonCBUIRequests
SET
	userBillingActionId = ?,
	aDatetime = ?");
			$cbuiRequestsStmt->execute(array($userBillingActionId,DATETIME));
			$callerReference = $Dbc->lastInsertId();*/
			//Create the url with CBUI parameters.
			$period = ucfirst($planInfo['period']);
			$transactionAmount = trim($planInfo['price']);
			$recurringPeriod = $planInfo['length'] > 1 ? $planInfo['length'] . ' ' . $period . 's' : $planInfo['length'] . ' ' . $period;
			$recurringPeriod = $recurringPeriod == '1 Year' ? '12 Months' : $recurringPeriod;
			if(LOCAL){
				$returnUrl = LINKMYACCOUNT . '/paymentAuthorization.php';
			}else{
				$returnUrl = 'https://' . DOMAIN . '/myAccount/paymentAuthorization.php';
			}
			$request = new Amazon_CBUI_CBUIRecurringTokenPipeline(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
			$request->setMandatoryParameters($callerReference, $returnUrl, $transactionAmount, $recurringPeriod);
			$request->addParameter('paymentReason',$planInfo['name']);
			$url = $request->getUrl();
			$debug->add('$url: ' . $url);
			$urlParts = parse_url($url);
			$debug->printArray($urlParts,'$urlParts');
			parse_str($urlParts['query'],$queryArray);
			$debug->printArray($queryArray,'$queryArray');
			/*Finish inserting the request in the database.
			$finishCbuiRequestStmt = $Dbc->prepare("UPDATE
	amazonCBUIRequests
SET
	apiVersion = ?,
	callerKey = ?,
	paymentReason = ?,
	pipelineName = ?,
	returnUrl = ?,
	signature = ?,
	signatureMethod = ?,
	signatureVersion = ?,
	transactionAmount = ?,
	url = ?
WHERE
	callerReference = ?");
			$finishCbuiRequestParams = array(
				$queryArray['version'],
				$queryArray['callerKey'],
				$queryArray['paymentReason'],
				$queryArray['pipelineName'],
				$queryArray['returnURL'],
				$queryArray['signature'],
				$queryArray['signatureMethod'],
				$queryArray['signatureVersion'],
				$queryArray['transactionAmount'],
				$url,
				$queryArray['callerReference']
			);
			$finishCbuiRequestStmt->execute($finishCbuiRequestParams);*/
			$Dbc->commit();
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
		return empty($url) ? false : $url;
	}

	/**
	 * Make an Amazon pay request.
	 *
	 * The required parameter is callerReference which references the original request. An optional parameter is transactionAmount.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	int	$callerReference	Refers to the request in the amazonCBUIRequest table and the response in the amazonCBUIResponses table.
	 * @param	float	$transactionAmount	In the format #.##. This is not more than the plan amount. It may be less if the user has a credit when changing plans.
	 *
	 * @return	boolean	True without errors or exceptions, otherwise it does not return.
	*/	
	public static function amazonPayRequest($callerReference,$transactionAmount = false){
		global $debug, $Dbc;
		try{
			if(empty($callerReference)){
				throw new Adrlist_CustomException('','$callerReference is empty.');
			}elseif(!is_numeric($callerReference)){
				throw new Adrlist_CustomException('','$callerReference is not numeric: ' . $callerReference);
			}
			if(!empty($transactionAmount) && !preg_match("/^\d{1,4}\.\d{2}$/",$transactionAmount)){
				throw new Adrlist_CustomException('','$transactionAmount is not in the correct #.## format: ' . $transactionAmount);
			}
			//For a pay request we need the tokenId, transactionAmount, and callerReference. Optionally, we get the planName.
			$CBUIStmt = $Dbc->prepare("SELECT
	billingOffers.offerName AS 'planName',
	amazonCBUIResponses.tokenId AS 'tokenId',
	billingOffers.price AS 'transactionAmount'
FROM
	userBillingActions
JOIN
	billingOffers ON billingOffers.billingOfferId = userBillingActions.billingOfferId
JOIN
	amazonCBUIResponses ON amazonCBUIResponses.callerReference = userBillingActions.userBillingActionId
WHERE
	userBillingActions.userBillingActionId = ?");
			$CBUIStmt->execute(array($callerReference));
			$row = $CBUIStmt->fetch(PDO::FETCH_ASSOC);
			/*
			Build the Pay Request.
			Note the different method syntax of set vs with. With returns the object so methods can be chained together.
			*/
			$transactionAmount = empty($transactionAmount) ? $row['transactionAmount'] : $transactionAmount;
			$amount = new Amazon_FPS_Model_Amount();
			$amount->withCurrencyCode('USD')
			->setValue($transactionAmount);
			$request = new Amazon_FPS_Model_PayRequest();
			$request->withSenderTokenId($row['tokenId'])
				->withTransactionAmount($amount)
				->withChargeFeeTo('Recipient')
				->withCallerReference($callerReference)
				->withCallerDescription('pay request for ' . $row['planName'])
				->setOverrideIPNURL(SETOVERRIDEIPNURL);
			//Add the following: TransactionTimeoutInMins
			
			
			
			
			
			//->withDescriptorPolicy(DOMAIN)
			$sendPayRequest = new Amazon_FPS_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
			$sendPayRequest->pay($request);
			//There is no verification that payment was successful here. The curl statement that sends the pay request simply responds to whether the pay request was successfully received or not. We must wait for IPN responses from Amazon to finally allow the application of the plan to the user's account.
			return true;
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
	}
	
	/**
	 * Make an Amazon refund request.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	string	$userBillingActionId	Refers to the payment IPN transaction.
	 * @param	string	$callerDescription	A description of the reason for the refund.
	 * @param	float	$transactionAmount	In the format #.##. If not specified, the entire original amount is refunded.
	 *
	 * @return	boolean	True without errors or exceptions, otherwise it does not return.
	*/	
	public static function amazonRefundRequest($userBillingActionId,$callerDescription = false,$transactionAmount = false){
		global $debug, $Dbc;
		try{
			if(empty($userBillingActionId)){
				throw new Adrlist_CustomException('','$userBillingActionId is empty.');
			}elseif(!is_numeric($userBillingActionId)){
				throw new Adrlist_CustomException('','$userBillingActionId is not numeric: ' . $userBillingActionId);
			}
			if(!empty($transactionAmount) && !preg_match("/^\d{1,4}\.\d{2}$/",$transactionAmount)){
				throw new Adrlist_CustomException('','$transactionAmount is not in the correct #.## format: ' . $transactionAmount);
			}
			//Get the  information of the transaction to be refunded.
			$transactionStmt = $Dbc->prepare("SELECT
	userBillingActions.userBillingActionId AS 'userBillingActionId',
	userBillingActions.userId AS 'userId',
	userBillingActions.billingOfferId AS 'billingOfferId',
	amazonIPNListener.transactionAmount AS 'transactionAmount',
	amazonIPNListener.transactionId AS 'transactionId'
FROM
	userBillingActions
JOIN
	amazonIPNListener ON amazonIPNListener.userBillingActionId = userBillingActions.userBillingActionId
WHERE
	userBillingActions.userBillingActionId = ?");
			$transactionStmt->execute(array($userBillingActionId));
			$row = $transactionStmt->fetch(PDO::FETCH_ASSOC);
			$row['transactionAmount'] = explode(' ',$row['transactionAmount']);
			$row['transactionAmount'] = $row['transactionAmount'][1];
			//Note the different method syntax of set vs with. With returns the object so methods can be chained together.
			$transactionAmount = empty($transactionAmount) ? $row['transactionAmount'] : $transactionAmount;
			$amount = new Amazon_FPS_Model_Amount();
			$amount->withCurrencyCode('USD')
			->setValue($transactionAmount);
			$request = new Amazon_FPS_Model_RefundRequest();
			$request->withCallerReference($row['userBillingActionId'])
				->withRefundAmount($amount)
				->withTransactionId($row['transactionId'])
				->withCallerDescription($callerDescription)
				->setOverrideIPNURL(SETOVERRIDEIPNURL);
				//->withDescriptorPolicy(DOMAIN)
			$sendPayRequest = new Amazon_FPS_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
			$sendPayRequest->refund($request);
			//There is no verification that the refund was successful here. The curl statement that sends the refund request simply responds to whether the refund request was successfully received or not. We must wait for IPN responses from Amazon to finally allow the application of the refund to the user's account.
			return true;
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
	}
	
	public static function buildPlans($showPrices = false){
		global $debug, $Dbc;
		$output = '';
		try{
			//Build the plans.
			$plansStmt = $Dbc->prepare("SELECT
	billingOfferId AS 'billingOfferId',
	offerName AS 'name',
	terms AS 'terms',
	period AS 'period',
	price AS 'price',
	pricePerListPerMonth AS 'pricePerListPerMonth',
	savings AS 'savings',
	credits AS 'credits'
FROM
	billingOffers
WHERE
	offerName IN(?,?,?,?,?,?,?,?)");
			$plansParams = array('Project Monthly','Project Yearly','Independent Monthly','Independent Yearly','Pro Monthly','Pro Yearly','Studio Monthly','Studio Yearly');
			$plansStmt->execute($plansParams);
			while($row = $plansStmt->fetch(PDO::FETCH_ASSOC)){
				$terms = $row['terms'];
				$newPlanArray[$row['credits']][$row['period']] = $row;
			}
			$url = empty($_COOKIE['adrListRememberMe']) ? LINKCREATEACCOUNT : LINKLOGIN;
			$planDesktop = '';
			$planDesktopNoPrices = '';
			$planMobile = '';
			$planMobileNoPrices = '';
			$column = 1;
			foreach($newPlanArray as $key => $value){
				$title = explode(' ',$value['month']['name']);
				$title = $title[0];
				if($column == 1){
					$css = 'gradientGray name';
				}elseif($column == 2 || $column == 3){
					$css = 'gradientBlue name nameCenter';
				}else{
					$css = 'gradientBlack name';
				}
				$planDesktop .= '<div class="column column' . $column . '">
	<div class="' . $css . '">' . $title . '</div>
	<div class="column' . $column . 'Bottom">
		<div class="price" billingOfferId="' . $value['month']['billingOfferId'] . '">
			$' . $value['month']['price'] . '<span class="priceText">/ ' . $value['month']['period'] . '</span>
			<div class="pricePer">(only $' . $value['month']['pricePerListPerMonth'] . '/month per list)</div>
		</div>
		<div class="price" billingOfferId="' . $value['year']['billingOfferId'] . '">
			$' . $value['year']['price'] . '<span class="priceText">/ ' . $value['year']['period'] . '</span>
			<div class="pricePer">(only $' . $value['year']['pricePerListPerMonth'] . '/month per list)</div>
			<div class="save">' . $value['year']['savings'] . '% off the monthly plan</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">' . $value['month']['credits'] . ' Active Lists</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">Unlimited Users</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">24/7 Access</div>
		</div>
		<div class="purchase">
			<div class="buttonBlue gradientBlue purchasePlan" column="column' . $column . '" billingOfferId="' . $value['month']['billingOfferId'] . '">Select ' . ucfirst($value['month']['period']) . 'ly</div>
			<div class="buttonBlue gradientBlue purchasePlan" column="column' . $column . '" billingOfferId="' . $value['year']['billingOfferId'] . '">Select ' . ucfirst($value['year']['period']) . 'ly</div>
		</div>
	</div>
</div>';
			$planDesktopNoPrices .= '<div class="column column' . $column . '">
	<div class="' . $css . '">' . $title . '</div>
	<div class="column' . $column . 'Bottom">
		<div class="break">
			<div class="check"></div><div class="checkText">' . $value['month']['credits'] . ' Active Lists</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">Unlimited Users</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">24/7 Access</div>
		</div>
		<div class="purchase">
			<a class="buttonBlue gradientBlue" data-ajax="false" data-role="none" href="' . $url . '">More Info</a>
		</div>
	</div>
</div>';
			$planMobile .= '
<div class="roundedCorners" style="border:1px solid #999; margin-bottom:.5em">
	<div class="' . $css . '">' . $title . '</div>
	<div style="padding:.5em">
		<div class="price" billingOfferId="' . $value['month']['billingOfferId'] . '">
			$' . $value['month']['price'] . '<span class="priceText">/ ' . $value['month']['period'] . '</span>
			<div class="pricePer">(only $' . $value['month']['pricePerListPerMonth'] . '/month per list)</div>
		</div>
		<div class="price" billingOfferId="' . $value['year']['billingOfferId'] . '">
			$' . $value['year']['price'] . '<span class="priceText">/ ' . $value['year']['period'] . '</span>
			<div class="pricePer">(only $' . $value['year']['pricePerListPerMonth'] . '/month per list)</div>
			<div class="save">' . $value['year']['savings'] . '% off the monthly plan</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">' . $value['month']['credits'] . ' Active Lists</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">Unlimited Users</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">24/7 Access</div>
		</div>
		<div class="purchase">
			<div class="buttonBlue gradientBlue purchasePlan" column="column' . $column . '" billingOfferId="' . $value['month']['billingOfferId'] . '">Select ' . ucfirst($value['month']['period']) . 'ly</div>
			<div class="buttonBlue gradientBlue purchasePlan" column="column' . $column . '" billingOfferId="' . $value['year']['billingOfferId'] . '">Select ' . ucfirst($value['year']['period']) . 'ly</div>
		</div>
	</div>
</div>';
				$planMobileNoPrices .= '
<div class="roundedCorners" style="border:1px solid #999;margin:.5em">
	<div class="' . $css . '">' . $title . '</div>
	<div class="column' . $column . 'Bottom" style="padding:.5em">
		<div class="break">
			<div class="check"></div><div class="checkText">' . $value['month']['credits'] . ' Active Lists</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">Unlimited Users</div>
		</div>
		<div class="break">
			<div class="check"></div><div class="checkText">24/7 Access</div>
		</div>
		<div class="purchase">
			<a class="buttonBlue gradientBlue" data-ajax="false" data-role="none" href="' . $url . '">More Info</a>
		</div>
	</div>
</div>';
				$column++;
			}
			if($showPrices){
				$plans = '<div class="desktop" style="margin:1em 0">
	' . $planDesktop . '
</div>
<div class="mobile tablet">
	' . $planMobile . '
</div>
<button class="ui-btn ui-btn-icon-right ui-btn-inline ui-corner-all ui-icon-carat-r ui-mini ui-shadow" toggle="toggleTerms">Plan Terms</button>
<div class="terms" id="toggleTerms">
	' . nl2br($terms) . '
</div>';
			}else{
				$plans = '<div class="desktop" style="margin:1em 0">
	' . $planDesktopNoPrices . '
</div>
<div class="mobile tablet">
	' . $planMobileNoPrices . '
</div>';
			}
			$output .= '<div class="textCenter">
	' . $plans . '
</div>';
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
		return $output;
	}
	
	/*
	 * Calculate the refund amount when changing plans.
	 *
	 * @param	int	$userId	The id of the user to get the refund amount for.
	 *
	 * @return	array	A nested array:
	 *	array(
	 *		0 userPlanArray,
	 *		1 refundAmount in the format #.##,
	 * 		2 billingPeriodDays,
	 *		3 daysRemaining
	 *	)
	 *	Returns false upon an error. To verify success, check that the output is an array using is_array().
	*/
	public static function calculateRefund($userId){
		global $debug, $Dbc;
		try{
			if(empty($userId)){
				throw new Adrlist_CustomException('','$userId is empty: ' . $userId);
			}elseif(!is_numeric($userId)){
				throw new Adrlist_CustomException('','$userId is not numeric: ' . $userId);
			}
			$userPlan = self::getUserPlan($userId);
			if(!is_array($userPlan)){
				return false;
			}
			$debug->printArray($userPlan,'$userPlan in calulateRefund');
			$nextBillingDate = Adrlist_Time::addToDate($userPlan['dateAdded'],$userPlan['period'],$userPlan['length']);
			$billingPeriodDays = Adrlist_Time::daysDifference($userPlan['dateAdded'],$nextBillingDate);
			$debug->add('$billingPeriodDays: ' . $billingPeriodDays);
			$daysRemaining = Adrlist_Time::daysDifference('NOW',$nextBillingDate);
			$costPerDay = $userPlan['price']/$billingPeriodDays;
			$debug->add('$costPerDay: ' . $costPerDay);
			$refundAmount = round($costPerDay*$daysRemaining,2);
			$returnArray = array('userPlanArray' => $userPlan,'refundAmount' => $refundAmount,'billingPeriodDays' => $billingPeriodDays,'daysRemaining' => $daysRemaining);	
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
		return isset($refundAmount) ? $returnArray : false;
	}
	
	public static function getBillingActions(){
		//Returns an array with billing actions, otherwise false.
		global $debug, $Dbc;
		try{
			$getBillingActionsStmt = $Dbc->prepare("SELECT
	billingActionId AS 'billingActionId',
	billingAction AS 'billingAction'
FROM
	billingActions");
			$getBillingActionsStmt->execute();
			$billingActions = array();
			while($row = $getBillingActionsStmt->fetch(PDO::FETCH_ASSOC)){
				$billingActions[$row['billingActionId']] = $row['billingAction'];
			}
			$debug->printArray($billingActions,'$billingActions');
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
		if(empty($billingActions)){
			return false;
		}else{
			return $billingActions;
		}
	}
	
	public static function getPlanInfo($billingOfferId){
		//Returns an array with plan information if a plan exists, otherwise false.
		global $debug, $Dbc;
		try{
			if(empty($billingOfferId)){
				throw new Adrlist_CustomException('','$billingOfferId is empty.');
			}elseif(!is_numeric($billingOfferId)){
				throw new Adrlist_CustomException('','$billingOfferId is not numeric.');
			}
			$getPlanInfoStmt = $Dbc->prepare("SELECT
	offerName AS 'name',
	offerType AS 'type',
	terms AS 'terms',
	offerLength AS 'length',
	period AS 'period',
	price AS 'price',
	pricePerListPerMonth AS 'pricePerListPerMonth',
	savings AS 'savings',
	renewable AS 'renewable',
	credits AS 'credits',
	startDate AS 'startDate',
	endDate AS 'endDate'
FROM
	billingOffers
WHERE
	billingOfferId = ?");
			$getPlanInfoStmt->execute(array($billingOfferId));
			$planInfo = $getPlanInfoStmt->fetch(PDO::FETCH_ASSOC);
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
		if(empty($planInfo)){
			return false;
		}else{
			return $planInfo;
		}
	}

	/*
	 * Get the plan information for a specified user.
	 *
	 * $userId	int
	 *
	 * @return	array	The user's plan information if a plan exists, otherwise false. To verify use is_array().
	*/
	public static function getUserPlan($userId){
		global $debug, $Dbc;
		try{
			if(empty($userId)){
				throw new Adrlist_CustomException('','$userId is empty.');
			}elseif(!is_numeric($userId)){
				throw new Adrlist_CustomException('','$userId is not numeric.');
			}
			$checkPlansStmt = $Dbc->prepare("SELECT
	billingOffers.billingOfferId AS 'billingOfferId' ,
	billingOffers.offerName AS 'name' ,
	billingOffers.offerType AS 'type',
	billingOffers.terms AS 'terms',
	billingOffers.offerLength AS 'length',
	billingOffers.period AS 'period',
	billingOffers.renewable AS 'renewable',
	billingOffers.credits AS 'credits',
	billingOffers.startDate AS 'startDate',
	billingOffers.endDate AS 'endDate',
	userBilling.userBillingId AS 'userBillingId',
	userBilling.userBillingActionId AS 'userBillingActionId',
	userBilling.dateAdded AS 'dateAdded'
FROM
	billingOffers
JOIN
	userBilling ON userBilling.billingOfferId = billingOffers.billingOfferId AND
	userBilling.userId = ?");
			$checkPlansStmt->execute(array($userId));
			$plansCount = 0;
			$credits = 0;
			while($row = $checkPlansStmt->fetch(PDO::FETCH_ASSOC)){
				//Check whether the plan or promotion has expired.
				$expires = Adrlist_Time::addToDate($row['dateAdded'],$row['period'],$row['length']);
				$expires = $expires->getTimestamp();
				$validCredit = false;
				if(empty($row['startDate']) && empty($row['endDate'])){
					$validCredit = true;
				}else{
					if(!empty($row['startDate'])){
						$validCredit = strtotime($row['startDate']) <= strtotime($row['dateAdded']) ? true : false;
					}
					if(!empty($value['endDate'])){
						$validCredit = strtotime($row['endDate']) >= strtotime($row['dateAdded']) ? true : false;
					}
				}
				if(!$row['renewable'] && $expires < TIMESTAMP){
					//A non-renewable plan or promotion has expired.
					$validCredit = false;
				}
				$row['validCredit'] = $validCredit;
				$credits = $validCredit ? $credits + $row['credits'] : $credits;
				$userBillingInfo[] = $row;
				$plansCount++;
			}
			$userBillingInfo['credits'] = $credits;
			if($plansCount > 1){
				throw new Adrlist_CustomException('','The user has more than one active plan or promotion, which shouldn\'t be possible.');
			}
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			error(__LINE__,'','');
		}
		return empty($userBillingInfo) ? false : $userBillingInfo;
	}
}