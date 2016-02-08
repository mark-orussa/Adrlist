<?php
$fileInfo = array('fileName' => 'includes/timeZoneMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'determineTimeZone'){
	determineTimeZone();
	$debug->add('Made it to alpha zeta.');
}

function determineTimeZone(){
	//Build a drop down list of times every 15 minutes. This function is dependent on date_default_timezone_set('UTC').
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['timestampMilliseconds'])){
			throw new Adrlist_CustomException('','$_POST[\'timestamp\'] is empty.');
		}elseif(empty($_POST['offsetMinutes'])){
			throw new Adrlist_CustomException('','$_POST[\'offsetMinutes\'] is empty.');
		}
		$jsTimestamp = round(($_POST['timestampMilliseconds']-($_POST['offsetMinutes']*1000*60))/1000);
		$debug->add('$_POST[\'timestampMilliseconds\']: ' . $_POST['timestampMilliseconds'] . '<br>
$_POST[\'offsetMinutes\']: ' . $_POST['offsetMinutes'] . '<br>
$jsTimestamp: ' . "$jsTimestamp.");
		$now = time();
		$timeZones = DateTimeZone::listIdentifiers();
		$potentialTimeZones = array();
		$allTimeZones = array();
		foreach($timeZones as $timeZone){
			//Use the DateTime class to determine the local time for $location.
			$dt = new DateTime('@' . $now);//Accepts a strtotime() string.
			$dt->setTimeZone(new DateTimeZone($timeZone));//Change to a different timezone.
			//$timestamp = $dt->format('U');
			$formatted = $dt->format('M j, g:i A');
			$timestamp = strtotime($formatted);
			$allTimeZones[$timeZone] = $timestamp . ', ' . $formatted;
			if(abs($timestamp - $jsTimestamp) < 450){//7 1/2 minutes
				$potentialTimeZones[] = $timeZone;
			}
		}
		$debug->printArray($allTimeZones,'$allTimeZones');
		$debug->printArray($potentialTimeZones,'$potentialTimeZones');
		//If the user is logged in, select their current timezone.
		if(!empty($_SESSION['userId'])){
			$checkStmt = $Dbc->prepare("SELECT
	timeZone AS 'timeZone'
FROM
	userSiteSettings
WHERE
	userId = ?");
			$checkStmt->execute(array($_SESSION['userId']));
			$row = $checkStmt->fetch(PDO::FETCH_ASSOC);
			$selectedTimeZone = $row['timeZone'];
		}else{
			$selectedTimeZone = '';
		}
		$output .= '<label for="timeZoneSelect" class="select">Time Zone</label>
<select name="timeZoneSelect" id="timeZoneSelect" data-mini="true" data-inline="true">';
		foreach($potentialTimeZones as $timeZone){
			$output .= '<option value="' . $timeZone . '"';
			if($selectedTimeZone && $timeZone == $selectedTimeZone){
				$output .= ' selected="selected"';
			}elseif($timeZone == 'America/Los_Angeles'){
				$output .= ' selected="selected"';
			}
			$output .= '>' . Adrlist_Time::timeZoneDisplay($timeZone) . '</option>';
		}
		$output .= '</select>';
		$success = true;
		$returnThis['timeZones'] = $output;
	}catch(Adrlist_CustomException $e){}
	if(MODE == 'determineTimeZone'){
		returnData();
	}else{
		return $output;
	}
}