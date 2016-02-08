<?php //This document will automatically force SSL and prevent unauthorized access. When this document is included you do not need to include config.php, as it is included below.
define('FORCEHTTPS', true, 1);
require_once('config.php');
$fileInfo = array('fileName' => 'includes/auth.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
$timeout = 20;//minutes before session logout
$expireTime = $timeout * 60;//Timeout in seconds.
$_SESSION['auth'] = false;
try{
	if(isset($_REQUEST['logout'])){
		//Log out.
		$debug->add('1');
		destroySession();
		//Attempt to delete an approved device.
		if(!empty($_COOKIE[UNIQUECOOKIE])){
			$removeDeviceStmt = $Dbc->prepare("DELETE FROM approvedDevices WHERE uniqueId = ?");
			$removeDeviceParams = array($_COOKIE[UNIQUECOOKIE]);
			$removeDeviceStmt->execute($removeDeviceParams);
			$_COOKIE[UNIQUECOOKIE] = false;
		}
		setcookie(UNIQUECOOKIE, NULL, 0, COOKIEPATH, COOKIEDOMAIN, false);//Delete cookie.
		header('Location: ' . AUTOLINK);
	}elseif(isset($_POST['mode']) && $_POST['mode'] == 'login'){
		//The cookie is empty. See if the user is attempting to login and check the email and password against the database.
		$debug->add('4');
		if(!isset($_POST['email'])){
			throw new Adrlist_CustomException('','$_POST[\'email\'] is not set.');
		}
		if(!isset($_POST['password'])){
			throw new Adrlist_CustomException('','$_POST[\'password\'] is not set.');
		}
		$loggedEmail = trim($_POST['email']);//use trim to clear any white space from the beginning and end
		$loggedPassword = trim($_POST['password']);
		$sha1loggedPassword = sha1($loggedPassword);
		$emailCheck = emailValidate($_POST['email']);
		if(!$emailCheck){
			throw new Adrlist_CustomException('','Please enter a valid email address.');
		}
		$loginStmt = $Dbc->prepare("SELECT
	users.userId AS 'userId',
	users.primaryEmail AS 'primaryEmail',
	users.secondaryEmail AS 'secondaryEmail',
	users.firstName AS 'firstName',
	users.lastName AS 'lastName',
	userSiteSettings.timeZone AS 'timeZone',
	userSiteSettings.siteRoleId AS 'siteRoleId',
	dateFormat.dateFormat AS 'dateFormat'
FROM
	users
JOIN
	userSiteSettings ON userSiteSettings.userId = users.userId AND
	users.primaryEmail = ? AND
	users.userPassword = ?
JOIN
	dateFormat ON dateFormat.dateFormatId = userSiteSettings.dateFormatId");
		$loginParams = array($loggedEmail,$sha1loggedPassword);
		$loginStmt->execute($loginParams);
		$row = $loginStmt->fetch(PDO::FETCH_ASSOC);
		if(empty($row)){
			pdoError(__LINE__, $loginStmt, $loginParams,1);
			throw new Adrlist_CustomException('Your email/password was not found. Please try again.','');
		}
		if(empty($row['siteRoleId'])){
			$message .= 'An administrative action is preventing you from logging in. Please <a href="' . LINKSUPPORT . '">contact support</a> for help.';
			returnData();	
		}
		$row['uniqueId'] = md5(DATETIME . $row['userId']);
		if(isset($_POST['rememberMe'])){
			$rememberMe = $_POST['rememberMe'] === 'true' ? 1 : 0;
			$rememberMeCode = sha1($row['primaryEmail']);
			if($rememberMe){
				setcookie(REMEMBERME, $rememberMeCode, time()+60*60*24*365, COOKIEPATH, COOKIEDOMAIN, false);
				$setRememberMeCodeQuery = $Dbc->prepare("UPDATE
	userSiteSettings
SET
	rememberMeCode = ?
WHERE
	userId = ?");
				$setRememberMeCodeParams = array($rememberMeCode,$row['userId']);
			}else{
				$setRememberMeCodeQuery = $Dbc->prepare("UPDATE
	userSiteSettings
SET
	rememberMeCode = NULL
WHERE
	userId = ?");
				$setRememberMeCodeParams = array($row['userId']);
				setcookie(REMEMBERME, '', -1 , COOKIEPATH, COOKIEDOMAIN, false);
			}
			$setRememberMeCodeQuery->execute($setRememberMeCodeParams);
		}
		
		//Log the user's login.
		$approvedStmt = $Dbc->prepare("INSERT INTO approvedDevices
SET
	userId = ?,
	uniqueId = ?,
	remoteAddress = ?,
	approvedDevicesDatetime = ?");
		$approvedParams = array($row['userId'],$row['uniqueId'],$_SERVER['REMOTE_ADDR'],DATETIME);
		$approvedStmt->execute($approvedParams);
		setcookie(UNIQUECOOKIE,$row['uniqueId'],time()+60*60*24*365,COOKIEPATH,COOKIEDOMAIN,false);
		if(empty($row['viewListOnLogin'])){
			$returnUrl = empty($message) ? LINKADRLISTS : LINKADRLISTS . '?message=' . $message;
		}else{
			$returnUrl = empty($message) ? LINKEDITLIST : LINKEDITLIST . '?message=' . $message;
		}
		$_SESSION['auth'] = true;
		$success = true;
		setSessionVariables($row);
		$returnThis['returnUrl'] = $returnUrl;
		returnData();	
	}elseif(!empty($_SESSION[UNIQUECOOKIE]) && !empty($_COOKIE[UNIQUECOOKIE]) && $_SESSION[UNIQUECOOKIE] == $_COOKIE[UNIQUECOOKIE]){
		//The session uniqueId matches the cookie uniqueId.
		$debug->add('2');
		$_SESSION['auth'] = true;
		if(stripos($_SERVER['PHP_SELF'],'/login/') !== false){
			//We are on the login page. Redirect according to the user's site settings.
			if(empty($_SESSION['viewListOnLogin'])){
				$location = empty($message) ? LINKADRLISTS : LINKADRLISTS . '?message=' . $message;
			}else{
				$location = empty($message) ? LINKEDITLIST : LINKEDITLIST . '?message=' . $message;
			}
			header('Location:' . $location);
		}
	}elseif(!empty($_COOKIE[UNIQUECOOKIE])){
		//Check the browser cookie against the database.
		$debug->add('3');
		$uniqueIdCheckStmt = $Dbc->prepare("SELECT
	approvedDevices.userId AS 'userId',
	users.userId AS 'userId',
	users.primaryEmail AS 'primaryEmail',
	users.secondaryEmail AS 'secondaryEmail',
	users.firstName AS 'firstName',
	users.lastName AS 'lastName',
	userSiteSettings.timeZone AS 'timeZone',
	userSiteSettings.listId AS 'listId',
	userSiteSettings.viewListOnLogin AS 'viewListOnLogin',
	userSiteSettings.siteRoleId AS 'siteRoleId',
	dateFormat.dateFormat AS 'dateFormat'
FROM
	users
JOIN
	userSiteSettings ON userSiteSettings.userId = users.userId
JOIN
	approvedDevices ON approvedDevices.userId = users.userId AND
	approvedDevices.uniqueId = ?
JOIN
	dateFormat ON dateFormat.dateFormatId = userSiteSettings.dateFormatId");
		$uniqueIdCheckParams = array($_COOKIE[UNIQUECOOKIE]);
		$uniqueIdCheckStmt->execute($uniqueIdCheckParams);
		$row = $uniqueIdCheckStmt->fetch(PDO::FETCH_ASSOC);
		if(!empty($row)){
			$row['uniqueId'] = $_COOKIE[UNIQUECOOKIE];
			$_SESSION['auth'] = true;
		}
		setSessionVariables($row);
	}
	
	if(isset($_SESSION['siteRoleId']) && empty($_SESSION['siteRoleId'])){
		//The user has been implicitley denied access to the site.
		destroySession();
		header('Location: ' . LINKLOGIN . '/?logout=1');
	}elseif($_SESSION['auth']){
		//The user is logged in.
		$debug->add('6');
		define('NAME', $_SESSION['firstName'] . ' ' . $_SESSION['lastName'], 1);
		//getMaintMode();
		if($_SESSION['siteRoleId'] == 5){
			setcookie('noGoogleAnalytics', 'donotcountme', time()+60*60*24*365, COOKIEPATH, COOKIEDOMAIN, false);//1 year
		}
		reconcileLists($_SESSION['userId']);//Reconcile lists credit balance.
		//Update the cookie
		if(empty($uniqueId)){
			setcookie(UNIQUECOOKIE, $_SESSION[UNIQUECOOKIE], time()+60*60*24*365, COOKIEPATH, COOKIEDOMAIN, false);//1 year
			$debug->add('Just set ' . UNIQUECOOKIE . ' cookie.');
		}else{
			setcookie(UNIQUECOOKIE, $uniqueId, time()+60*60*24*365, COOKIEPATH, COOKIEDOMAIN, false);//1 year
		}
		if(stripos($_SERVER['PHP_SELF'],'/login/') !== false){//If the user is on the login page, redirect to the admin section.
			$debug->add('9');
			if(empty($row['viewListOnLogin'])){
				$location = empty($message) ? LINKADRLISTS : LINKADRLISTS . '?message=' . $message;
			}else{
				$location = empty($message) ? LINKEDITLIST : LINKEDITLIST . '?message=' . $message;
			}
			header("Location: $location");
		}
	}else{
		//The users has no uniqueId cookie and is not attempting to login. Redirect the user to the login page.
		$debug->add('12');
		if(stripos($_SERVER['PHP_SELF'],'/login/') === false && stripos($_SERVER['PHP_SELF'],'/createAccount/') === false){
			$debug->add('13');
			$location = LINKLOGIN;
			header("Location: $location");
		}
	}
}catch(Adrlist_CustomException $e){
	if($_POST['mode'] == 'login'){
		returnData();
	}
}catch(PDOException $e){
	error(__LINE__,'','<pre>' . $e . '</pre>');
}

function setSessionVariables($row){
	$_SESSION['userId'] = intThis($row['userId']);
	$_SESSION['siteRoleId'] = intThis($row['siteRoleId']);
	$_SESSION['primaryEmail'] = $row['primaryEmail'];
	$_SESSION['secondaryEmail'] = $row['secondaryEmail'];
	$_SESSION['firstName'] = $row['firstName'];
	$_SESSION['lastName'] = $row['lastName'];
	$_SESSION['dateFormat'] = $row['dateFormat'];
	$_SESSION['timeZone'] = $row['timeZone'];
	$_SESSION[UNIQUECOOKIE] = $row['uniqueId'];
}