<?php require_once('siteAdmin.php');
/* This file and it's functions are to be used solely by ../admin/adminTools.php in conjunction with ../js/adminTools.js. */
$fileInfo = array('fileName' => 'includes/adminToolsMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'setMaintMode'){
	maintSwitch();
}elseif(MODE == 'buildListMaint'){
	buildListMaint();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function debugSwitch(){
	global $debug, $message;
	$output = 'Use this button to produce a cookie that will display debug information in the browser. Without this cookie, no other users are affected.
<div style="margin:2em 0;">
	<button class="ui-btn ui-btn-inline ui-btn-a ui-shadow ui-corner-all" id="debugButton">Turn Debug Mode ';
	if(isset($_COOKIE['DEBUG']) && $_COOKIE['DEBUG'] == true){
		$output .= 'Off';
	}else{
		$output .= 'On';
	}
	$output .= '</button>
</div>';
	return $output;
}

function maintSwitch(){
	//All date and time values stored in mysql should be in UTC.
	global $debug, $message, $success, $Dbc;
	$output = '';
	try{
		if(MODE == 'setMaintMode'){
			if(empty($_POST['maintModeStartTime']) || empty($_POST['maintModeEndTime'])){
				$params = array($_SESSION['userId'],null,null);
			}else{
				$maintModeStartTime = Adrlist_Time::localToUtc($_POST['maintModeStartTime'],false);
				$maintModeStartTime = $maintModeStartTime->format('Y-m-d H:i:s');
				$maintModeEndTime = Adrlist_Time::localToUtc($_POST['maintModeEndTime'],false);
				$maintModeEndTime = $maintModeEndTime->format('Y-m-d H:i:s');
				$params = array($_SESSION['userId'],$maintModeStartTime,$maintModeEndTime);
			}
			$stmt = $Dbc->prepare("UPDATE
	adminControl
SET
	userId = ?,
	maintModeStartTime = ?,
	maintModeEndTime = ?");
			$stmt->execute($params);
			$success = true;
			pdoError(__LINE__,$stmt,$params,0);				
			returnData();
		}else{
			$stmt = $Dbc->query("SELECT
	maintModeStartTime AS 'maintModeStartTime',
	maintModeEndTime AS 'maintModeEndTime'
FROM
	adminControl");
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$startTimeDisplay = TIMESTAMP > strtotime($row['maintModeStartTime']) ? TIMESTAMP : $row['maintModeStartTime'];
			$startTimeDisplay = Adrlist_Time::utcToLocal($startTimeDisplay,false)->format('F d, Y H:i:s');
			$endTimeDisplay = Adrlist_Time::addToDate(TIMESTAMP, 'hour', 1);
			$endTimeDisplay = TIMESTAMP > strtotime($row['maintModeEndTime']) ? $endTimeDisplay : $row['maintModeEndTime'];
			$endTimeDisplay = Adrlist_Time::utcToLocal($endTimeDisplay,false)->format('F d, Y H:i:s');
			$output .= '			<p>
				Maintenance mode will prevent all non-admin user access to the authorized sections of the site. It is highly recommended that this mode be used to perform updates and changes to the site.
			</p>
			<p>
				Both must be valid dates for maint mode to function. All dates are shown in local time according to your saved timezone setting.
			</p>
			<div class="center textCenter">
				<div class="ui-field-contain">
					<label class="bold" for="maintModeStartTime">Start on</label>
					<input type="text" id="maintModeStartTime" value="' . $startTimeDisplay . '">
				</div>
				<button class="ui-btn ui-btn-inline ui-btn-a ui-shadow ui-corner-all" id="clearMaintModeStartTime">Clear</button>
				<div class="ui-field-contain">
					<label class="bold" for="maintModeEndTime">End on</label>
					<input type="text" id="maintModeEndTime" value="' . $endTimeDisplay . '">
				</div>
				<button class="ui-btn ui-btn-inline ui-btn-a ui-shadow ui-corner-all" id="clearMaintModeEndTime">Clear</button>
				<button class="ui-btn ui-btn-inline ui-btn-a ui-shadow ui-corner-all" id="maintModeSave">Save</button>
			</div>
';
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		returnData();
	}
	return $output;
}

function buildListMaint(){
	/*
	Find errors in the database:
	 1. Lines missing listId, charId, and/or cId.
	 2. Folders or lists with more than one owner or no owner.
	 3. Verify that all users of lists inside folders have a folderRoleId.
	 4. Verify that all users of lists inside folders have a userSiteSettings.
	 */
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		//Check for lines missing listId, charId, and/or cId.
		$badLinesCountStmt = $Dbc->query("SELECT
	COUNT(*) AS 'count'
FROM
	linesTable
WHERE
	listId = '' OR
	listId IS NULL OR
	charId = '' OR
	charId IS NULL OR
	cId = '' OR
	cId IS NULL");
		$badLinesCountStmt->execute();
		$badLinesCount = $badLinesCountStmt->fetch(PDO::FETCH_ASSOC);
		$badLinesCount = $badLinesCount['count'];
		$badLinesPagination = new Adrlist_Pagination('buildListMaint','badLines',$badLinesCount);
		list($offset,$limit) = $badLinesPagination->offsetLimit();
		$checkLinesStmt = $Dbc->query("SELECT
	linesTable.lineId AS 'lineId',
	linesTable.listId AS 'listId',
	lists.listName AS 'listName',
	linesTable.charId AS 'charId',
	linesTable.cId AS 'cId',
	linesTable.line AS 'line'
FROM
	linesTable
LEFT JOIN
	lists ON lists.listId = linesTable.listId
WHERE
	linesTable.listId = '' OR
	linesTable.listId IS NULL OR
	linesTable.charId = '' OR
	linesTable.charId IS NULL OR
	linesTable.cId = '' OR
	linesTable.cId IS NULL
LIMIT $offset, $limit");
		$checkLinesStmt->execute();
		$badLines = array();
		$foundBadLines = false;
		while($row = $checkLinesStmt->fetch(PDO::FETCH_ASSOC)){
			$badLines[] = $row;
			$foundBadLines = true;
		}
		//$debug->printArray($badLines,'$badLines');

		//Verify all lists have one owner.
		$multipleListOwnersCountStmt = $Dbc->query("SELECT
	COUNT(*) AS 'count'
FROM
	lists
JOIN
	(userListSettings JOIN users ON userListSettings.userId = users.userId) ON lists.listId = userListSettings.listId AND
	userListSettings.listRoleId = 4 AND
	lists.listId IN (SELECT listId FROM userListSettings WHERE listRoleId = 4 GROUP BY listId HAVING COUNT(userId)>1)");
		$multipleListOwnersCountStmt->execute();
		$multipleListOwnersCount = $multipleListOwnersCountStmt->fetch(PDO::FETCH_ASSOC);
		$multipleListOwnersCount = $multipleListOwnersCount['count'];
		$mulitpleListOwnersPagination = new Adrlist_Pagination('buildListMaint','multipleListOwners',$multipleListOwnersCount);
		list($offset,$limit) = $mulitpleListOwnersPagination->offsetLimit();
		$multipleListOwnersStmt = $Dbc->query("SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	lists.cId AS 'cId',
	users.userId AS 'userId',
	primaryEmail AS 'primaryEmail',
	CONCAT_WS(' ', users.firstName, users.lastName) AS 'userName'
FROM
	lists
JOIN
	(userListSettings JOIN users ON userListSettings.userId = users.userId) ON lists.listId = userListSettings.listId AND
	userListSettings.listRoleId = 4 AND
	lists.listId IN (SELECT listId FROM userListSettings WHERE listRoleId = 4 GROUP BY listId HAVING COUNT(userId)>1)
ORDER BY
	lists.listId ASC
LIMIT $offset, $limit");
		$multipleListOwnersStmt->execute();
		$listOwners = array();
		$foundMultipleListOwners = false;
		while($row = $multipleListOwnersStmt->fetch(PDO::FETCH_ASSOC)){
			$multipleListOwners[] = $row;
			$foundMultipleListOwners = true;
		}
		//$debug->printArray($listOwners,'$listOwners');
		
		
		if($foundBadLines){
			$badLinesTitleArray = array(
				array('lineId'),
				array('listId'),
				array('List Name'),
				array('charId'),
				array('cId'),
				array('line')
			);
			$buildBadLines = new Adrlist_BuildRows('badLines',$badLinesTitleArray,$badLines);
			$badLinesOutput = $badLinesPagination->output() . $buildBadLines->output();
		}else{
			$badLinesOutput = '<div class="break textCenter">
	All lines are good.
</div>';
		}
		if($foundMultipleListOwners){
			$multipleListOwnersTitleArray = array(
				array('listId'),
				array('List Name'),
				array('cId'),
				array('userId'),
				array('Email'),
				array('Name')
			);
			$multipleListOwnersBuildRows = new Adrlist_BuildRows('multipleOwners',$multipleListOwnersTitleArray,$multipleListOwners);
			$multipleOwnersOuput = $mulitpleListOwnersPagination->output() . $multipleListOwnersBuildRows->output();
		}else{
			$multipleOwnersOuput = '<div class="break textCenter">
	All lists have proper ownership.
</div>';
		}
		
		
		//Build the output.		
		$output .= '<div class="bold textCenter textLarge">Bad Lines</div>
	' . $badLinesOutput . '
<div class="break" style="margin-top:2em">
	<div class="bold textCenter textLarge">Multiple List Owners</div>
	' . $multipleOwnersOuput . '
</div>';
	if(MODE == 'buildListMaint'){
			$success = true;
			$returnThis['holder'] = 'listMaintHolder';
			$returnThis['output'] = $output;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildListMaint'){
		returnData();
	}else{
		return $output;
	}
}

function addTimezones(){//Not needed?
	/*
	WARNING: Running this function could change the timezones of all users, throwing off all time calculations. Do not use unless you fully understand the ramifications of your actions.
	This should only be used when the associated database table is empty. This will only add timezones to it.
	*/
	global $debug, $message, $Dbc;
	try{
		$now = time(); // ensure that we have the same time throughout
		//Get all listed timezones.
		$timezones = DateTimeZone::listIdentifiers();
		//$debug->printArray($timezones,'$timezones');
		//An array of timezones and their current time, ie America/Vancouver => 4:12 AM.
		$currentTimezones = array();
		foreach($timezones AS $timezone)
		{
			//Instantiate a DateTime object for each timezone.
			$dt = new DateTime('@'.$now);
			//Set the DateTime object to the current timezone.
			$dt->setTimeZone(new DateTimeZone($timezone));
			//Get the current time for this timezone.
			$time = $dt->format('g:i A');
			//Place it in the array.
			$currentTimezones[$timezone] = $time;
		}
		//Filter to get rid of times that don't have time zones.
		$currentTimezones = array_filter($currentTimezones);
		$debug->printArray($currentTimezones,'$currentTimezones');
		$errorReportingStmt = $Dbc->prepare("INSERT INTO
	newTimezones
SET
	timeZone = ?");
		foreach($currentTimezones as $key => $value){
			$errorReportingStmt->execute(array($key));
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	
}