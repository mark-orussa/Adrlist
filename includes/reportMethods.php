<?php /*
This file and it's functions are to be used solely by ../lists/report.php in conjunction with ../js/report.js.
*/
$fileInfo = array('fileName' => 'includes/reportMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(!empty($_POST['mode']) && isset($_SESSION['listId']) && ((boolean) $_SESSION['listId'])){
	define('MODE', $_POST['mode']);
	$debug->add('MODE: ' . MODE);
	if(isset($_SESSION['listRoleId']) && $_SESSION['listRoleId'] > 1){
		if($_POST['mode'] == 'buildReport'){
			buildReport();
		}elseif($_POST['mode'] == 'buildTRT'){
			buildTRT();
		}else{
			$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
		}
	}
}else{
	define('MODE', '');
	if(empty($_POST['mode'])){
		$debug->add('$_POST[\'mode\'] is empty.');
	}elseif(!isset($_SESSION['listId'])){
		$debug->add('$_SESSION[\'listId\'] is not defined.');
	}elseif(!((boolean) $_SESSION['listId'])){
		$debug->add('$_SESSION[\'listId\'] does not contain a value.');
	}else{
		$debug->add('Something else is wrong.');	
	}
}

//Build the report.-----------------------------------------------------------------------------------------------
function buildReport(){
	global $debug, $message, $success, $Dbc;
	try{
		$output = '
	<div class="indent textLeft" id="reportReturn">';
		$Dbc->beginTransaction();
		//Get the user's lists. User must be a list admin or list owner.
		$stmt = $Dbc->prepare("SELECT
	characters.charId AS 'charId',
	characters.charName AS 'charName',
	COUNT(DISTINCT characters.charId) AS 'charCount',
	COUNT(linesTable.lineId) AS 'lineCount',
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	lists.created AS 'created'
FROM
	lists
JOIN
	(userListSettings JOIN users ON userListSettings.userId = users.userId) ON userListSettings.listId = lists.listId
JOIN
	linesTable ON linesTable.listId = lists.listId
JOIN
	userSiteSettings ON userSiteSettings.listId = lists.listId
JOIN
	characters ON characters.charId = linesTable.charId AND
	(userListSettings.listRoleId > 2) AND
	users.userId = ? AND
	lists.listId = ?");
		$params = array($_SESSION['userId'],$_SESSION['listId']);
		$stmt->execute($params);
		$rowsFound = 0;
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$rowsFound = true;
			$output .= '
	<div class="textCenter" style="padding:10px 0px 0px 0px;">General Info for ' . $row['listName'] . '</div>
	<table class="textCenter" style="width:100%">
		<tr>
			<td class="tdRight">Created:</td>
			<td class="textLeft">' . Adrlist_Time::utcToLocal($row['created']) . '</td>
		</tr>
		<tr>
			<td class="tdRight">Lines:</td>
			<td class="textLeft">' . $row['lineCount'] . '</td>
		</tr>
		<tr>
			<td class="tdRight">Characters:</td>
			<td class="textLeft">' . $row['charCount'] . '</td>
		</tr>
	</table>';
		}
		if(!$rowsFound){
			$output .= 'There were no lines.';
		}
		$Dbc->commit();
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'buildReport'){
			returnData();
		}else{
			return $output;
		}
	}
	if(MODE == 'buildReport'){
		returnData();
	}else{
		return $output;
	}
}

function buildTRT(){
	//The total running time of all adr lines, according to the TC in and out points. Lines with malformed or missing TC are not counted.
	global $debug, $message, $success, $Dbc;
	try{
		$output = '';
		$Dbc->beginTransaction();
		$stmt = $Dbc->prepare("SELECT
	lineId as 'lineId',
	tcIn as 'tcIn',
	tcOut as 'tcOut'
FROM
	linesTable
WHERE
	listId = ? AND
	tcIn <> ? AND
	tcOut <> ?");
		$params = array($_SESSION['listId'],'','');
		$stmt->execute($params);
		$rowsFound = 0;
		$hours = 0;
		$minutes = 0;
		$seconds = 0;
		$frames = 0;
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			//Validate the tc first.
			$output .= 'In: ' . $row['tcIn'] . ', Out: ' . $row['tcOut'] . '<br>';
			$tcInNumbers = str_replace(':','',$row['tcIn']);
			$tcOutNumbers = str_replace(':','',$row['tcOut']);
			$thisCount = $tcOutNumbers - $tcInNumbers;
			if($thisCount < 0){
				return 'The TC Out value is earlier than the TC In value for line ID: ' . $row['lineId'] . '.<br>';
			}else{
				$hours = 
				$count += $thisCount;
			}
			$tcInArray = splitTC($row['tcIn']);
			$tcOutArray = splitTC($row['tcOut']);
			$rowsFound++;
		}
		if(!$rowsFound){
			$output .= 'There were no valid time code fields.';
		}
		$Dbc->commit();
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'buildTRT'){
			returnData();
		}else{
			return $output;
		}
	}
	if(MODE == 'buildTRT'){
		returnData();
	}else{
		return $output;
	}
}

function validateTC($tcValue){
	//Checks to see if the tc value is properly formatted like HH:MM:SS:FF.
	global $debug, $message;
	$tcValue = trim($tcValue);
	if(strlen($tcValue) != 11){
		return false;
	}else{
		//Add preg expression to check for the format HH:MM:SS:FF.
		$pattern = '^\d{2}:\d{2}:\d{2}:\d{2}$';
		preg_match($pattern,$tcValue,$matches);
		if(is_array($matches)){
			$debug->printArray($matches);
		}
		return true;
	}
}
