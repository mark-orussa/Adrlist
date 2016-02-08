<?php /* This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
Functions only here. They are listed in alphabetical order.
*/
$fileInfo = array('fileName' => 'includes/functions.php');
$debug->newFile($fileInfo['fileName']);
if(MODE == 'buildFaqPopupContent'){
	buildFaqPopupContent();
}elseif(MODE == 'buildTimeZones'){
	Adrlist_Time::buildTimeZones();
}

function br2nl($string){//Convert <br> tags to \n.
	if(is_array($string)){
		foreach($string as $key => $value){
			$value = str_replace('<br>', "", $value);
			$value = str_replace('<br ' . '/>', "", $value);//The concatenation is to prevent being replaced from a replace-all action.
		}
	}else{
		$string = str_replace('<br>', "", $string);
		$string = str_replace('<br ' . '/>', "", $string);//The concatenation is to prevent being replaced from a replace-all action.
	}
	return $string;
}

function breakEmail($email, $length = 20){//This will break $email at the @ symbol if it is over $length characters long.
	if(strlen($email) > $length){
		$parts = explode('@', $email);
		return $parts[0] . '@<br>
		' . $parts[1];
	}else{
		return $email;
	}
}

function buildFaqPopupContent(){// DEPRECATED. FAQ links now open in a new window/tab.
	//Build the content for the floating faq box.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['qId'])){
			throw new Adrlist_CustomException('','$_POST[\'qId\'] is not set.');
		}
		if(!is_numeric($_POST['qId'])){
			throw new Adrlist_CustomException('','$_POST[\'qId\'] is not numeric.');
		}
		$stmt = $Dbc->prepare("SELECT
	faqs.q AS 'question',
	faqs.a AS 'answer',
	faqs.faqId AS 'faqId'
FROM
	faqs
WHERE
	faqs.faqId = ?");
		$stmt->execute(array($_POST['qId']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(empty($row)){
			throw new Adrlist_CustomException('Couldn\'t find a FAQ associated with this question.','The statement returned zero rows.');
		}
		$output .= '<a href="#" data-rel="back" class="ui-btn ui-corner-all ui-shadow ui-btn-b ui-icon-delete ui-btn-icon-notext ui-btn-right">Close</a>
<div class="bold textLarge" style="margin:1em">' . $row['question'] . '</div>
	<div class="hr2"></div>
	<div class="textLeft" style="line-height:1.5em;margin:1em">
		' . nl2br($row['answer'], 1) . '
	</div>
	<div class="hr2"></div>
	<div class="textCenter">
		<a class="ui-btn ui-btn-inline ui-corner-all ui-mini" href="' . LINKFAQ . '" target="new">View all FAQs<i style="margin-left:.5em" class="fa fa-external-link"></i></a><a style="text-decoration:none" class="ui-btn ui-btn-inline ui-corner-all ui-shadow ui-btn-icon-right ui-icon-delete ui-btn-b ui-mini" data-rel="back">Close</a>
	</div>
</div>';
		$success = true;
		$returnThis['output'] = convertFaqLink($output);
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildFaqPopupContent'){
		returnData();
	}
}

function buildHeaderForEmail(){
	$output = '<a href="http://' . DOMAIN . '"><img alt="" border="0" src="http://' . DOMAIN . '/images/logo.png" height="68" width="245"></a>
<hr width="100%" noshade="noshade" size="3">
';
	return $output;
}

function questionOld($faqId = ''){// DEPRECATED. FAQ links now open in a new window/tab.
	/*
	This will build the question mark img tag for producing the faq popup.
	$faqId = (int) the id of the faq to produce.
	Returns the img tag, otherwise false.
	*/
	global $debug, $Dbc, $message, $success;
	$output = NULL;
	if(empty($faqId) || !is_int($faqId)){
		if(empty($faqId)){
			$debug->add('$faqId is empty on line ' . __LINE__ . '.');
		}elseif(!is_int($faqId)){
			$debug->add('$faqId is not an integer on line ' . __LINE__ . '.');
		}else{
			$debug->add('Something else is wrong.');
		}
		return false;
	}else{
		try{
			$verifyFaqStmt = $Dbc->prepare("SELECT
	q as 'q'
FROM
	faqs
WHERE
	faqId = ?");
			$verifyFaqParams = array($faqId);
			$verifyFaqStmt->execute($verifyFaqParams);
			$foundFaq = false;
			while($verifyFaqStmt->fetch(PDO::FETCH_ASSOC)){
				$foundFaq = true;
			}
			if($foundFaq){
				return '<img alt="" class="linkPadding middle question" qId="' .  $faqId. '" onClick="" src="' . LINKIMAGES . '/question.png" style="height:1em;width:1em">';
			}
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
}

function buildRoles($id = NULL, $selected = '0', $roles, $additionalAttributes = false){
	/*
	Returns a drop down list (select) with the corresponding role values.
	$id = (int) the id for the drop down list.
	$selected = (int) the value of the drop down list.
	$roles = (array) a numeric array of roles to be listed. Format is array(1,2,3). Options are as follows:
	0 = None
	1 = Member
	2 = Editor
	3 = Manager
	4 = Owner
	5 = Site Admin
	In general, folder array(0,1,2,3,4), list array(0,1,2,3,4), site array(0,1,5).
	$additionalAttributes = (associative array) additional attributes for use with jquery. Format: attributeName => attributeValue.
	*/
	global $debug, $message;
	$roleArray = array(0 => 'None', 1 => 'Member', 2 => 'Editor', 3 => 'Manager', 4 => 'Owner', 5 => 'SiteAdmin');
	$output = '<select';
	$output .= empty($id) ? '' : ' id="' . $id . '"';
	if(is_array($additionalAttributes) and !empty($additionalAttributes)){
		foreach($additionalAttributes as $attributeName => $attributeValue){
			$output .= ' ' . $attributeName . '="' . $attributeValue . '"';
		}
	}
	$output .= '>';
	if(is_array($roles) && !empty($roles)){
		foreach($roles as $value){
			$output .= '	<option value="' . $value . '"';
			if($selected == $value || $selected == $roleArray[$value]){
				$output .= ' selected="selected"';
			}
			$output .= '>' . $roleArray[$value] . '</option>
';
		}
	}else{
		error(__LINE__);
	}
	$output .= '</select>
';
	return $output;
}

function cancelButton(){
	return '<button class="generalCancel ui-btn ui-btn-b ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-delete">Cancel</button>';
}

function convertFaqLink($string){
	/*
	Convert the unique links used in the FAQ to real hyperlinks.
	$string = (string) a uniquely encoded string to be converted to a hyperlink.
	Returns a hyperlink with a valid input, otherwise false.
	*/
	global $debug;
	if(!empty($string)){
		if(preg_match_all('/%(\w*)%/m', $string, $matches)){//Replace the special links with real links.
			$constants = array();
			foreach($matches[1] as $key2 => $value2){
				if(defined($value2) && !empty($value2)){
					$constants[] = constant($value2);
				}else{
					$constants[] = '';
					$debug->add('$value2: ' . "$value2 is not defined.<br>");
				}
			}
			//$debug->printArray($constants, '$constants');
			$x = 0;
			foreach($matches[0] as $key3 => $value3){							
				$string = str_replace($value3, $constants[$x], $string);
				$x++;
			}
			//$debug->add('$value[\'q\']: <textarea cols="90>"' . $value['q'] . '</textarea><br>');
		}
		return $string;
	}else{
		return false;
	}
}

function charToHtml($input){
	/*
	Certain characters need to be converted to prevent breaking html code. " & ' < >
	
	This works on strings and arrays.
	*/
	global $debug;
	if(is_array($input)){
		foreach($input as $key => &$value){//Be sure to use the & character so the changes apply to the value and not a reference.
			if(is_array($value)){
				charToHtml($value);
			}else{
				$value = htmlspecialchars($value);
			}
		}
	}else{
		$input = htmlspecialchars($input);
	}
	return $input;
}

function createListRoles($requestingUserId,$listId,$folderId,$special = ''){
	/*
	This will create list roles for members of a folder for a specific list. This is used when adding a list to a folder either by creating a new list or by moving a list into a folder. By default:
	- folder members (1) will have no access (0) to the list.
	- folder editors (2) will have editor (2) roles.
	- folder managers (3) will have manager (3) roles.
	- folder owners (4) will have owner (4) roles.
	The reason that members will not have access to the list is to prevent directors, producers, actors, etc from gaining access to lists they are not involved in. Being members of a folder doesn't mean they should have access to every list added to the folder. Members must implicitly be given a role.
	$requestingUserId = (int) id of the user initiating the addition of the list to the folder.
	$listId = (int) id of the list.
	$folderId = (int) id of the folder.
	$special = (boolean) True here will use the user's folder role as the list role.
	Returns true upon successful application of list roles, false upon any errors. Use === to confirm.
	*/
	global $debug, $message, $Dbc, $transactionStarted;
	try{
		if(empty($requestingUserId)){
			throw new Adrlist_CustomException('','$requestingUserId is empty.');
		}elseif(empty($listId)){
			throw new Adrlist_CustomException('','$listId is empty.');
		}elseif(empty($folderId)){
			throw new Adrlist_CustomException('','$folderId is empty.');
		}
		$requestingUserId = intThis($requestingUserId);
		$listId = intThis($listId);
		$folderId = intThis($folderId);
		$debug->add('$requestingUserId: ' . "$requestingUserId");
		$debug->add('$listId: ' . "$listId");
		$debug->add('$folderId: ' . "$folderId");
		if(!is_numeric($requestingUserId)){
			throw new Adrlist_CustomException('','$requestingUserId is not an integer. $requestingUserId: ' . "$requestingUserId");
		}elseif(!is_numeric($listId)){
			throw new Adrlist_CustomException('','$listId is not an integer. $listId: ' . "$listId");
		}elseif(!is_numeric($folderId)){
			throw new Adrlist_CustomException('','$folderId is not an integer. $folderId: ' . "$folderId");
		}elseif(!$transactionStarted){
			throw new Adrlist_CustomException('','From createListRoles(): A PDO transaction must be started by the parent function.');
		}
		//The requesting user must be an owner to perform this action.
		$folderInfo = getFolderInfo($requestingUserId,$folderId);
		$requestingUserFolderRoleId = $folderInfo['folderRoleId'];
		if(empty($requestingUserFolderRoleId) || $requestingUserFolderRoleId < 4){
			throw new Adrlist_CustomException("You don't have a sufficient folder role to perform this action.",'');
		}
		//Get the users of the folder and their default list settings. This excludes users that already have a list role.	
		$folderUsersStmt = $Dbc->prepare("SELECT
	userFolderSettings.userId as 'userId',
	userFolderSettings.folderRoleId as 'folderRoleId',
	userSiteSettings.defaultLimit as 'defaultLimit',
	userSiteSettings.defaultOrderBy as 'defaultOrderBy',
	userSiteSettings.defaultOrderDirection as 'defaultOrderDirection',
	userSiteSettings.defaultShowCharacterColors as 'defaultShowCharacterColors'
FROM
	userSiteSettings
JOIN
	userFolderSettings ON userFolderSettings.userId = userSiteSettings.userId AND
	userFolderSettings.folderId = ? AND
	userSiteSettings.userId NOT IN (SELECT userId FROM userListSettings WHERE listId = ?)
");
		$folderUsersStmt->execute(array($folderId,$listId));
		$folderUsers = array();
		//Prepare the statements once outside of the loop.
		$insertUserListSettingsStmt = $Dbc->prepare("INSERT INTO
	userListSettings
SET
	userId = ?,
	listId= ?,
	listRoleId = ?,
	dateAdded = ?,
	limitCount = ?,
	orderBy = ?,
	orderDirection = ?,
	viewCharacters = ?,
	showCharacterColors = ?");
		while($folderUsersRow = $folderUsersStmt->fetch(PDO::FETCH_ASSOC)){
			//Set the listRoleId depending on the folderRoleId. This is not a 1 to 1 relationship.
			if($special){
				$newListRoleId = $folderUsersRow['folderRoleId'];
			}else{
				if($folderUsersRow['folderRoleId'] == 1){
					$newListRoleId = 0;
				}elseif($folderUsersRow['folderRoleId'] == 2){
					$newListRoleId = 2;
				}elseif($folderUsersRow['folderRoleId'] == 3){
					$newListRoleId = 3;
				}elseif($folderUsersRow['folderRoleId'] == 4){
					$newListRoleId = 4;
				}else{
					$newListRoleId = 0;
				}
			}
			$insertUserListSettingsParams = array(
				$folderUsersRow['userId'],
				$listId,
				$newListRoleId,
				DATETIME,
				$folderUsersRow['defaultLimit'],
				$folderUsersRow['defaultOrderBy'],
				$folderUsersRow['defaultOrderDirection'],
				'viewAll',
				$folderUsersRow['defaultShowCharacterColors']
			);
			$insertUserListSettingsStmt->execute($insertUserListSettingsParams);
		}
		$debug->add('created list roles for folder users.');
		return true;
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		return false;
	}
}

function destroySession(){
	global $debug, $message;
	$_SESSION = array();
	@session_destroy();
	@session_unset();
	setcookie(session_name(), '', time()-42000, '/');
	$debug->add('The session has been destroyed.');
	$_SESSION['auth'] = false;
}

function distributeRoles($requestingUserId,$userId,$sharedFoldersArray,$startedTransaction = false){
	/*
	This function will create roles or apply new roles for a user to a folder's lists. It will not create a folder role. It performs checks to verify the requesting user has sufficient roles for the folders. It is not used for lists outside of folders as there is no way of knowing which users to add. That's the reason why folders exist.
	$requestingUserId = (int) the user responsible for the request to change roles.
	$userId = (int) the user whose roles will be affected.
	$sharedFoldersArray = (array) in the format of array(folderId => folderRoleId, folderId => folderRoleId,...).
	Returns (boolean) true on success, false on failure. Use === true or === false to verify success or failure.
	*/
	global $debug, $message, $Dbc;
	$requestingUserId = intval($requestingUserId);
	$userId = intval($userId);
	if(!empty($sharedFoldersArray) && !is_array($sharedFoldersArray)){
		$debug->add('The $sharedFoldersArray parameter is empty or not an array:');
		$debug->printArray($sharedFoldersArray,'$sharedFoldersArray');
		$message .= error(__LINE__,false,'From distributeRoles() on line ' . __LINE__ . ' in functions.php.<br>');
		return false;
	}else{
		try{
			if(!$startedTransaction){
				$Dbc->beginTransaction();
			}
			//Prepare the folder role check query.
			$folderRoleCheck = $Dbc->prepare("SELECT
	folderRoleId AS 'folderRoleId'
FROM
	userFolderSettings
WHERE
	userId = ? AND
	folderId = ?");
			//Prepare the update folder role query.
			$updateFolderRole = $Dbc->prepare("UPDATE
	userFolderSettings
SET
	folderRoleId = ?
WHERE
	userId = ? AND
	folderId = ?
LIMIT 1");
			//Prepare the get folder lists query.
			$getListsQuery = $Dbc->prepare("SELECT
	lists.listId AS 'listId'
FROM
	lists
WHERE
	folderId = ?");
			$sharedLists = array();
			//Loop through the folders.
			foreach($sharedFoldersArray as $folderId => $folderRoleId){
				//Verify the requesting user has a sufficient folder role.
				$folderRoleCheck->execute(array($requestingUserId,$folderId));
				$requestingUserRole = $folderRoleCheck->fetch(PDO::FETCH_ASSOC);
				if($requestingUserRole['folderRoleId'] < 3){
					//The user does not have a sufficient folder role.
					//Get the name of the folder.
					$folderInfo = getFolderInfo($requestingUserId,$folderId);
					$folderName = $folderInfo['folderName'];
					$message .= $folderName ? 'Could not update the role for the folder "' . $folderName . '".<br>' : 'Could not update the role for a folder.<br>';
					$debug->add('Could not get the folderName for folderId: ' . $folderId . '. The requesting userId is: ' . $requestingUserId . '.');
					continue;
				}
				//Update the folder role.
				$params = array($folderRoleId,$userId,$folderId);
				$updateFolderRole->execute($params);
				$getListsQuery->execute(array($folderId));
				while($temp = $getListsQuery->fetch(PDO::FETCH_ASSOC)){
					//Fill the array with the format: listId => array('userListRoleId' => 3, 'requestingUserRoleId' => 4).
					$sharedLists[$temp['listId']] = array('userListRoleId' => $folderRoleId, 'requestingUserRoleId' => $requestingUserRole['folderRoleId']);
				}
			}
			$debug->printArray($sharedLists,'$sharedLists');
			//See if the user has an existing role for the lists.
			$existingListRoleQuery = $Dbc->prepare("SELECT
	listRoleId AS 'listRoleId'
FROM
	userListSettings
WHERE
	userId = ? AND
	listId = ?");
			foreach($sharedLists as $listId => $listInfo){
				$params = array($userId,$listId);
				$existingListRoleQuery->execute($params);
				$existingListRole = $existingListRoleQuery->fetch(PDO::FETCH_ASSOC);
				if($existingListRole['listRoleId'] === '' || $existingListRole['listRoleId'] === NULL){
					//The user does not have an existing role in the list. Insert one.
					$insertListRole = $Dbc->prepare("INSERT INTO
	userListSettings
SET
	userId = ?,
	listId = ?,
	listRoleId = ?,
	dateAdded = ?");
					$params = array($userId,$listId,$listInfo['userListRoleId'],DATETIME);
					$insertListRole->execute($params);
				}else{
					//The user has an existing list role. Update it.
					if($listInfo['requestingUserRoleId'] == 3 && $existingListRole >= 3){
						//Managers cannot change the role of fellow Managers or Owners.
						$message .= 'One or more list roles could not be updated. You cannot change the role of a Manager or Owner.<br>';
					}else{
						$updateListRole = $Dbc->prepare("UPDATE
	userListSettings
SET
	listRoleId = ?
WHERE
	userId = ? AND
	listId = ?");
						$params = array($listInfo['userListRoleId'],$userId,$listId);
						$updateListRole->execute($params);
					}
				}			
			}
			if(!$startedTransaction){
				$Dbc->commit();
			}
			return true;
		}catch(PDOException $e){
			$message .= error(__LINE__,'','<pre>' . $e . '</pre>');
			return false;
		}
	}
}

function email($fromAddress,$toAddress,$subject,$bodyHtml,$bodyText,$senderAddress = NULL,$returnAddress = NULL){
	/*
	Send an email using the Swift Mailer class library. Returns true if sent successfully, false otherwise.
	$fromAddress = (string, array, associative array) one or more senders' email addresses. The email will show as coming from this address. Array structure is array('here@there.com' => 'Joe Bob'). Strings will be converted to an array.
	$toAddress = (string, array, associative array) recipients' email addresses. Array structure is array('here@there.com' => 'Joe Bob'). Strings will be converted to an array.
	$subject = (string) the subject of the email.
	$bodyHtml = (string) the body or message of the email. May contain HTML.
	$bodyText = (string) the text version of the message. Should not contain HTML.
	$senderAddress = (string) optional single email address of the sender, not necessarily the creator of the message. This address is visible in the message headers, will be seen by the recipients, and will be used as the Return-Path: unless otherwise specified. Default is EMAILDONOTREPLY set in config.php.
	$returnAddress = (string) an optional single email address to handle bounced emails. This address specifies where bounce notifications should be sent and is set with the setReturnPath() method of the message. You can use only one email address and it must not include a personal name. Default is EMAILDONOTREPLY defined in config.php.
	*/
	require_once('Classes/Swift/swift_init.php');
	global $debug, $message;
	if((array) $fromAddress === $fromAddress){
		$thisCount = 0;
		$newFromAddress = array();
		foreach($fromAddress as $key){
			//Add valid email addresses to the new array.
			if(emailValidate($key) === true){
				$newFromAddress[] = $key;
			}elseif($thisCount == 0){
				error(__LINE__,'',"The to address '$fromAddress' is not valid.<br>");
				return false;
			}
			$thisCount++;
		}
		$fromAddress = $newFromAddress;
	}else{
		if(emailValidate($fromAddress) === true){
			$fromAddress = array($fromAddress);
		}else{
			error(__LINE__,'',"The to address '$fromAddress' is not valid.<br>");
			return false;
		}
	}
	if((array) $toAddress === $toAddress){
		$thisCount = 0;
		$newToAddress = array();
		foreach($toAddress as $key){
			//Add valid email addresses to the new array.
			if(emailValidate($key) === true){
				$newToAddress[] = $key;
			}elseif($thisCount == 0){
				error(__LINE__,'',"The to address '$toAddress' is not valid.<br>");
				return false;
			}
			$thisCount++;
		}
		$toAddress = $newToAddress;
	}else{
		if(emailValidate($toAddress) === true){
			$toAddress = array($toAddress);
		}else{
			error(__LINE__,'',"The to address '$toAddress' is not valid.<br>");
			return false;
		}
	}
	$debug->add('$senderAddress before validation: ' . "$senderAddress");
	$senderAddress = emailValidate($senderAddress) ? $senderAddress : EMAILDONOTREPLY;
	$returnAddress = emailValidate($returnAddress) ? $returnAddress : EMAILDONOTREPLY;
	$debug->add('$senderAddress after validation: ' . "$senderAddress");
	//Create the message
	$email = Swift_Message::newInstance()
	->setFrom($fromAddress)
	->setTo($toAddress)
	->setSubject($subject)
	->addPart('<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body bgcolor="#FFFFFF" marginheight="0" marginwidth="0" text="#000000" topmargin="0">
<table width="800" cellpadding="10" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="left">' . buildHeaderForEmail() . '</td>
	</tr>
	<tr>
		<td align="left"><font face="' . FONT . '" size="3">' . $bodyHtml . '
			<br>
			<br>
			Sincerly,<br>
			<br>
			' . THENAMEOFTHESITE . '
			<br>
			<br></font>
		</td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE1 . '">This is an automated message. Please do not reply.</font><br><br>
<a href="' . LINKSUPPORT . '">Click here to contact support.</a></td>
	</tr>
</table>		
</body>
</html>', 'text/html')
	->setBody($bodyText . '
Sincerly,

' . THENAMEOFTHESITE . ' Support


This is an automated message. Please do not reply.')
	->setSender($senderAddress)
	->setReturnPath($returnAddress)
	
	// Optionally add any attachments
	//->attach(Swift_Attachment::fromPath('my-document.pdf'))
	;
	if(LOCAL){
		//$transport = Swift_SmtpTransport::newInstance('127.0.0.0', 25);//Doesn't work on local machine.
		$transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');//This uses the local machine's MTA, not a remote service.
		//$transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')->setUsername('donotreply@adrlist.com')->setPassword('');//This uses a remote service like gmail for secure mail transactions.
	}else{
		$transport = Swift_SendmailTransport::newInstance('/usr/sbin/exim -bs');//This works better with ServInt.
	}
	$mailer = Swift_Mailer::newInstance($transport);
	//To use the ArrayLogger.
	$logger = new Swift_Plugins_Loggers_ArrayLogger();
	$mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
	if($mailer->send($email, $failures)){
		return true;
	}else{
		$debug->printArray($failures,'email address undeliverable');
		return false;
	}
	//Dump the error log.
	$debug->add($logger->dump());
}

function emailValidate($email){
	/*
	$email = (string) an email address.
	Returns true for a valid email address, false otherwise. Use === for validation.
	*/
	$email = trim($email);
	$expression = '/^[a-zA-Z\d._-]+@[a-zA-Z\d._-]+(\.(\w)+)+$/';
	if(preg_match($expression, $email, $matches)){
		return true;
	}else{
		return false;
	}
}

function error($line = false, $altMessage = false, $debugMessage = false){
	/* Produces a publicly visible error message with a line number at the end.
	$line = __LINE__.
	$altMessage = (string) a custom message.
	*/
	global $debug, $message;
	$message .= empty($altMessage) ? 'We\'ve encountered a technical problem that is preventing infomation from being shown. Please try again in a few moments.<br>
<br>
If the problem persists please <a href="' . LINKSUPPORT . '">contact support</a>.' : $altMessage;
	if(!empty($debugMessage)){
		$debug->add($debugMessage);
	}
}

function faqLink($faqId = '',$extra = false){
	/*
	This will build the question mark img tag for linking to the faq.
	$faqId = (int) the id of the faq to produce.
	Returns a link.
	*/
	return '<a data-ajax="false" href="' . LINKFAQ . '/#faq' .  $faqId. '" target="new" rel="external"><i class="fa fa-question-circle fa-lg blue" style="margin:0 .5em"></i>' . $extra . '</a>';
}

function getActiveLists($userId){
	/*
	Returns an array of active list information.
	$userId = (integer) the id of the user.
	*/
	global $debug, $Dbc, $message;
	$activeLists = array();
	try{
		//Get the lists of which the user is an owner.
		$activeListsStmt = $Dbc->prepare("SELECT
	lists.listName AS 'listName' ,
	lists.locked AS 'locked',
	lists.modified AS 'modified',
	unix_timestamp(lists.modified) AS 'timestamp',
	folders.folderName AS 'folderName'
FROM
	lists
JOIN
	userListSettings ON userListSettings.listId = lists.listId AND
	userListSettings.userId = ? AND
	userListSettings.listRoleId = 4
LEFT JOIN
	folders ON folders.folderId = lists.folderId
WHERE
	(lists.locked = ? || lists.locked IS NULL)");
		$activeListsParams = array($_SESSION['userId'],0);
		$activeListsStmt->execute($activeListsParams);
		while($row = $activeListsStmt->fetch(PDO::FETCH_ASSOC)){
			$activeLists[] = $row;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	return $activeLists;
}

function getDefaultListSettings($userId = 0){
	/*
	Returns an associative array containing the user's default list settings.
	$userId = (string) Default is $_SESSION['userId'].
	Return array('defaultLimit','defaultOrderBy','defaultOrderDirection','defaultShowCharacterColors'), otherwise (boolean) false.
	Use === false to verify.
	*/
	global $debug, $message, $success, $Dbc;
	$userId = empty($userId) ? $_SESSION['userId'] : $userId;
	try{
		$listSettingsStmt = $Dbc->prepare("SELECT
	defaultLimit AS 'defaultLimit',
	defaultOrderBy AS 'defaultOrderBy',
	defaultOrderDirection AS 'defaultOrderDirection',
	defaultShowCharacterColors AS 'defaultShowCharacterColors',
	defaultShowRecordedLines AS 'defaultShowRecordedLines',
	defaultShowDeletedLines AS 'defaultShowDeletedLines'
FROM
	userSiteSettings
WHERE
	userId = ?");
		$listSettingsStmt->execute(array($userId));
		$userDefaultListSettings = $listSettingsStmt->fetch(PDO::FETCH_ASSOC);
		return $userDefaultListSettings;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		return false;
	}
}

function getFolderInfo($requestingUserId,$folderId){
	/*
	Get a folder's information as it relates to a user. The name, created date, modified date, creator, modifier, folderRoleId, and it's lists in an array(listId=>listname).
	$userId = (int) the id of the requesting user. This is to verify the user has role of Member (1) or greater.
	$folderId = (int) the id of the folder.
	Returns (array) of the lists in the folder and the user's role, otherwise (boolean) false. Use === false to check for failure as it's possible a list could be named "0".
	*/
	global $debug, $message, $success, $Dbc;
	$output = '';
	try{
		if(empty($requestingUserId)){
			throw new Adrlist_CustomException('','$requestingUserId is empty.');
		}elseif(empty($folderId)){
			throw new Adrlist_CustomException('','$folderId is empty.');
		}
		$requestingUserId = intThis($requestingUserId);
		$folderId = intThis($folderId);
		//Get the folder's name.
		$stmt = $Dbc->prepare("SELECT
	folders.folderName AS 'folderName',
	folders.cId AS 'cId',
	folders.created AS 'created',
	folders.mId AS 'mId',
	folders.modified AS 'modified',
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	userFolderSettings.folderRoleId AS 'folderRoleId'
FROM
	userFolderSettings
JOIN
	folders ON userFolderSettings.folderId = folders.folderId
LEFT JOIN
	lists ON lists.folderId = userFolderSettings.folderId
WHERE
	userFolderSettings.userId = ? AND
	userFolderSettings.folderId = ?");
		$params = array($requestingUserId,$folderId);
		$stmt->execute($params);
		$folderArray = array();
		$listArray = array();
		$foundRecords = false;
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			if($row['folderName'] === '' || $row['folderName'] === NULL){
				pdoError(__LINE__,$stmt,$params,true);
				return false;
			}
			$folderArray['folderName'] = $row['folderName'];
			$folderArray['cId'] = $row['cId'];
			$folderArray['created'] = $row['created'];
			$folderArray['mId'] = $row['mId'];
			$folderArray['modified'] = $row['modified'];
			$folderArray['folderRoleId'] = empty($row['folderRoleId']) ? 0 : $row['folderRoleId'];
			$listArray[] = array($row['listId']=>$row['listName']);
			$foundRecords = true;
		}
		if(!$foundRecords){
			return false;
		}else {
			$folderArray['listArray'] = $listArray;
			return $folderArray;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE !== ''){
			returnData();
		}
	}
	return false;
}

function getFramerates(){
	//Returns framerates as an array(frId=>framerate).
	global $debug, $Dbc;
	$framerateQuery = $Dbc->prepare("SELECT
	frId AS 'frId',
	framerate AS 'framerate'
FROM
	framerates");
	$framerateQuery->execute();
	$framerates = array();
	while($row = $framerateQuery->fetch(PDO::FETCH_ASSOC)){
		$framerates[$row['frId']] =  $row['framerate'];
	}
	return $framerates;
}

function getListInfo($userId = 0,$listId = 0){
	/*
	Get a list's information according to the current user's view options. If there is a containing folder it gets the folder info too.
	$userId = (int) the user Id. This is to verify the user has a role of Member (1) or higher.
	$listId = (int) the list Id.
	Returns an associative array(
	'listName'				=> listName (string),
	'folderId'				=> folderId (int),
	'frId'					=> frId (int),
	'locked'				=> locked (boolean),
	'cId'					=> cId (int),
	'created'				=> created (DATETIME),
	'mId'					=> mId (int),
	'modified'				=> modified (DATETIME),
	'dId'					=> dId (int),
	'deleted'				=> deleted (DATETIME),
	'framerate'				=> framerate (float),
	'fps'					=> fps (int),
	'listRoleId'			=> listRoldId (int),
	'offset'				=> offset (int),
	'limit'					=> limit (int),
	'orderBy'				=> orderBy (string, 86 characters in length),
	'orderDirection'		=> orderDirection (string, ASC or DESC),
	'viewReels'				=> viewReels (string),
	'viewCharacters'		=> viewCharacters (string),
	'showCharacterColors'	=> showCharacterColors (boolean),
	'showRecordedLines'		=> showRecordedLines (boolean),
	'showDeletedLines'		=> showDeletedLines (boolean),
	'folderName'			=> folderName (string),
	'folderRoleId'			=> folderRoleId (int)
	), otherwise (boolean) false. Use === false to check for failure.
	*/
	global $debug, $message, $success, $Dbc;
	$output = '';
	try{
		if(empty($userId) && empty($_SESSION['userId'])){
			throw new Adrlist_CustomException('','There is no userId.');
		}
		$userId = empty($userId) ? $_SESSION['userId'] : $userId;
		if(empty($listId)){
			$getListIdStmt = $Dbc->prepare("SELECT userSiteSettings.listId AS 'listId' FROM userSiteSettings WHERE userSiteSettings.userId = ?");
			$getListIdStmt->execute(array($_SESSION['userId']));
			$row = $getListIdStmt->fetch(PDO::FETCH_ASSOC);
			$listId = $row['listId'];
			if(empty($listId)){
				throw new Adrlist_CustomException('','$listId is empty.');	
			}
		}
		/*SELECT
	lists.listId AS listId,
	lists.listName AS 'listName',
	lists.folderId AS 'folderId',
	lists.frId AS 'frId',
	lists.locked AS 'locked',
	lists.cId AS 'cId',
	lists.created AS 'created',
	lists.mId AS 'mId',
	lists.modified AS 'modified',
	lists.dId AS 'dId',
	lists.deleted AS 'deleted',
	framerates.framerate AS 'framerate',
	framerates.fps AS 'fps',
	userListSettings.userId AS 'userId',
	userListSettings.listRoleId AS 'listRoleId',
	userListSettings.listOffset AS 'offset',
	userListSettings.limitCount AS 'limitCount',
	userListSettings.orderBy AS 'orderBy',
	userListSettings.orderDirection AS 'orderDirection',
	userListSettings.viewReels AS 'viewReels',
	userListSettings.viewCharacters AS 'viewCharacters',
	userListSettings.showCharacterColors AS 'showCharacterColors',
	userListSettings.showRecordedLines AS 'showRecordedLines',
	userListSettings.showDeletedLines AS 'showDeletedLines',
	folders.folderName AS 'folderName',
	userFolderSettings.folderRoleId AS 'folderRoleId'
FROM
	lists
LEFT JOIN
	folders ON folders.folderId = lists.folderId
LEFT JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
		userFolderSettings.userId = ?
JOIN
	userListSettings ON userListSettings.listId = lists.listId AND
		userListSettings.userId = ? AND
	lists.listId = ?
JOIN
	framerates on framerates.frId = lists.frId
GROUP BY
	folders.folderId*/
		$stmt = $Dbc->prepare("SELECT
	lists.listId AS listId,
	lists.listName AS 'listName',
	lists.folderId AS 'folderId',
	lists.frId AS 'frId',
	lists.locked AS 'locked',
	lists.cId AS 'cId',
	lists.created AS 'created',
	lists.mId AS 'mId',
	lists.modified AS 'modified',
	lists.dId AS 'dId',
	lists.deleted AS 'deleted',
	framerates.framerate AS 'framerate',
	framerates.fps AS 'fps',
	userListSettings.userId AS 'userId',
	userListSettings.listRoleId AS 'listRoleId',
	userListSettings.listOffset AS offset,
	userListSettings.limitCount AS 'limitCount',
	userListSettings.orderBy AS 'orderBy',
	userListSettings.orderDirection AS 'orderDirection',
	userListSettings.viewReels AS 'viewReels',
	userListSettings.viewCharacters AS 'viewCharacters',
	userListSettings.showCharacterColors AS 'showCharacterColors',
	userListSettings.showRecordedLines AS 'showRecordedLines',
	userListSettings.showDeletedLines AS 'showDeletedLines',
	folders.folderName AS 'folderName',
	userFolderSettings.folderRoleId AS 'folderRoleId'
FROM
	lists
LEFT JOIN
	folders ON folders.folderId = lists.folderId
LEFT JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
		userFolderSettings.userId = ?
JOIN
	userListSettings ON userListSettings.listId = lists.listId AND
		userListSettings.userId = ? AND
	lists.listId = ?
JOIN
	framerates on framerates.frId = lists.frId");
		$params = array($userId,$userId,$listId);
		$stmt->execute($params);
		$listInfo = $stmt->fetch(PDO::FETCH_ASSOC);
		if($listInfo['listName'] === '' || $listInfo['listName'] === NULL){
			pdoError(__LINE__,$stmt,$params,true);
			return false;
		}else{
			//$debug->printArray($listInfo,'$listInfo');
			return $listInfo;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		return false;
	}
}

function getMaintMode(){
	//See if maintenance mode is set. Unless the user is Admin the session will be destroyed to prevent login.
	global $debug, $message, $success, $Dbc;
	try{
		$stmt = $Dbc->query("SELECT
	maintModeStartTime AS 'maintModeStartTime',
	maintModeEndTime AS 'maintModeEndTime'
FROM
	adminControl");
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row['maintModeStartTime']){
			if(isset($_SESSION['siteRoleId']) && $_SESSION['siteRoleId'] == 5){
				$_SESSION['maintMode'] = false;
			}else{
				//Don't activate maintMode if either start or end time is null.
				if(empty($row['maintModeStartTime']) || empty($row['maintModeEndTime'])){
					$_SESSION['maintMode'] = false;
				}else{
					if(strtotime($row['maintModeStartTime']) < TIMESTAMP && strtotime($row['maintModeEndTime']) > TIMESTAMP){
						$message = 'We are currenlty performing maintenance on the site. Access will be restored on ' . $row['maintModeEndTime']. ' UTC. ';
						$_SESSION['maintMode'] = true;
						destroySession();
					}else{
						$_SESSION['maintMode'] = false;
					}
				}
			}
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}	
}

function googleCode(){
	$output = '';
	if(!LOCAL && !isset($_COOKIE['noGoogleAnalytics'])){
		$output .= '<script type="text/javascript">' . "
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '" . GOOGLEANALYTICS . "']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>";
	}
	return $output;
}

function intThis($value){
	//Attempts to return an integer. The coersion method used here is faster than (int) or intval(), and produces more desireable outcomes when given non-numeric values.
	$temp = 0 + $value;
	$temp = (int) $temp;
	return $temp;
}

function passwordValidate($password){
	//Checks for 6-20 characters, allowing ! and @.
	if(preg_match("/^[a-zA-Z\d!@]{6,20}$/", $password)){
		return true;
	}else{
		return false;
	}
}

function pdoError($line = '', $statementObject, $statementParameters = false, $noRowsReturned = false){
	/* Returns database error information into the $debug variable. It does not display publicly.
	$line - (__LINE__) to return the script line number.
	$statementObject = (PDO object) the object containing the statement presented to the db handler.
	$statementParameters  = (array) the parameters given to the statement, if any.
	$zero = (boolean) to indicate there were no rows returned for the query.
	Example: pdoError(__LINE__, $testQuery, '$testQuery', 1);
	*/
	global $debug, $message, $Dbc;
	if(!empty($statementObject)){
		$error = $statementObject->errorInfo();
		$temp = '<div class="borderRed">';
		if(empty($noRowsReturned)){
			$temp .= '<span class="red">Mysql error: ' . $error[2] . $error[1] . ' (SQLSTATE error code ' . $error[0] . ')</span>';
		}else{
			$temp .= '<span class="red">Mysql returned zero rows</span>';
		}
		if(!empty($line)){
			$temp .= ' on line ' . $line . '. ';
		}
		if($statementParameters){
			$temp .= 'The query is:<br>' . $Dbc->interpolateQuery($statementObject->queryString, $statementParameters);
		}else{
			$temp .= 'The query is:<br>' . $statementObject->queryString;
		}
		$temp .= '</div>';
	}else{
		$temp .= 'The paramaterized statement is empty.';
	}
	$debug->add($temp);
}

function reconcileLists($userId){
	/*
	Automatically lock lists beyond the account credit balance, starting with the list with the oldest modified date.

	Returns true if lists were locked.
	*/
	global $debug, $message, $Dbc;
	try{
		$locked = false;
		$userBillingInfo = Adrlist_Billing::getUserPlan($_SESSION['userId']);
		$_SESSION['credits'] = $_SESSION['siteRoleId'] == 5 ? 9999 : $userBillingInfo['credits'];
		$activeLists = getActiveLists($_SESSION['userId']);
		$_SESSION['activeLists'] = count($activeLists);
		$creditBalance = $_SESSION['credits'] - $_SESSION['activeLists'];
		if($creditBalance < 0){
			$Dbc->beginTransaction();
			//Get a list of the user's currently unlocked lists.
			$unlockedListsStmt = $Dbc->prepare("SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	lists.modified AS 'modified'
FROM
	lists
JOIN
	userListSettings ON userListSettings.listId = lists.listId AND
	userListSettings.userId = ? AND
	userListSettings.listRoleId = 4
WHERE
	lists.locked = 0
ORDER BY
	modified ASC");
			$unlockedListsParams = array($_SESSION['userId']);
			$unlockedListsStmt->execute($unlockedListsParams);
			$preUnlockedLists = array();
			$listsToLock = abs($creditBalance);
			$lockListId = '';
			$x = 1;
			while($row = $unlockedListsStmt->fetch(PDO::FETCH_ASSOC)){
				$preUnlockedLists[] = $row;
				$lockListId .= empty($lockListId) ? $row['listId'] : ', ' . $row['listId'];
				if($x = $listsToLock){
					//Only lock as many lists as we need to.
					break;
				}
			}
			$debug->add('$listsToLock: ' . $listsToLock);
			$lockStmt = $Dbc->query("UPDATE
	lists
SET
	locked = 1
WHERE
	listId IN (" . $lockListId . ")");
			pdoError(__LINE__,$lockStmt,'');
			$lockStmt->execute();
			//Re-run the unlocked list query to check for differences.
			$unlockedListsStmt->execute($unlockedListsParams);
			$postUnlockedLists = array();
			while($row = $unlockedListsStmt->fetch(PDO::FETCH_ASSOC)){
				$postUnlockedLists[] = $row;
			}
			$debug->printArray($postUnlockedLists,'$postUnlockedLists');
			$difference = arrayRecursiveDiff($preUnlockedLists,$postUnlockedLists);
			$debug->printArray($difference,'$difference');
			$message .= 'As you have more active lists than credits, the following lists were locked ' . faqLink(29) . ':<br>';
			foreach($difference as $key => $value){
				$message .= $value['listName'] . '<br>';
			}
			$Dbc->commit();
			$locked = true;
			//$Dbc->rollback();
		}

		
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	return $locked;
}

function recur_array($startingValue){
	global $debug, $tempStorage;
	if(!is_object($startingValue)){
		if(is_array($startingValue)){
			foreach($startingValue as $key => $value){
				if(is_array($key)){
					recur_array($key);
				}else{
					if(is_array($value)){
						recur_array($value);
					}elseif(is_object($value)){
						$tempStorage .= var_dump($value);
					}else{
						$tempStorage .= $value . '<br>';
					}
				}
				/*recur_array!is_object($value)){
					if(is_array($value)){
						recur_array($value);
					}else{
						$tempStorage .= "$key: $value";
					}
				}*/
			}
		}else{
			$tempStorage .= $startingValue;
		}
	}else{
		$tempStorage .= var_dump($startingValue);
	}
}

function returnData(){
	//Create JSON syntax information to send back to the browser.
	global $debug, $message, $success, $returnThis;
	$success = empty($success) ? false : $success;
	$message = empty($message) ? '' : $message;//<span style="display:none">No message.</span>
	if(is_array($returnThis)){
		$jsonArray = array('debug' => $debug->output(), 'message' => $message, 'success' => $success);
		foreach($returnThis as $key => $value){
			$jsonArray[$key] = $value; 
		}
	}else{
		$jsonArray = array('debug' => $debug->output(), 'message' => $message, 'success' => $success);
	}
	
	/*$output = "{debug:'" . charConvert($debug->output()) . "', message:'" . charConvert($message) . "', success: '" . $success . "'";
	if(!empty($returnThis)){
		foreach($returnThis as $key => $value){
 			$output .= "," . charConvert($key) . ":'" . charConvert($value) . "'";
			$debug->add('$key: ' . $key . ', $value: ' . $value);
		}
	}
	$output .= '}';
	//echo "{ returnThis:'hi' }";
	die($output);
	*/
	$test = json_encode($jsonArray,JSON_HEX_APOS | JSON_HEX_QUOT);
	//$debug->add(json_decode($test));
	die($test);
}

function role($roleId, $siteRole = false){
	/*
	Returns the roles for the given numeric value.
	$roleId = (int) the role id.
	$siteRole = (boolean) true returns site roles instead of regular roles.
	*/
	if($siteRole){
		if(empty($roleId)){
			return 'Blocked';
		}elseif($roleId == 1){
			return 'Allowed';
		}elseif($roleId == 5){
			return 'Site Admin';
		}
	}else{
		if(empty($roleId)){
			return 'None';
		}elseif($roleId == 1){
			return 'Member';
		}elseif($roleId == 2){
			return 'Editor';
		}elseif($roleId == 3){
			return 'Manager';
		}elseif($roleId == 4){
			return 'Owner';
		}
	}
}

function shortenText($string, $length = 20, $cutBeginning = false, $trailingDots = false,$convertFromEm = false){
	/*
	This function will return $string if $string length is less than $length.
	$text = (string) the string to be shortened.
	$length = (int) maximum length of the resulting string. Non-integer values will be converted to int.
	$cut = (boolean) remove text from the beginning of $text by $length.
	$trail = (boolean) add ... at the end. Does not apply if using $cut.
	$convertFromEm = (boolean) applies a conversion ratio to convert from em measurements. This increases $length.
	*/
	$length = is_int($length) ? $length : intval($length);
	$length = $convertFromEm ? round($length*1.8) : $length;

	if(strlen($string) > $length){
		if($cutBeginning){
			$string = substr($string,$length,abs($length));
			return "...$text";
		}else{
			$string = substr($string, 0, abs($length));
			if($trailingDots){
				return $string . '...';
			}else{
				return $string;
			}
		}
	}else{
		return $string;
	}
}

function updateFolderHist($folderId = false){
	global $debug, $message, $Dbc;
	$folderId = !empty($folderId) ? intval($folderId) : intval($_POST['folderId']);
	$stmt = $Dbc->prepare("UPDATE
	folders
SET
	mId = ?,
	modified = ?
WHERE
	folderId = ?");
	$stmt->execute(array($_SESSION['userId'],DATETIME,$folderId));
}

function updateListHist($listId){
	global $debug, $message, $Dbc;
	$stmt = $Dbc->prepare("UPDATE
	lists
SET
	mId = ?,
	modified = ?
WHERE
	listId = ?");
	$stmt->execute(array($_SESSION['userId'],DATETIME,$listId));
	$stmt = $Dbc->prepare("SELECT
	folderId
FROM
	lists
WHERE
	listId = ?");
	$stmt->execute(array($listId));
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if(!empty($row['folderId'])){
		updateFolderHist($row['folderId']);
	}
}

function variables(){
	global $debug, $tempStorage;
	$tempStorage = '';
	//return 'GLOBAL variables follow:<pre>' . var_dump($GLOBALS) . '</pre>';
	return 'GLOBALS:<br>' . recur_array($GLOBALS);
}

function arrayRecursiveDiff($aArray1, $aArray2) {
    $aReturn = array();
  
    foreach ($aArray1 as $mKey => $mValue) {
        if (array_key_exists($mKey, $aArray2)) {
            if (is_array($mValue)) {
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
            } else {
                if ($mValue != $aArray2[$mKey]) {
                    $aReturn[$mKey] = $mValue;
                }
            }
        } else {
            $aReturn[$mKey] = $mValue;
        }
    }
  
    return $aReturn;
} 