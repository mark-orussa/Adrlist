<?php /*
This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
This file and it's functions are to be used solely by ../errors/ in conjunction with ../js/error.js.

All functions are listed in alphabetical order.
*/
$fileInfo = array('fileName' => 'includes/errorReportingMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
global $errorDbc;
$errorDbc = new Adrlist_Dbc(ERRORDBC);
if(MODE == 'buildDailyDigest'){
	buildDailyDigest();
}

function errorReporting($httpResponseStatusCode){
	global $debug, $message, $errorDbc;
	//die(var_dump($errorDbc));
	foreach($_SERVER as $key => $value){
		$_SERVER[$key] = empty($key) ? '' : $value;
	}
	$httpResponseStatusCode = empty($httpResponseStatusCode) ? '' : $httpResponseStatusCode;
	$_SERVER['HTTP_REFERER'] = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
	$errorDebug = 'Debug information follows:<br>
' . $debug->output() . "<br>";// . variables();
	try{
		$errorReportingStmt = $errorDbc->prepare("INSERT INTO
	errorReporting
SET
	errorDatetime = ?,
	serverName = ?,
	httpHost = ?,
	httpResponseStatusCode = ?,
	userAgent = ?,
	requestUri = ?,
	remoteAddress = ?,
	httpReferrer = ?,
	debug = ?");
		$errorReportingStmt->execute(array(DATETIME,$_SERVER['SERVER_NAME'],$_SERVER['HTTP_HOST'],$httpResponseStatusCode,$_SERVER['HTTP_USER_AGENT'],$_SERVER['REQUEST_URI'],$_SERVER['REMOTE_ADDR'],$_SERVER['HTTP_REFERER'],$errorDebug));
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
}

function buildDailyDigest(){
	/*
	Returns error logs for the day.
	All times are UTC.
	*/
	global $debug, $message, $success, $errorDbc, $returnThis;
	$output = '';
	try{
		$midnight = strtotime('-1 day', mktime(0,0,0));
		$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d H:i:s',$midnight);
		$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d H:i:s',$midnight + 86399);
		$params = array($startDate,$endDate);
		//$time = new Adrlist_MeasureTime('countStmt');
		$countStmt = $errorDbc->prepare("SELECT COUNT(errorId) AS 'count' FROM errorReporting WHERE errorDatetime BETWEEN ? AND ?");
		$countStmt->execute($params);
		$row = $countStmt->fetch(PDO::FETCH_ASSOC);
		//$debug->add($time->output());
		$itemCount = $row['count'];
		$pagination = new Adrlist_Pagination('buildDailyDigest','dailyDigest',$itemCount);
		$offsetLimit = $pagination->offsetLimit();
		list($offset,$limit) = $offsetLimit;
		$dailyDigestStmt = "SELECT
	errorId AS 'errorId',
	errorDatetime AS 'datetime',
	serverName AS 'serverName',
	httpHost AS 'httpHost',
	httpResponseStatusCode AS 'httpResponseStatusCode',
	userAgent AS 'userAgent',
	requestUri AS 'requestUri',
	remoteAddress AS 'remoteAddress',
	httpReferrer AS 'httpReferrer',
	debug AS 'debug'
FROM
	errorReporting
WHERE
	datetime BETWEEN ? AND ?
ORDER BY
	serverName,httpHost,errorDatetime
LIMIT " . $offset . ', ' . $limit;
		//Get all of the items for statistics.
		$debug->add($dailyDigestStmt);
		$debug->printArray($params,'$params');
		$dailyDigestStatsStmt = $errorDbc->prepare($dailyDigestStmt);
		//$time = new Adrlist_MeasureTime('dailyDigestStatsStmt');
		$dailyDigestStatsStmt->execute($params);
		//$debug->add($time->output());
		//General Statistics like how many errors per domain.
		$errors = array();
		$serverNameArray = array();
		$sucker = 0;
		/*
		 array(
	 *		0 => array(
	 *				Name,
	 *				Date,
	 *				etc
	 *			),
	 *		1 => array(
	 *				Name,
	 *				Date,
	 *				etc
	 *			)
	 *	)
	 */
		while($row = $dailyDigestStatsStmt->fetch(PDO::FETCH_ASSOC)){
		}
		//Get just the server names to count them.
		$serverNamesStmt = $errorDbc->prepare("SELECT
	serverName AS 'serverName'
FROM
	errorReporting
WHERE
	errorDatetime BETWEEN ? AND ?");
		$serverNamesStmt->execute($params);
		while($row = $serverNamesStmt->fetch(PDO::FETCH_ASSOC)){
			if(!array_key_exists($row['serverName'],$serverNameArray)){
				$serverNameArray[$row['serverName']] = 0;
			}
			$serverNameArray[$row['serverName']] = $serverNameArray[$row['serverName']] + 1;
		}
		$debug->printArray(array($serverNameArray),'$serverNameArray');
		$newServerNameArray = array();
		foreach($serverNameArray as $key => $value){
			$newServerNameArray[] = array($key,$value);
		}
		$generalStatsTitleRow = array('Server Name','# of Records');
		$generalStatsCssWidths = array(20,5);
		$generalStatsBuildRows = new Adrlist_BuildRows($generalStatsTitleRow,$newServerNameArray,$generalStatsCssWidths);
		$generalStats = '<div class="bold textCenter textLarge" style="margin-top:2em">General Statistics</div>
' . $generalStatsBuildRows->output();
		
		$dailyDigestStmt = $errorDbc->prepare($dailyDigestStmt);
		$dailyDigestStmt->execute(array($startDate,$endDate));
		//$pagination = new BuildPagination($itemCount,$offset,$limit,'dailyDigest');
		//$pagination->setPagesNumbersToDisplay(25);
		//$pagination = $pagination->output();
		//Column widths in em.
		$widthDatetime = 8;
		$widthServerName = '18';
		$widthHttpHost = 18;
		$widthHttpResponse = 8;
		$widthUserAgent = 15;
		$widthRequestUri = 18;
		$widthRemoteAddress = 8;
		$widthHttpReferrer = 20;
		$widthDebug = 8;
		$cssWidths = array(2,8,18,18,8,15,18,8,20,8);
		$titleRow = array('','Datetime','Server Name','Http Host','HTTP Response Status Code','User Agent','Request URI','Remote Address','Http Referrer');
		$pagination = $pagination->output();
		$buildRows = new Adrlist_BuildRows($titleRow,'',$cssWidths);
		$table = $buildRows->outputTitleRow();
		$class = 'rowAlt';
		$x = $offset + 1;
		while($row = $dailyDigestStmt->fetch(PDO::FETCH_ASSOC)){
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$table .= '<div class="hand ' . $class . ' clear relative" style="overflow:hidden" id="errorTrigger' . $row['errorId'] . '" errorId="' . $row['errorId'] . '">
	<div class="row">' . $x . '</div>
	<div class="row" style="width:' . $widthDatetime . 'em;">' . $row['datetime'] . '</div>
	<div class="row" style="width:' . $widthServerName . 'em;">' . $row['serverName'] . '</div>
	<div class="row" style="width:' . $widthHttpHost . 'em;">
		<span id="httpHostShortShowHide' . $row['errorId'] . '">' . shortenText($row['httpHost'], $widthHttpHost, false, true, true) . '</span>
		<span class="hide" id="HttpHostLongShowHide' . $row['errorId'] . '">' . $row['httpHost'] . '</span>
	</div>
	<div class="row" style="width:' . $widthHttpResponse . 'em;">' . $row['httpResponseStatusCode'] . '</div>
	<div class="row" style="width:' . $widthUserAgent . 'em;">
		<span id="userAgentShortShowHide' . $row['errorId'] . '">' . shortenText($row['userAgent'], $widthUserAgent, false, true, true) . '</span>
		<span class="hide" id="userAgentLongShowHide' . $row['errorId'] . '">' . $row['userAgent'] . '</span>
	</div>
	<div class="row" style="width:' . $widthRequestUri . 'em;">
		<span id="requestUriShortShowHide' . $row['errorId'] . '">' . shortenText($row['requestUri'], $widthRequestUri, false, true, true) . '</span>
		<span class="hide" id="requestUriLongShowHide' . $row['errorId'] . '">' . urldecode($row['requestUri']) . '</span>
	</div>
	<div class="row" style="width:' . $widthRemoteAddress . 'em;">' . $row['remoteAddress'] . '</div>
	<div class="row" style="width:' . $widthHttpReferrer . 'em;">
		<span id="httpReferrerShortShowHide' . $row['errorId'] . '">' . shortenText($row['httpReferrer'], $widthHttpReferrer, false, true, true) . '</span>
		<span class="hide" id="httpReferrerLongShowHide' . $row['errorId'] . '">' . $row['httpReferrer'] . '</span>
	</div>
	<div class="break hide textLeft" id="debugShowHide' . $row['errorId'] . '">' . $row['debug'] . '</div>
</div>
';
			$x++;
		}
		$output .= 'Showing <input type="text" id="startDate" value="' . $startDate . '"> to <input type="text"  id="endDate" value="' . $endDate . '"> <span class="buttonBlueThin" id="dateRangeGo">Go</span>' . $generalStats . '<div class="hr3"></div><div class="bold textCenter textLarge" style="margin-top:1em">Errors</div>' . $pagination . $table . $pagination;
		if(MODE == 'buildDailyDigest'){
			$success = true;
			$returnThis['output'] = $output;
			$returnThis['container'] = 'dailyDigestHolder';
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildDailyDigest'){
		returnData();
	}else{
		return $output;
	}
}