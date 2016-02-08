<?php
class Adrlist_Time{
	/**
	 * Time methods.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 *
	*/
	
	//Properties.
	
	public function __construct(){

	}

	/*
	 * This will add time to a date. If adding a month it will do so without rolling over to the next month in the event the new date doesn't exist. Example: going from March 31 to April 30.
	 *
	 * @param	object string	$date_str	A DateTime object or string. Strings will be converted to a DateTime object.
	 * @param	string	$period		A time period of 'year' or 'month'.
	 * @param	integer	$length		The number of years or months to add.
	 *
	 * @return	object	Returns a DateTime object.
	*/
	public static function addToDate($initDate, $period, $length){
		$dateTime = self::convertToDateTime($initDate);
		$start_day = $dateTime->format('j');//The day.
		if($period == 'hour'){
			$dateTime->modify("+{$length} hour");//Add year(s).
		}elseif($period == 'day'){
			$dateTime->modify("+{$length} day");//Add year(s).
		}elseif($period == 'month'){
			$dateTime->modify("+{$length} month");//Add month(s).
		}elseif($period == 'year'){
			$dateTime->modify("+{$length} year");//Add year(s).
		}
		$end_day = $dateTime->format('j');//The day of the new date.
		if($period = 'month' && $start_day != $end_day){//If the day is different, set it to the last day of the month.
			$dateTime->modify('last day of last month');
		}
		return $dateTime;
	}

	public static function buildTimeZones(){
		//Build a drop down list of times every 15 minutes. This function is dependent on date_default_timezone_set('UTC').
		global $debug, $message, $success, $Dbc, $returnThis;
		$output = '';
		try{
			if(empty($_POST['timestampMilliseconds'])){
				throw new Adrlist_CustomException('','$_POST[\'timestamp\'] is empty.');
			}elseif(empty($_POST['offsetMinutes'])){
				throw new Adrlist_CustomException('','$_POST[\'offsetMinutes\'] is empty.');
			}
			$label = empty($_POST['label']) ? 'Time Zone' : $_POST['label'];
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
			//$debug->printArray($allTimeZones,'$allTimeZones');
			//$debug->printArray($potentialTimeZones,'$potentialTimeZones');
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
			$output .= '<div class="ui-field-contain">';
			$output .= '<label for="timeZoneSelect" class="select">' . $label . '</label>
<select name="timeZoneSelect" id="timeZoneSelect" data-mini="false" data-inline="true">';
			foreach($potentialTimeZones as $timeZone){
				$output .= '<option value="' . $timeZone . '"';
				if($selectedTimeZone && $timeZone == $selectedTimeZone){
					$output .= ' selected="selected"';
				}elseif($timeZone == 'America/Los_Angeles'){
					$output .= ' selected="selected"';
				}
				$output .= '>' . Adrlist_Time::timeZoneDisplay($timeZone) . '</option>';
			}
			$output .= '</select>
</div>';
			$success = true;
			$returnThis['timeZones'] = $output;
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
		if(MODE == 'buildTimeZones'){
			returnData();
		}else{
			return $output;
		}
	}

	/*
	 * Convert anything to a DateTime object.
	 *
	 * @param	object string	$str	A date string, timestamp, or DateTime object.
	 *
	 * @return	object	A DateTime object.
	*/
	public static function convertToDateTime($str){
		global $debug;
		if($str instanceof DateTime){
			return $str;
		}else{
			$datetime = new DateTime();
			if(!(self::isValidTimeStamp($str))){
				$str = strtotime($str);
			}
			$datetime->setTimestamp($str);
		}
		return $datetime;
	}
	/*
	 * Calculate the difference in days between two dates.
	 *
	 * Parameters are automatically converted to DateTime objects.
	 *
	 * @param	string object	$startTime	Any English textual date or DateTime object.
	 * @param	string object	$endTime	Any English textual date or DateTime object.
	 *
	 * @return	int	The difference in days.
	*/
	public static function daysDifference($startTime,$endTime){
		$startTime = self::convertToDateTime($startTime);
		$endTime = self::convertToDateTime($endTime);
		$interval = $startTime->diff($endTime);
		return intThis($interval->format('%a'));
	}

	//Get the dateFormats. Returns an array.
	public static function getDateFormats(){
		global $debug, $Dbc;
		$dateFormatStmt = $Dbc->prepare("SELECT
	dateFormatId AS 'dateFormatId',
	dateFormat AS 'dateFormat',
	example AS 'example'
FROM
	dateFormat");
		$dateFormatStmt->execute();
		$dateFormats = array();
		while($row = $dateFormatStmt->fetch(PDO::FETCH_ASSOC)){
			$dateFormats[$row['dateFormatId']] = array($row['dateFormat'],$row['example']);
		}
		return $dateFormats;
	}
	
	public static function isValidTimeStamp($timestamp){
		return ((string) (int) $timestamp === $timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
	}
	
	/*
	 * Converting local datetime to UTC datetime.
	 *
	 * $time is converted to a DateTime object.
	 *
	 * @param	string			$format		Output a formatted date.
	 * @param	string			$timeZone	An optional php time zone. Defaults to 'America/Los_Angeles'.
	 *
	 * @return	object	A datetime object.
	*/
	public static function localToUtc($format = true,$timeZone = false){
		global $debug, $message;
		if(empty($timeZone)){
			if(empty($_SESSION['timeZone'])){
				$timeZone = 'America/Los_Angeles';
			}else{
				$timeZone = $_SESSION['timeZone'];
			}
		}
		//Calculate the local time. This takes daylight savings into account for the local time zone.
		$dateTimeUtc = new DateTime('@' . TIMESTAMP);
		$localTimeZone = new DateTimeZone($timeZone);
		$timeOffset = $localTimeZone->getOffset($dateTimeUtc);
		$datetime = new DateTime(TIMESTAMP + $timeOffset);
		if($format){
			return $datetime->format($_SESSION['dateFormat']);
		}else{
			return $datetime;
		}
	}
	
	/*
	 * Converts a UTC datetime object to a different time zone.
	 *
	 * This will produce a time in the user's local timezone. If $datetime is provided it will be relative
	 * to the provided time, otherwise the current UTC time will be used.
	 *
	 * !!!! Important!!!! You must store the output of this method in a variable to be able to call methods against it:
	 * $newTime = Markproaudio_Time::utcToLocal(false,false);
	 *
	 * @param	string object	$datetime	Any English textual date or DateTime object.
	 * @param	string			$useSessionFormat		Output a date in the format found in $_SESSION['dateFormat'].
	 * @param	string			$timeZone	The time zone to convert to. Defaults to 'America/Los_Angeles'.
	 *
	 * @return	object	A datetime object.
	*/
	public static function utcToLocal($datetime = false,$useSessionFormat = true,$timeZone = false){
		global $debug, $message;
		if(empty($timeZone)){
			if(empty($_SESSION['timeZone'])){
				$timeZone = new DateTimeZone('America/Los_Angeles');
			}else{
				$timeZone = new DateTimeZone($_SESSION['timeZone']);
			}
		}
		
		if(empty($datetime)){
			//Use the current UTC if $datetime is empty.
			$datetime = new DateTime();
		}else{
			//Use the provided time.
			$datetime = self::convertToDateTime($datetime);
		}		

		$datetime->setTimezone($timeZone);//This performs the time zone conversion.
		if($useSessionFormat){
			return $datetime->format($_SESSION['dateFormat']);
		}else{
			return $datetime;
		}
	}
	
	/*
	 * Displays only the city name from the time zone locations. For example, "America/Los_Angeles" becomes "Los Angeles".
	 *
	 * @param	string	$timeZone	The time zone to display.
	 *
	 * @return	string	The city, otherwise false.
	*/
	public static function timeZoneDisplay($timeZone){
		if(empty($timeZone)){
			return false;
		}else{
			$timeZone = preg_replace('/_/',' ',$timeZone);
			$timeZoneParts = explode('/',$timeZone);//An array of parts split by /.
			return array_pop($timeZoneParts);//The last value.
		}
	}
}
