<?php //This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
require_once('siteAdmin.php');
$fileInfo = array('fileName' => 'includes/payPalMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'buildIpn'){
	buildIpn();
}elseif(MODE == 'buildIpnErrors'){
	buildIpnErrors();
}elseif(MODE == 'buildPdt'){
	buildIpnErrors();
}elseif(MODE == 'buildPdtErrors'){
	buildIpnErrors();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function buildIpn(){
	global $debug, $message, $success, $Dbc;
	$output = '';
	try{
		$ipnQueryStart = "SELECT
	ipnId AS 'ipnId',
	time AS 'time',
	txn_id AS 'txn_id',
	txn_type AS 'txn_type',
	payment_status AS 'payment_status',
	queryString AS 'queryString',
	request AS 'request'
FROM
	ipnListener";
		if(!empty($_POST['searchVal'])){
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchVal: ' . "$searchVal.");
			$ipnQuery = $ipnQueryStart . "
WHERE
	ipnId LIKE ? OR
	txn_id LIKE ? OR
	txn_type LIKE ? OR
	payment_status LIKE ? OR
	queryString LIKE ? OR
	request LIKE ?
ORDER BY
	ipnId DESC";
			$ipnStmt = $Dbc->prepare($ipnQuery);
			$ipnParams = array($searchVal,$searchVal,$searchVal,$searchVal,$searchVal,$searchVal);
			$ipnStmt->execute($ipnParams);
		}else{
			$search = false;
			$searchVal = '';
			$ipnQuery = $ipnQueryStart . "
ORDER BY
	ipnId DESC";
			$ipnStmt = $Dbc->prepare($ipnQuery);
			$ipnStmt->execute();
		}
		$class = 'rowAlt';
		$foundRows = false;
		$content = '';
		while($row = $ipnStmt->fetch(PDO::FETCH_ASSOC)){
			$foundRows = true;
			$ipnId = $row['ipnId'];
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$time = Adrlist_Time::utcToLocal($row['time']);
			$content .= '
		<div class="break ' . $class . '">
			<div class="absolute" style="line-height:.9em;right:8px">
				<div class="textRight textXsmall">IPN Id: ' . $row['ipnId'] . '</div>
				<div class="textRight textSmall">' . $time . '</div>
			</div>
			<div class="row textSmall" style="min-width:100px;width:20%">' . $row['txn_id'] . '</div>
			<div class="row textSmall" style="min-width:100px;width:20%">' . $row['txn_type'] . '</div>
			<div class="row textSmall" style="min-width:100px;width:20%">' . $row['payment_status'] . '</div>
			<div class="row" id="viewQueryString' . $row['ipnId'] . '" style="">
				<img alt="" class="bottom hand" height="16" src="' . LINKIMAGES . '/view.png" width="16"><span class="linkPadding" id="viewQueryStringText' . $row['ipnId'] . '">View Query String</span>
			</div>
			<div class="row" id="viewRequest' . $row['ipnId'] . '">
				<img alt="" class="bottom hand" height="16" src="' . LINKIMAGES . '/view.png" width="16"><span class="linkPadding" id="viewRequestText' . $row['ipnId'] . '">View Request</span>
			</div>
			<div class="break">
				<div class="row" style="width:50%">
					<div class="textLeft" id="queryStringHolder' . $row['ipnId'] . '" style="display:none">' . $row['queryString'] . '</div>
				</div>
				<div class="row" style="width:50%">
					<div class="textLeft" id="requestHolder' . $row['ipnId'] . '" style="display:none">' . $row['request'] . '</div>
				</div>
			</div>
		</div>';
		}
		if($foundRows){
			$output .= '<div class="rowTitle" style="min-width:100px;width:20%">txn_id</div>
			<div class="rowTitle" style="width:100px;width:20%">txn_type</div>
			<div class="rowTitle" style="width:100px;width:20%">payment_status</div>
			<div class="rowTitle" style="width:300px;width:40%">Actions</div>' . $content;
		}else{
			$message .= 'stuff and things';
			$output .= '<div class="break">No transactions found.</div>';
		}
		$success = true;
		$returnThis['buildIpn'] = $output;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'buildIpn'){
			returnData();
		}
	}
	if(MODE == 'buildIpn'){
		returnData();
	}else{
		return $output;
	}
}

function buildIpnErrors(){
	global $debug, $message, $success, $Dbc;
	$output = '';
	try{
		$ipnErrorQueryStart = "SELECT
	ipnError.ipnErrorId AS 'ipnErrorId',
	ipnError.time AS 'time',
	ipnError.errorMessage AS 'error'
FROM
	ipnError";
		if(!empty($_POST['searchVal'])){
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchVal: ' . "$searchVal.");
			$ipnErrorQuery = $ipnErrorQueryStart . "
WHERE
	ipnError.ipnErrorId LIKE ?
ORDER BY
	ipnError.ipnErrorId DESC";
			$ipnErrorStmt = $Dbc->prepare($ipnErrorQuery);
			$ipnErrorParams = array($searchVal);
			$ipnErrorStmt->execute($ipnErrorParams);
		}else{
			$search = false;
			$searchVal = '';
			$ipnErrorQuery = $ipnErrorQueryStart . "
ORDER BY
	ipnError.ipnErrorId DESC";
			$ipnErrorStmt = $Dbc->prepare($ipnErrorQuery);
			$ipnErrorStmt->execute();
		}
		$class = 'rowAlt';
		$foundRows = false;
		$content = '';
		while($row = $ipnErrorStmt->fetch(PDO::FETCH_ASSOC)){
			$ipnErrorId = $row['ipnId'];
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$time = Adrlist_Time::utcToLocal($row['time']);
			$content .= '
		<div class="break ' . $class . '">
			<div class="absolute" style="line-height:.9em;right:8px">
				<div class="textRight textXsmall">IPN Id: ' . $row['ipnErrorId'] . '</div>
				<div class="textRight textSmall">' . $time . '</div>
			</div>
			<div class="row textSmall" style="width:60px">' . $row['txn_id'] . '</div>
			<div class="textLeft">' . $row['error'] . '</div>
		</div>';
		}
		$success = true;
		if($foundRows){
			$output .= '<div class="rowTitle" style="width:100px">txn_id</div>
			<div class="rowTitle" style="width:300px">Actions</div>' . $content;
		}else{
			$output .= '<div class="break">No errors found.</div>';
		}		
		$returnThis['buildIpnErrors'] = $output;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'buildIpnErrors'){
			returnData();
		}
	}
	if(MODE == 'buildIpnErrors'){
		returnData();
	}else{
		return $output;
	}
}

function buildPdt(){
	global $debug, $message, $success, $Dbc;
	$output = '';
	try{
		$pdtQueryStart = "SELECT
	pdtId AS 'ipnId',
	time AS 'time',
	tx AS 'tx',
	st AS 'st',
	queryString AS 'queryString',
	request AS 'request'
FROM
	pdtListener";
		if(!empty($_POST['searchVal'])){
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchVal: ' . "$searchVal.");
			$pdtQuery = $pdtQueryStart . "
WHERE
	pdtId LIKE ?
ORDER BY
	pdtId DESC";
			$pdtStmt = $Dbc->prepare($pdtQuery);
			$pdtParams = array($searchVal);
			$pdtStmt->execute($pdtParams);
		}else{
			$search = false;
			$searchVal = '';
			$pdtQuery = $pdtQueryStart . "
ORDER BY
	pdtId DESC";
			$pdtStmt = $Dbc->prepare($pdtQuery);
			$pdtStmt->execute();
		}
		$class = 'rowAlt';
		$foundRows = false;
		$content = '';
		while($row = $pdtStmt->fetch(PDO::FETCH_ASSOC)){
			$ipnId = $row['pdtId'];
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$time = Adrlist_Time::utcToLocal($row['time']);
			$content .= '
		<div class="break ' . $class . '">
			<div class="absolute" style="line-height:.9em;right:8px">
				<div class="textRight textXsmall">PDT Id: ' . $row['pdtId'] . '</div>
				<div class="textRight textSmall">' . $time . '</div>
			</div>
			<div class="row textSmall" style="width:100px">' . $row['tx'] . '</div>
			<div class="row textSmall" style="width:100px">' . $row['st'] . '</div>
			<div class="row" id="viewQueryString' . $row['pdtId'] . '">
				<img alt="" class="bottom hand" height="16" src="' . LINKIMAGES . '/view.png" width="16"><span class="linkPadding" id="viewQueryStringText' . $row['pdtId'] . '">View Query String</span>
			</div>
			<div class="row" id="viewRequest' . $row['pdtId'] . '">
				<img alt="" class="bottom hand" height="16" src="' . LINKIMAGES . '/view.png" width="16"><span class="linkPadding" id="viewRequestText' . $row['pdtId'] . '">View Request</span>
			</div>
			<div class="break">
				<div class="row" style="width:50%">
					<div class="textLeft" id="queryStringHolder' . $row['pdtId'] . '" style="display:none">' . $row['queryString'] . '</div>
				</div>
				<div class="row" style="width:50%">
					<div class="textLeft" id="requestHolder' . $row['pdtId'] . '" style="display:none">' . $row['request'] . '</div>
				</div>
			</div>
		</div>';
		}
		if($foundRows){
			$output .= '<div class="rowTitle" style="width:100px">tx</div>
			<div class="rowTitle" style="width:100px">st</div>
			<div class="rowTitle" style="width:300px">Actions</div>' . $content;
		}else{
			$output .= '<div class="break">No transactions found.</div>';
		}
		$success = true;
		$returnThis['buildPdt'] = $output;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'buildPdt'){
			returnData();
		}
	}
	if(MODE == 'buildPdt'){
		returnData();
	}else{
		return $output;
	}
}

function buildPdtErrors(){
	global $debug, $message, $success, $Dbc;
	$output = '';
	try{
		$pdtErrorQueryStart = "SELECT
	pdtErrorId AS pdtErrorId,
	time AS 'time',
	errorMessage AS 'error'
FROM
	pdtError";
		if(isset($_POST['searchVal']) && !empty($_POST['searchVal'])){
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchVal: ' . "$searchVal.");
			$pdtErrorQuery = $pdtErrorQueryStart . "
WHERE
	pdtErrorId LIKE ?
ORDER BY
	pdtErrorId DESC";
			$pdtErrorStmt = $Dbc->prepare($pdtErrorQuery);
			$pdtErrorParams = array($searchVal);
			$pdtErrorStmt->execute($pdtErrorParams);
		}else{
			$search = false;
			$searchVal = '';
			$pdtErrorQuery = $pdtErrorQueryStart . "
ORDER BY
	pdtErrorId DESC";
			$pdtErrorStmt = $Dbc->prepare($pdtErrorQuery);
			$pdtErrorStmt->execute();
		}
		$class = 'rowAlt';
		$foundRows = false;
		$content = '';
		while($row = $pdtErrorStmt->fetch(PDO::FETCH_ASSOC)){
			$pdtErrorId = $row['ipnId'];
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$time = Adrlist_Time::utcToLocal($row['time']);
			$output .= '
		<div class="break ' . $class . '">
			<div class="absolute" style="line-height:.9em;right:8px">
				<div class="textRight textXsmall">PDT Id: ' . $row[pdtErrorId] . '</div>
				<div class="textRight textSmall">' . $time . '</div>
			</div>
			<div class="row textSmall" style="width:60px">' . $row['tx'] . '</div>
			<div class="textLeft">' . $row['error'] . '</div>
		</div>';
		}
		$success = true;
		$returnThis['returnCode'] = $output;
		if($foundRows){
			$output .= '<div class="rowTitle" style="width:60px">tx</div>
			<div class="rowTitle" style="width:300px">Actions</div>' . $content;
		}else{
			$output .= '<div class="break">No PDT errors found.</div>';
		}
		$success = true;
		$returnThis['buildPdtErrors'] = $output;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'buildPdt'){
			returnData();
		}
	}
	if(MODE == 'buildPdt'){
		returnData();
	}else{
		return $output;
	}
}
