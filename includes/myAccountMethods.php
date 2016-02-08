<?php /*
This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
*/
$fileInfo = array('fileName' => 'includes/myAccountMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'addCredits'){
	addCredits();
}elseif(MODE == 'buildBilling'){
	buildBilling();
}elseif(MODE == 'buildBillingHistory'){
	buildBillingHistory();
}elseif(MODE == 'buildBillingOffers'){
	buildBillingOffers();
}elseif(MODE == 'changePlan'){
	changePlan();
}elseif(MODE == 'parseReturnUrl'){
	parseReturnUrl();
}elseif(MODE == 'purchasePlanAmazon'){
	purchasePlanAmazon();
}elseif(MODE == 'saveMyInformation'){
	saveMyInformation();
}elseif(MODE == 'saveSettings'){
	saveSettings();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function addCredits(){
	//Build the initial plan selection.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '<div class="textLeft" style="margin-top:1em;line-height:1.5em">
ADR List offers many different levels of service plans to fit the needs of your business.
	<div>
		<i class="fa fa-hand-o-right" ></i>All plans are subscription-based. You can choose to be billed once per month, or save by paying yearly.
	</div>
	<div>
		<i class="fa fa-hand-o-right" ></i>Change or cancel plans at any time. You\'ll be refunded the pro-rated balance of your current plan.
	</div>
	<div>
		<i class="fa fa-hand-o-right" ></i>Cancel any time within the first 14 days and receive a full refund.*
	</div>
</div>
<div class="textLeft textSmall">* See the plans terms below for more information.</div>
'
 . buildBillingOffers();
	if(MODE == 'addCredits'){
		$returnThis['output'] = $output;
		$success = true;
		returnData();
	}else{
		return $output;
	}
}

function buildBilling(){
	//Build the billing info including credits and expiration date.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_SESSION['userId'])){
			throw new Adrlist_CustomException('','$_SESSION[\'userId\'] is empty.');
		}
		//Refresh credits.
		reconcileLists($_SESSION['userId']);
		$userPlan = Adrlist_Billing::getUserPlan($_SESSION['userId']);
		$purchasedPlan = false;
		$hiddenRows = array();
		$billingOutput = '';
		$foundRows = false;
		if(count($userPlan) > 1){
			$nameLength = 0;
			$billingPlansArray = array();
			$billingPlansHiddenArray = array();
			foreach($userPlan as $key => $value){
				$debug->add('key: ' . $key);
				if(is_numeric($key) && $value['validCredit']){
					$foundRows = true;
					if($value['validCredit']){
						$purchasedPlan = $value['type'] == 'plan' ? true : $purchasedPlan;
					}
					$newDate = Adrlist_Time::addToDate($value['dateAdded'],$value['period'],$value['length']);
					$newDate = Adrlist_Time::utcToLocal($newDate,false)->format('F j, Y');
					if($value['type'] == 'plan'){
						$nextBillingDate = $newDate;
						$expires = 'N/A';
					}else{
						$nextBillingDate = 'N/A';
						$expires = $newDate;
					}
					$nameLength = strlen($value['name']) > $nameLength ? strlen($value['name']) * .8 : $nameLength;
					$billingOutput .= '
<div class="columnParent">
	<div class="break">
		<div class="columnLeft" style="font-weight:none">
			Billing Plan:
		</div>
		<div class="columnRight">
			' . $value['name'] . '
		</div>
	</div>
	<div class="break">
		<div class="columnLeft">
			Next Billing Date:
		</div>
		<div class="columnRight">
			' . $nextBillingDate . '
		</div>
	</div>
	<div class="break">
		<div class="columnLeft">
			Credits:
		</div>
		<div class="columnRight">
			' . $value['credits'] . '
		</div>
	</div>
	<div class="break">
		<div class="columnLeft">
			Expires:
		</div>
		<div class="columnRight">
			' . $expires . '
		</div>
	</div>
</div>
<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all"  toggle="planTerms' . $value['userBillingId'] . '">Plan Terms</button>
<div class="hide" id="planTerms' . $value['userBillingId'] . '">
	<div class="textLeft" style="margin:1em"><br>
		' . nl2br($value['terms']) . '
	</div>
</div>';
					
					/*Currently not used.
					$name = '<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all" toggle="billingPlanAction' . $value['userBillingId'] . '">' . $value['name'] . '</button>';
					$billingPlansArray[$value['userBillingId']] = array(
						$name,
						$nextBillingDate,
						$value['credits'],
						$expires
					);
					$billingPlanActions = '<div class="break">Plan Actions</div>
<button class="changePaymentCard ui-btn ui-btn-inline ui-corner-all ui-mini"><i class="fa fa-credit-card" ></i>Change Payment Card</button><button class="changePlan ui-btn ui-btn-inline ui-corner-all ui-mini"><i class="fa fa-credit-card" ></i>Change Plan</button>

<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all"  toggle="planTerms' . $value['userBillingId'] . '">Plan Terms</button>
<div class="hide" id="planTerms' . $value['userBillingId'] . '">
	<div class="textLeft" style="margin:1em"><br>
		' . nl2br($value['terms']) . '
	</div>
</div>';
					$billingPlansHiddenArray[$value['userBillingId']] = array('billingPlanAction' . $value['userBillingId'],$billingPlanActions);*/
				}
			}
			/*if($foundRows){
				$titleArray = array(
					array('Name'),
					array('Next Billing Date',1),
					array('Credits',2),
					array('Expires',3)
				);
				$buildBillingRows = new Adrlist_BuildRows('billingPlans',$titleArray,$billingPlansArray);
				$buildBillingRows->addHiddenRows($billingPlansHiddenArray);
				$billingOutput .= $buildBillingRows->output();
			}*/
		}
		if(!$foundRows){
			$billingOutput = '<div style="margin:1em">This account doesn\'t have any active plans or promotions.</div>';
		}
		$billingOutput .= $purchasedPlan ? '<button class="changePaymentCard ui-btn ui-btn-inline ui-corner-all ui-mini"><i class="fa fa-credit-card" ></i>Change Payment Card</button><button class="changePlan ui-btn ui-btn-inline ui-corner-all ui-mini"><i class="fa fa-credit-card" ></i>Change Plan</button>
' : '<button class="ui-btn ui-btn-icon-left ui-btn-inline ui-corner-all ui-mini ui-shadow" id="addCredits">Add Credits</button>';
		$output .= '<button class="buildBillingHistory ui-btn ui-btn-icon-left ui-btn-inline ui-corner-all ui-icon-clock ui-mini ui-shadow">Billing History</button><div class="myAccountTitle textCenter">
	Billing
</div>
<div class="textCenter">
' . $billingOutput .'
	<div class="columnParent">
		<div class="break">
			<div class="columnLeft" style="font-weight:none">
				Credits:
			</div>
			<div class="columnRight">
				' . $_SESSION['credits'] . '
			</div>
		</div>
		<div class="break">
			<div class="columnLeft">
				Active Lists:
			</div>
			<div class="columnRight">
				' . $_SESSION['activeLists'] . '
			</div>
		</div>
		<div class="hr2"></div>
		<div class="break">
			<div class="columnLeft">
				Credit Balance:
			</div>
			<div class="columnRight">
				' . ($_SESSION['credits'] - $_SESSION['activeLists']) . '
			</div>
		</div>
	</div>
</div>';
		if(MODE == 'buildBilling'){
			$success = true;
			$returnThis['buildBilling'] = $output;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildBilling'){
		returnData();
	}else{
		return $output;
	}
}

function buildBillingHistory(){
	//Build the billing history.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		$billingHistoryCount = "SELECT
	COUNT(*) AS 'count'
FROM
	userBillingActions
JOIN
	billingOffers ON billingOffers.billingOfferId = userBillingActions.billingOfferId
LEFT JOIN
	amazonCBUIResponses ON (amazonCBUIResponses.callerReference = userBillingActions.userBillingActionId OR
	amazonCBUIResponses.userBillingActionId = userBillingActions.userBillingActionId)
LEFT JOIN
	amazonIPNListener ON (amazonIPNListener.callerReference = amazonCBUIResponses.callerReference OR
	amazonIPNListener.userBillingActionId = userBillingActions.userBillingActionId)
WHERE
	userBillingActions.userId = ?
";
		//Get transactions from userBillingActions.
		$billingHistoryStmt = "SELECT
	billingOffers.offerName AS 'offerName',
	billingOffers.offerLength AS 'length',
	billingOffers.period AS 'period',
	billingOffers.price AS 'price',
	billingOffers.credits AS 'credits',
	billingOffers.terms AS 'terms',
	amazonCBUIResponses.aDatetime AS 'responsesDatetime',
	amazonIPNListener.aDatetime AS 'listenerDatetime',
	amazonIPNListener.operation AS 'operation',
	amazonIPNListener.parentTransactionId AS 'parentTransactionId',
	amazonIPNListener.transactionAmount AS 'transactionAmount',
	amazonIPNListener.transactionId AS 'transactionId',
	amazonIPNListener.transactionStatus AS 'transactionStatus',
	amazonCBUIResponses.tokenId AS 'tokenId',
	userBillingActions.billingDatetime AS 'datetime',
	userBillingActions.userBillingActionId AS 'userBillingActionId',
	userBillingActions.billingActionId AS 'billingActionId'
FROM
	userBillingActions
JOIN
	billingOffers ON billingOffers.billingOfferId = userBillingActions.billingOfferId
LEFT JOIN
	amazonCBUIResponses ON (amazonCBUIResponses.callerReference = userBillingActions.userBillingActionId OR
	amazonCBUIResponses.userBillingActionId = userBillingActions.userBillingActionId)
LEFT JOIN
	amazonIPNListener ON (amazonIPNListener.callerReference = amazonCBUIResponses.callerReference OR
	amazonIPNListener.userBillingActionId = userBillingActions.userBillingActionId)
WHERE
	userBillingActions.userId = ?
";
		if(empty($_POST['searchVal'])){
			$search = false;
			$billingHistoryStmt .= "
GROUP BY
	userBillingActions.userBillingActionId
ORDER BY
	userBillingActions.userBillingActionId, userBillingActions.billingDatetime, amazonIPNListener.aDatetime, amazonIPNListener.microtime,amazonCBUIResponses.aDatetime";
			$billingHistoryParams = array($_SESSION['userId']);
			$billingHistoryCount = $Dbc->prepare($billingHistoryCount);
		}else{
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchval: ' . $searchVal);
			$endStmt = " AND
	(billingActions.billingAction LIKE ? || billingOffers.offerName LIKE ?)
GROUP BY
	userBillingActions.userBillingActionId
ORDER BY
	userBillingActions.userBillingActionId, userBillingActions.billingDatetime, amazonIPNListener.aDatetime, amazonIPNListener.microtime,amazonCBUIResponses.aDatetime";
			$billingHistoryStmt .= $endStmt;
			$billingHistoryParams = array($_SESSION['userId'],$searchVal,$searchVal,$searchVal);
			$billingHistoryCount = $Dbc->prepare($billingHistoryCount . $endStmt);
		}
		$billingHistoryCount->execute($billingHistoryParams);
		$count = $billingHistoryCount->fetch(PDO::FETCH_ASSOC);
		$itemCount = $count['count'];
		$pagination = new Adrlist_Pagination('buildBillingHistory','buildBillingHistory',$itemCount,'Search History',$search);
		list($offset,$limit) = $pagination->offsetLimit();
		$billingHistoryStmt = $Dbc->prepare($billingHistoryStmt . "
LIMIT $offset, $limit");
		$billingHistoryStmt->execute($billingHistoryParams);
		//pdoError(__LINE__,$billingHistoryStmt,$billingHistoryParams);
		$foundRows = false;
		$rowArray = array();
		$termsArray = array();
		$billingActions = Adrlist_Billing::getBillingActions();
		$nestedTransactions = array();
		while($row = $billingHistoryStmt->fetch(PDO::FETCH_ASSOC)){
			$transactionId = $row['parentTransactionId'] ? $row['parentTransactionId'] : $row['transactionId'];
			$transactionAmount = $row['transactionAmount'] ? $row['transactionAmount'] : 'USD ' . $row['price'];
			//Use the payment authorization request (billingActionId = 1) as the main transaction. All other transactions will be referenced by transactionId or parentTransactionId.
			if($row['billingActionId'] == 1){
				$rowArray[$transactionId] = array(
					$row['userBillingActionId'],
					$row['offerName'],
					$row['length'] . ' ' . $row['period'],
					$transactionAmount,
					$row['credits'],
					Adrlist_Time::utcToLocal($row['datetime']),
					'<button class="ui-btn ui-icon-carat-r ui-btn-icon-right ui-btn-inline ui-corner-all ui-mini" toggle="BillingHistoryNested' . $transactionId . '">View Transactions</button>'
				);
				$foundRows = true;
			}else{
				//This is for nested, related transactions that are not a payment authorization request.
				if($row['billingActionId'] == 1){
					$date = $row['datetime'];
				}elseif($row['billingActionId'] == 2){
					//An amazonCBUIResponse date.
					$date = $row['responsesDatetime'];
				}elseif($row['billingActionId'] == 3 || $row['billingActionId'] == 4 || $row['billingActionId'] == 5 || $row['billingActionId'] == 6){
					//An amazonIPNListener date.
					$date = $row['listenerDatetime'];
				}else{
					$date = $row['datetime'];
				}
				//Build the nested transactions.
				$termsArray[$transactionId] = '<div class="textLeft">' . nl2br($row['terms']) . '</div>';
				$nestedTransactions[$transactionId][] = array(
					Adrlist_Time::utcToLocal($date),
					$billingActions[$row['billingActionId']],
					$transactionAmount
				);
			}
		}
		$output .= '<div class="bold textLarge">Billing History</div>';
		if($foundRows){
			$nestedTransactionsTitleRowArray = array(
				array('Date',15),
				array('Billing Action',30),
				array('Transaction Amount',15)
			);
			$debug->printArray($nestedTransactions,'$nestedTransactions');
			$hiddenRows = array();
			foreach($nestedTransactions as $transactionId => $value){
				$buildNestedRows = new Adrlist_BuildRows('nothing' . $transactionId,$nestedTransactionsTitleRowArray,$value);
				$hiddenRows[$transactionId] = array('BillingHistoryNested' . $transactionId,'<div>' . $buildNestedRows->output() . '</div>' . '<h2>Terms</h2>' . $termsArray[$transactionId]);
			}
			$debug->printArray($hiddenRows,'$hiddenRows');
			$rowArray = array_reverse($rowArray);
			$debug->printArray($rowArray,'$rowArray');
			$titleArray = array(
				array('Billing ID',6),
				array('Offer Name',10),
				array('Period',8),
				array('Price',8),
				array('Credits',8),
				array('Purchase Date',16),
				array('Transaction Details',15)
			);
			$buildLists = new Adrlist_BuildRows('BillingHistory',$titleArray,$rowArray);
			$buildLists->addHiddenRows($hiddenRows);
			$output .= $pagination->output() . $buildLists->output();

			//$titleRowArray = array('Billing ID','Offer Name','Period','Price','Credits','Date','Terms','Transactions');
			//$cssWidths = array(6,10,8,8,8,15,15,15);
		}else{
			$output .= 'There is no billing history for this account.';
		}
		if(MODE == 'buildBillingHistory'){
			$success = true;
			$returnThis['output'] = $output;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildBillingHistory'){
		$success = true;
		returnData();
	}else{
		return $output;
	}
}

function buildBillingOffers($selectedPlanId = false){
	//Build the plans and payment buttons.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = Adrlist_Billing::buildPlans(true) . '<div class="textCenter">
	<img src="' . AUTOLINK . '/images/amazonFPS.gif" style="height:65px;width:230px;margin:1em"><br>
	<button class="purchasePlanAmazon ui-btn ui-btn-icon-left ui-btn-inline ui-corner-all ui-icon-credit-card ui-shadow">Pay via Amazon Payments</button>' . cancelButton() . '
</div>
<div>&nbsp;</div>
';
	if(MODE == 'buildBillingOffers'){
		$returnThis['buildBillingOffers'] = $output;
		$success = true;
		returnData();
	}else{
		return $output;
	}
}

function buildMyInformation(){
	//User information, where the user can change name, email address, password, etc.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_SESSION['userId'])){
			throw new Adrlist_CustomException('$_SESSION[\'userId\'] is empty.','');
		}
		$output .= '<div class="myAccountTitle textCenter">My Information</div>
<div class="textCenter">Change your name, email address, and password here. Leave the new password empty if you do not want to change it.</div>
';
		$stmt = $Dbc->prepare("SELECT
	users.userId AS userId,
	users.firstName as 'firstName',
	users.lastName as 'lastName',
	users.primaryEmail AS 'primaryEmail',
	users.secondaryEmail AS 'secondaryEmail'
FROM
	users
WHERE
users.userId = ?");
		$stmt->execute(array($_SESSION['userId']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$folders = array();
		$output .= '
<div class="center textCenter">
	<div class="ui-field-contain">
		<label for="myInformationFirstName" unused="ui-hidden-accessible">First Name</label>
		<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="myInformationFirstName" goswitch="saveMyInformation" name="myInformationFirstName" placeholder="" value="' . $row['firstName'] . '" type="text">
	</div>
	<div class="ui-field-contain">
		<label for="myInformationLastName" unused="ui-hidden-accessible">Last Name</label>
		<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="myInformationLastName" goswitch="saveMyInformation" name="myInformationLastName" placeholder="" value="' . $row['lastName'] . '" type="text">
	</div>
	<div class="ui-field-contain">
		<label for="primaryEmail" unused="ui-hidden-accessible"><span class="red">*</span> Primary Email Address</label>
		<input autocapitalize="off" autocorrect="off" data-wrapper-class="true" id="primaryEmail" goswitch="saveMyInformation" name="primaryEmail" placeholder="" value="' . $row['primaryEmail'] . '" type="email">
	</div>
	<div class="ui-field-contain">
		<label for="primaryEmailRetype" unused="ui-hidden-accessible"><span class="red">*</span> Re-type Primary Email Address</label>
		<input autocapitalize="off" autocorrect="off" data-wrapper-class="true" id="primaryEmailRetype" goswitch="saveMyInformation" name="primaryEmailRetype" placeholder="" value="' . $row['primaryEmail'] . '" type="email">
	</div>
	<div class="ui-field-contain">
		<label for="secondaryEmail" unused="ui-hidden-accessible">' . faqLink(37) . 'Secondary Email Address</label>
		<input autocapitalize="off" autocorrect="off" data-wrapper-class="true" id="secondaryEmail" goswitch="saveMyInformation" name="secondaryEmail" placeholder="" value="' . $row['secondaryEmail'] . '" type="email">
	</div>
	<div class="ui-field-contain">
		<label for="secondaryEmailRetype" unused="ui-hidden-accessible">Re-type Secondary Email Address</label>
		<input autocapitalize="off" autocorrect="off" data-wrapper-class="true" id="secondaryEmailRetype" goswitch="saveMyInformation" name="secondaryEmailRetype" placeholder="" value="' . $row['secondaryEmail'] . '" type="email">
	</div>
	<div class="ui-field-contain">
		<label for="currentPassword" unused="ui-hidden-accessible"><span class="red">*</span> Current Password</label>
		<input autocapitalize="off" autocorrect="off" id="currentPassword" goswitch="saveMyInformation" name="currentPassword" placeholder="" value="" type="password">
	</div>
	<div class="ui-field-contain">
		<label for="newPassword" unused="ui-hidden-accessible">New Password</label>
		<input autocapitalize="off" autocorrect="off" id="newPassword" goswitch="saveMyInformation" name="newPassword" placeholder="" value="" type="password">
	</div>
	<span class="textSmall">case-sensitive, at least 6 characters, ! and @ allowed</span>
	<div class="ui-field-contain">
		<label for="newPasswordRetype" unused="ui-hidden-accessible">Re-type New Password</label>
		<input autocapitalize="off" autocorrect="off" id="newPasswordRetype" goswitch="saveMyInformation" name="newPasswordRetype" placeholder="" value="" type="password">
	</div>
	<a href="' . LINKFORGOTPASSWORD . '">forgot password ?</a>
	<div>
		<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-heart" id="saveMyInformation">Save</button>
	</div>
</div>';
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	return $output;
}

function buildSettings(){
	//The user's settings for timezone, dateFormat, etc.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_SESSION['userId'])){
			throw new Adrlist_CustomException('$_SESSION[\'userId\'] is empty.','');
		}
		$output = '';
		//Get the user's prefs.
		$stmt = $Dbc->prepare("SELECT
	userSiteSettings.timeZone AS 'timeZone',
	userSiteSettings.dateFormatId AS 'dateFormatId',
	userSiteSettings.viewListOnLogin AS 'viewListOnLogin',
	userSiteSettings.defaultShowCharacterColors AS 'defaultShowCharacterColors'
FROM
	userSiteSettings
JOIN users ON userSiteSettings.userId = users.userId AND
	users.userId = ?");
		$stmt->execute(array($_SESSION['userId']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		//Get the date formats.
		$dateFormatArray = Adrlist_Time::getDateFormats();
		$dateFormats = '<div class="ui-field-contain">
	<label for="dateFormatSelect" class="select">Date Format</label>
		<select name="dateFormatSelect" id="dateFormatSelect" data-mini="false" data-inline="true">';
		foreach($dateFormatArray as $dateFormatId => $miniArray){
			$dateFormats .= '<option value="' . $dateFormatId . '"';
			$dateFormats .= $row['dateFormatId'] == $dateFormatId ? ' selected="selected"' : '';
			$dateFormats .='>' . Adrlist_Time::utcToLocal(false,false)->format($miniArray[0]) . '</option>';
		}
		$dateFormats .= '		</select>
</div>';
		//Build the time zone drop down list.
		$output .= '
<div class="center textCenter" id="settingsContainer" style="padding:0.3em;">
	<div class="myAccountTitle">Settings</div>
	<div class="center textCenter" id="timeZoneHolder" goswitch="createNewUser" label=""></div>
	' . $dateFormats . '
	<div class="ui-field-contain">
		<input';
			$output .= $row['viewListOnLogin'] ? ' checked="checked"' : '';
			$output .= ' name="viewListOnLogin" id="viewListOnLogin" type="checkbox">
		<label for="viewListOnLogin">View List on Login</label>
	</div>
	<div style="padding:inherit" class="break myAccountTitle textCenter">
		List Defaults
	</div>
	<div class="ui-field-contain">
		<input';
	$output .= $row['defaultShowCharacterColors'] ? ' checked="checked"' : '';
	$output .= ' goswitch="saveSettings" id="defaultShowCharacterColors" name="defaultShowCharacterColors" type="checkbox"><label for="defaultShowCharacterColors" class="ui-btn ui-corner-all ui-btn-inherit ui-btn-icon-left ui-checkbox-off">Use character colors</label>
	</div>
	<div>
		<button id="saveSettings" class="ui-btn ui-icon-heart ui-btn-icon-left ui-btn-inline ui-shadow ui-corner-all">Save</button>
	</div>
</div>
';
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	return $output;
}

function changePlan(){
	//The user has an active plan and wants to change to another plan.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		//See if the user currently has an active plan or promotion. The userId is a unique field, so only one billing record can exist per user.
		$calculateRefund = Adrlist_Billing::calculateRefund($_SESSION['userId']);
		if(!is_array($calculateRefund)){
			throw new Adrlist_CustomException('','$calculateRefund returned false.');
		}
		$debug->printArray($calculateRefund,'$calculateRefund');
		$calculateRefund['refundAmount'] = '$' . $calculateRefund['refundAmount'];
		$beginningDate = Adrlist_Time::utcToLocal($calculateRefund['userPlanArray']['dateAdded'],false)->format('M j, Y');//Return a DateTime object.
		$nextBillingDate = Adrlist_Time::addToDate($calculateRefund['userPlanArray']['dateAdded'],$calculateRefund['userPlanArray']['period'],$calculateRefund['userPlanArray']['length']);
		$nextBillingDate = Adrlist_Time::utcToLocal($nextBillingDate,false)->format('M j, Y');//Return a DateTime object.
		$output .= '<div class="textMedium textLeft" style="margin:2em;line-height:1.5em">
Please select a new plan. You current plan is <span class="bold">' . $calculateRefund['userPlanArray']['name'] . '</span>.<br>
You will directed to the payment processor to authorize a new payment and will receive a credit in the form of a pro-rated refund for the remainder of the current billing period.
<br>
<br>
' . $calculateRefund['daysRemaining'] . ' of ' . $calculateRefund['billingPeriodDays'] . ' days remaining in this billing period (' . $beginningDate . ' to ' . $nextBillingDate . ').<br>
Refund Amount: ' . $calculateRefund['refundAmount'] . '
</div>' . buildBillingOffers();
		$returnThis['billingOfferId'] = $calculateRefund['userPlanArray']['billingOfferId'];
		$returnThis['output'] = $output;
		$success = true;
		//Work to be done here.
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'changePlan'){
		returnData();
	}
}

function purchasePlanAmazon(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['billingOfferId'])){
			throw new Adrlist_CustomException('','$_POST[\'billingOfferId\'] is empty.');
		}elseif(!is_numeric($_POST['billingOfferId'])){
			throw new Adrlist_CustomException('','$_POST[\'billingOfferId\'] is not numeric.');
		}
		//Build the Amazon recurring payment authorization request.
		$url = Adrlist_Billing::amazonAuthorization($_SESSION['userId'],$_POST['billingOfferId']);
		if(empty($url)){
			throw new Adrlist_CustomException('','$url is empty.');
		}
		$returnThis['url'] = $url;
		if(MODE == 'purchasePlanAmazon'){
			$success = true;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'purchasePlanAmazon'){
		returnData();
	}
}

function saveMyInformation(){
	/*Save the updated user information.
	//This has become a rather complex and lengthy script. The best way to handle it is to compare the current information to the new information to see what has changed. Then do verifications on the changed information.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		//The secondary email and new password fields are optional, so we must test them separately from the rest.
		if(empty($_POST['firstName'])){
			throw new Adrlist_CustomException('','$_POST[\'firstName\'] is empty.');
		}elseif(strlen($_POST['firstName']) > 255){
			throw new Adrlist_CustomException('','$_POST[\'firstName\'] is more than 255 characters.');
		}elseif(empty($_POST['lastName'])){
			throw new Adrlist_CustomException('','$_POST[\'lastName\'] is empty.');
		}elseif(strlen($_POST['lastName']) > 255){
			throw new Adrlist_CustomException('','$_POST[\'lastName\'] is more than 255 characters.');
		}elseif(empty($_POST['primaryEmail'])){
			throw new Adrlist_CustomException('','$_POST[\'primaryEmail\'] is empty.');
		}elseif(!emailValidate($_POST['primaryEmail'])){
			throw new Adrlist_CustomException('','$_POST[\'primaryEmail\'] is not a valid email address.');		
		}elseif(empty($_POST['primaryEmailRetype'])){
			throw new Adrlist_CustomException('','$_POST[\'primaryEmailRetype\'] is empty.');
		}elseif($_POST['primaryEmail'] != $_POST['primaryEmailRetype']){
			throw new Adrlist_CustomException("The primary email addresses don't match.",'');
		}elseif(empty($_POST['currentPassword'])){
			throw new Adrlist_CustomException('','$_POST[\'currentPassword\'] is empty.');
		}elseif(!passwordValidate($_POST['currentPassword'])){
			throw new Adrlist_CustomException('','$_POST[\'currentPassword\'] is not valid.');
		}
		$_POST['firstName'] = trim($_POST['firstName']);
		$_POST['lastName'] = trim($_POST['lastName']);
		$_POST['primaryEmail'] = trim($_POST['primaryEmail']);
		$_POST['currentPassword'] = trim($_POST['currentPassword']);
		$_POST['newPassword'] = trim($_POST['newPassword']);
		$_POST['secondaryEmail'] = trim($_POST['secondaryEmail']);
		$toAddress = array();
		$Dbc->beginTransaction();
		//Verify the user has entered the correct current password. Grab other info to check what has been changed.
		$stmt = $Dbc->prepare("SELECT
	firstName AS 'firstName',
	lastName AS 'lastName',
	primaryEmail AS 'primaryEmail',
	secondaryEmail AS 'secondaryEmail',
	userPassword AS 'password'
FROM
	users
WHERE
	userId = ? AND
	userPassword = ?");
		$sha1CurrentPassword = sha1($_POST['currentPassword']);
		$sha1NewPassword = sha1($_POST['newPassword']);
		$params = array($_SESSION['userId'],$sha1CurrentPassword);
		$stmt->execute($params);
		$currentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
		$debug->printArray($currentInfo,'$currentInfo');
		$debug->printArray($_POST,'$_POST');
		if(empty($currentInfo['password'])){
			pdoError(__LINE__, $stmt, $params, true);
			throw new Adrlist_CustomException('Your password could not be verified. Please re-enter your current password.','');
		}
		$debug->add('The user has entered the correct current password.');
		if(!empty($currentInfo['secondaryEmail'])){
			$toAddress[] = $currentInfo['secondaryEmail'];
		}
		$newInformationArray = array('First Name' => $_POST['firstName'], 'Last Name' => $_POST['lastName'],'Primary Email Address' => $_POST['primaryEmail'], 'Secondary Email Address' => $_POST['secondaryEmail']);
		//Check if the password has changed.
		if(empty($_POST['newPassword'])){
			$returnThis['pass'] = $_POST['currentPassword'];
			$newInformationArray['Password'] = $sha1CurrentPassword;
		}elseif($_POST['newPassword'] != $_POST['newPasswordRetype']){
			throw new Adrlist_CustomException('The new passwords don\'t match. Please re-enter a new password.','');
		}elseif(!passwordValidate($_POST['newPassword'])){
			throw new Adrlist_CustomException('The new password you entered contains invalid characters. Please enter a valid password.','');
		}else{
			//Update the password.
			$stmt = $Dbc->prepare("UPDATE
	users
SET
	userPassword = ?
WHERE
	userId = ?");
			$params = array($sha1NewPassword,$_SESSION['userId']);
			$stmt->execute($params);
			$returnThis['pass'] = $_POST['newPassword'];
			$newInformationArray['Password'] = $sha1NewPassword;
		}
		//Compare the information in the database with the new information to report what has changed.
		$changes = array_diff($newInformationArray,$currentInfo);
		$debug->printArray($changes,'$changes');
		if(empty($changes)){
			$message .= 'No changes were made.<br>';
		}else{
			//Update the secondary email only if it has changed and isn't empty.
			if(array_key_exists('Secondary Email Address',$changes)){
				$debug->add('I detect that the Secondary Email Address has been changed.');
				//Verify the new secondary email is different from the current and new primary email, and the re-type matches.
				if(empty($_POST['secondaryEmail'])){
					//The user has removed a secondary email. Set the secondary email to null.
					$stmt = $Dbc->prepare("UPDATE
	users
SET
	secondaryEmail = ?
WHERE
	userId = ?");
					$params = array(NULL,$_SESSION['userId']);
					$stmt->execute($params);
				}elseif($_POST['secondaryEmail'] != $currentInfo['primaryEmail'] && $_POST['secondaryEmail'] != $_POST['primaryEmail'] && $_POST['secondaryEmail'] == $_POST['secondaryEmailRetype'] && emailValidate($_POST['secondaryEmail'])){
					//Check to see if secondaryEmail is used by another user as either a primary or secondary email.
					$debug->add('About to check the Secondary Email Address.');
					$stmt = $Dbc->prepare("SELECT
	userId AS 'userId'
FROM
	users
WHERE
	secondaryEmail = ? OR
	primaryEmail = ? AND
	userId <> ?");
					$params = array($_POST['secondaryEmail'],$_POST['secondaryEmail'],$_SESSION['userId']);
					$stmt->execute($params);
					$row = $stmt->fetch(PDO::FETCH_ASSOC);
					if(empty($row['userId']) && empty($row['userId'])){
						pdoError(__LINE__, $stmt, $params, true);
						$debug->add('As there are no users with the secondary email address ' . $_POST['secondaryEmail'] . ' this user can use it.');
						//Update secondary email.
						$stmt = $Dbc->prepare("UPDATE
	users
SET
	secondaryEmail = ?
WHERE
	userId = ?");
						$stmt->execute(array($_POST['secondaryEmail'],$_SESSION['userId']));
						$toAddress[] = $_POST['secondaryEmail'];
					}else{
						throw new Adrlist_CustomException('The Secondary Email Address your entered is associated with another account.<br>
<div style="height:.6em"></div>
Please choose a different Secondary Email Address.<br>','');
					}
				}else{
					if($_POST['secondaryEmail'] == $currentInfo['primaryEmail']){
						$message .= 'The Primary and Secondary Email Addresses must be different.<br>';
					}elseif($_POST['secondaryEmail'] != $_POST['secondaryEmailRetype']){
						$message .= 'The secondary email addresses don\'t match.<br>';
					}elseif(!emailValidate($_POST['secondaryEmail'])){
						$debug->add('$_POST[\'secondaryEmail\'] is not a valid email address.<br>
<div style="height:.6em"></div>
Please enter a valid email address.');		
					}
				}
			}
			//Update the Primary Email Address only if it has changed.
			if(array_key_exists('Primary Email Address',$changes)){
				$debug->add('I detect that the Primary Email Address has been changed.');
				//Verify the new Primary Email is different from the Secondary Email.
				if($_POST['primaryEmail'] == $currentInfo['secondaryEmail']){
					throw new Adrlist_CustomException('The Primary and Secondary email addresses must be different.','');
				}
				//Check to see if the primary email address is used by another user.
				$debug->add('About to check the Primary Email Address.');
				$stmt = $Dbc->prepare("SELECT
	userId AS 'userId'
FROM
	users
WHERE
	secondaryEmail = ? OR
	primaryEmail = ? AND
	userId <> ?");
				$params = array($_POST['primaryEmail'],$_POST['primaryEmail'],$_SESSION['userId']);
				$stmt->execute($params);
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				if(!empty($row['userId'])){
					throw new Adrlist_CustomException('The Primary Email Address your entered is associated with another account.<br>
<div style="height:.6em"></div>
Please enter a different Primary Email Address.<br>','');
				}
				pdoError(__LINE__, $stmt, $params, true);
				$debug->add('As there are no users with the email address ' . $_POST['primaryEmail'] . ' this user can use it.');
				//Update the user's Primary Email Address.
				$stmt = $Dbc->prepare("UPDATE
	users
SET
	primaryEmail = ?
WHERE
	userId = ?");
				$params = array($_POST['primaryEmail'],$_SESSION['userId']);
				$stmt->execute($params);
				$toAddress[] = $_POST['primaryEmail'];
			}
			//Update the rest of the info.
			$stmt = $Dbc->prepare("UPDATE
	users
SET
	firstName = ?,
	lastName = ?
WHERE
	userId = ? AND
	userPassword = ?");
			$params = array($_POST['firstName'],$_POST['lastName'],$_SESSION['userId'],$sha1CurrentPassword);
			$stmt->execute($params);
			//Record the changes made.
			$userChangesStmt = $Dbc->prepare("INSERT INTO userChanges SET
	userId = ?,
	oldPrimaryEmail = ?,
	newPrimaryEmail = ?,
	oldSecondaryEmail = ?,
	newSecondaryEmail = ?,
	oldPassword = ?,
	newPassword = ?,
	oldFirstName = ?,
	newFirstName = ?,
	oldLastName = ?,
	newLastName = ?,
	dateChanged = ?");
			$userChangesParams = array($_SESSION['userId'],$currentInfo['primaryEmail'],$_POST['primaryEmail'],$currentInfo['secondaryEmail'],$_POST['secondaryEmail'],$currentInfo['password'],$sha1NewPassword,$currentInfo['firstName'],$_POST['firstName'],$currentInfo['lastName'],$_POST['lastName'],DATETIME);
			$userChangesStmt->execute($userChangesParams);
			$changesListText = '';
			$changesListHtml = '';
			foreach($changes as $key => $value){
				$changesListText .= "- $key
";
				$changesListHtml .= "&#8226; $key<br>";
			}
			$subject = 'Changes have been made to your ' . THENAMEOFTHESITE . ' account';
			$bodyText = 'The following changes have been made to your ' . THENAMEOFTHESITE . ' account:
' . $changesListText . '
If you did not authorize these changes please <a href="' . LINKSUPPORT . '">contact support</a>. 

This is an automated message. Please do not reply.';
				$bodyHtml = 'The following changes have been made to your account:<br>
' . $changesListHtml . '<br>
If you did not authorize these changes please <a href="' . LINKSUPPORT . '">contact support</a>.';
			$debug->printArray($toAddress,'$toAddress');
			if(email(EMAILDONOTREPLY,$currentInfo['primaryEmail'],$subject,$bodyHtml,$bodyText)){
				$Dbc->commit();
				$message .= 'Saved My Information';
				$success = MODE == 'saveMyInformation' ? true : $success;
				if(!empty($toAddress)){
					foreach($toAddress as $value){
						email('donotreply@' . DOMAIN,$value,$subject,$bodyHtml,$bodyText);
					}
				}
			}else{
				throw new Adrlist_CustomException('','There was a problem trying to send an email.');
			}
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'saveMyInformation'){
		returnData();
	}else{
		return $output;
	}
}

function saveSettings(){
	//Save the user's settings.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['timeZone'])){
			throw new Adrlist_CustomException('','$_POST[\'timeZone\'] is empty.');
		}elseif(empty($_POST['dateFormat'])){
			throw new Adrlist_CustomException('','$_POST[\'dateFormat\'] is empty.');
		}elseif(!isset($_POST['viewListOnLogin'])){
			throw new Adrlist_CustomException('','$_POST[\'viewListOnLogin\'] is not set.');
		}elseif(!isset($_POST['defaultShowCharacterColors'])){
			throw new Adrlist_CustomException('','$_POST[\'defaultShowCharacterColors\'] is not set.');
		}
		$debug->add('$_POST[\'dateFormat\']: ' . $_POST['dateFormat'] . '<br>
$_POST[\'viewListOnLogin\']: ' . $_POST['viewListOnLogin'] . '<br>
$_POST[\'defaultShowCharacterColors\']: ' . $_POST['defaultShowCharacterColors']);
		//Get the dateFormat.
		$dateFormatArray = Adrlist_Time::getDateFormats();
		list($dateFormat,$example) = $dateFormatArray[$_POST['dateFormat']];
		$_SESSION['dateFormat'] = $dateFormat;
		$viewListOnLogin = $_POST['viewListOnLogin'] === 'true' ? 1 : 0;
		$defaultShowCharacterColors = $_POST['defaultShowCharacterColors'] === 'true' ? 1 : 0;
		$debug->add('viewListOnLogin: ' . "$viewListOnLogin" . '<br>
defaultShowCharacterColors: ' . "$defaultShowCharacterColors.");
		$stmt = $Dbc->prepare("UPDATE
	userSiteSettings
SET
	timeZone = ?,
	dateFormatId = ?,
	viewListOnLogin = ?,
	defaultShowCharacterColors = ?
WHERE
	userSiteSettings.userId = ?");
		$params = array($_POST['timeZone'],$_POST['dateFormat'],$viewListOnLogin,$defaultShowCharacterColors,$_SESSION['userId']);
		$stmt->execute($params);
		$message .= 'Saved Settings';
		$success = MODE == 'saveSettings' ? true : $success;//It's okay if no lines were updated by these queries.
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'saveSettings'){
		returnData();
	}
}