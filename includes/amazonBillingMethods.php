<?php $fileInfo = array('title' => '', 'fileName' => 'includes/amazonBillingMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'buildAmazonBilling'){
	buildAmazonBilling();
}elseif(MODE == 'addMonth'){
	addMonth();
}else{
	$debug->add('There is no matching mode in ' . $fileInfo['fileName'] . '.');
}

function addMonth(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['date'])){
			throw new Adrlist_CustomException();
		}
		$date = Adrlist_Time::addToDate($_POST['date'],'month',1);
		$date = $date->format('Y-m-d');
		if(MODE == 'addMonth'){
			$success = true;
			$returnThis['output'] = $date;
		}
	}catch(Adrlist_CustomException $e){
	}
	if(MODE == 'addMonth'){
		returnData();
	}else{
		return $output;
	}
}

function buildAmazonBilling(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		//See if the user has an account.
		$accountCheckCountStmt = "SELECT
	COUNT(*) AS 'count'
FROM
	billingOffers
JOIN
	userBilling ON userBilling.billingOfferId = billingOffers.billingOfferId
JOIN
	users ON users.userId = userBilling.userId
WHERE
	billingOffers.renewable = 1";
		$accountCheckStmt = "SELECT
	userBilling.userId AS 'userId',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName)) as 'userName',
	billingOffers.billingOfferId AS 'billingOfferId',
	billingOffers.offerName AS 'planName',
	billingOffers.period AS 'period',
	userBilling.dateAdded AS 'dateAdded'
FROM
	billingOffers
JOIN
	userBilling ON userBilling.billingOfferId = billingOffers.billingOfferId
JOIN
	users ON users.userId = userBilling.userId
WHERE
	billingOffers.renewable = 1";
		if(empty($_POST['searchVal'])){
			$search = false;
			$accountCheckParams = array();
			$accountCheckCountStmt = $Dbc->prepare($accountCheckCountStmt);
		}else{
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchval: ' . $searchVal);
			$endStmt = " AND
	(users.firstName LIKE ? || users.lastName LIKE ? || billingOffers.offerName LIKE ?)
";
			$accountCheckStmt .= $endStmt;
			$accountCheckParams = array($searchVal,$searchVal,$searchVal);
			$accountCheckCountStmt = $Dbc->prepare($accountCheckCountStmt . $endStmt);
		}
		$accountCheckCountStmt->execute($accountCheckParams);
		$count = $accountCheckCountStmt->fetch(PDO::FETCH_ASSOC);
		$itemCount = $count['count'];
		$pagination = new Adrlist_Pagination('buildAmazonBilling','buildAmazonBilling',$itemCount,'Search Billing',$search);
		list($offset,$limit) = $pagination->offsetLimit();
		$accountCheckStmt .= "
LIMIT $offset, $limit";
		$accountCheckStmt = $Dbc->prepare($accountCheckStmt);
		$accountCheckStmt->execute($accountCheckParams);
		$userPlans = array();
		$foundRows = false;
		while($row = $accountCheckStmt->fetch(PDO::FETCH_ASSOC)){
			$foundRows = true;
			//Add the question to the user's support section.
			if($row['period'] == 'month'){
				$date = Adrlist_Time::addToDate($row['dateAdded'],$row['period'],1);
				$row[] = $date = $date->format('Y-m-d');
			}elseif($row['period'] == 'year'){
				$date = Adrlist_Time::addToDate($row['dateAdded'],$row['period'],1);
				$row[] = $date = $date->format('Y-m-d');
			}
			$userPlans[] = $row;
		}
		$cssWidths = array(3,20,10,20,5,20,20);
		$titleRowArray = array('userId','User','billingOfferId','Plan Name','Period','Date Added','Next Billing Date');
		$buildRows = new Adrlist_BuildRows($titleRowArray,$userPlans,$cssWidths);
		$output .= '<div>
	<input type="text" style="width:20em" id="billingDate"> Date <span class="buttonBlueThin" id="addMonth">Add a Month</span> <input type="text" id="dateDestination">
</div>' . $pagination->output();
		$output .= $foundRows ? $buildRows->output() : '<div class="textCenter" style="margin:1em">No records were found.</div>';
		if(MODE == 'buildAmazonBilling'){
			$success = true;
			$returnThis['holder'] = 'amazonBillingHolder';
			$returnThis['output'] = $output;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildAmazonBilling'){
		returnData();
	}else{
		return $output;
	}
}
