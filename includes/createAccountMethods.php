<?php
require_once('recaptchalib.php');
$fileInfo = array('fileName' => 'includes/createAccountMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'createNewUser'){
	createNewUser();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function buildCreateAccount(){
	/*
	This function builds "create new account" form for new users. If an invitation code is detected it will lock in the email address.
	*/
	global $debug, $message, $Dbc, $returnThis;
	$output = '';
	/*
	Build the create form. If an invitation code is present get the associated email from the record and lock in the email field so the user can't change it.
	*/
	try{
		if(isset($_REQUEST['invitationCode']) && strlen($_REQUEST['invitationCode']) == 40){
			$selectInviteQuery = $Dbc->prepare("SELECT
	email as 'email'
FROM
	invitations
WHERE
	invitationCode = ? AND
	respondDate IS NULL");
			$inviteParams = array($_REQUEST['invitationCode']);
			$selectInviteQuery->execute($inviteParams);
			$invited = $selectInviteQuery->fetch(PDO::FETCH_ASSOC);
			if($invited['email'] === '' || $invited['email'] === NULL){
				//The invitation code wasn't found.
				$invitedEmail = false;
				pdoError(__LINE__,$selectInviteQuery,$inviteParams,1);
				$output .= '<div class="red" style="padding:10px;">An invitation wasn\'t found. It may have been cancelled by the person who made the invitation. You can continue creating your free account any way.</div>';
			}else{
				$invitedEmail = $invited['email'];
			}
		}else{
			$invitedEmail = false;
		}
		$createForm = '<div class="textCenter center">
	<div class="ui-field-contain">
		<label for="createFirstName" unused="ui-hidden-accessible">First Name</label>
		<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="createFirstName" goswitch="createNewUser" name="createFirstName" placeholder="" type="text" value="">
	</div>
	<div class="ui-field-contain">
		<label for="createLastName" unused="ui-hidden-accessible">Last Name</label>
		<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="createLastName" goswitch="createNewUser" name="createLastName" placeholder="" type="text" value="">
	</div>
	<div class="ui-field-contain">
		<label for="createEmail" unused="ui-hidden-accessible">Email</label>
		<input autocapitalize="off" autocorrect="off" data-wrapper-class="true" id="createEmail" goswitch="createNewUser" name="createEmail" placeholder="" type="email" value="';
		$createForm .= $invitedEmail ? $invitedEmail . '" disabled="disabled">' : '">'; 
		$createForm .= '
	</div>
	<div class="ui-field-contain">
		<label for="loginPassword" unused="ui-hidden-accessible">Password</label>
		<input id="createPass1" goswitch="createNewUser" name="createPass1" placeholder="" value="" type="password">
	</div>
	<div class="center textCenter" id="timeZoneHolder" goswitch="createNewUser" label="What city best represents your time zone?"></div>
	<div class="ui-field-contain">
		<input name="termsConfirmation" id="termsConfirmation" goswitch="createNewUser" type="checkbox">
	    <label for="termsConfirmation">Click here to agree to the terms and conditions</label>
		<a href="' . LINKLEGAL . '" target="_new">terms and conditions</a> <img src="' . LINKIMAGES . '/newWindow.gif">
	</div>
	<input checked="checked" class="hide" id="rememberMe" name="rememberMe" type="checkbox">';
		$createForm .= isset($_REQUEST['invitationCode']) ? '	<div class="hide" id="invitationCode"> ' . $_REQUEST['invitationCode'] . '</div>' : '';
		$createForm .= '
	<div>
		<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-plus" id="createNewUser">Create My Account</button>
	</div>
</div>
';

		/*
		Build the output.
		*/
		$output .= '<div class="overflowauto relative" style="padding:5px 0;margin:10px 0">
	<div class="red textCenter" style="padding-bottom:5px">
		<noscript>(javascript required)</noscript>
	</div>
' . $createForm . '
</div>
';
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	return $output;
}

function createNewUser(){
	/*
	A new user has entered their information. We will create their account.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['firstName'])){
			throw new Adrlist_CustomException('','$_POST[\'lastName\'] is empty.');
		}elseif(empty($_POST['lastName'])){
			throw new Adrlist_CustomException('','$_POST[\'lastName\'] is empty.');
		}elseif(empty($_POST['email'])){
			throw new Adrlist_CustomException('','email is empty.');
		}elseif(!emailValidate($_POST['email'])){
			throw new Adrlist_CustomException('','Email address is not valid.');		
		}elseif(!passwordValidate($_POST['password'])){
			throw new Adrlist_CustomException('','$_POST[\'password\'] is not valid.');
		}elseif(empty($_POST['password'])){
			throw new Adrlist_CustomException('','$_POST[\'password\'] is empty.');
		}elseif(empty($_POST['timeZone'])){
			throw new Adrlist_CustomException('','$_POST[\'timeZone\'] is empty.');
		}/*elseif(empty($_POST['recaptcha_challenge_field'])){
			throw new Adrlist_CustomException('','$_POST[\'recaptcha_challenge_field\'] is empty.');
		}elseif(empty($_POST['recaptcha_response_field'])){
			throw new Adrlist_CustomException('','$_POST[\'recaptcha_response_field\'] is empty.');
		}*/
		destroySession();
		$_POST['email'] = trim($_POST['email']);
		$passEncoded = sha1(trim($_POST['password']));
		$_POST['firstName'] = trim($_POST['firstName']);
		$_POST['lastName'] = trim($_POST['lastName']);
		$rememberMeCode = sha1($_POST['email']);
		$invitationCode = isset($_POST['invitationCode']) ? trim($_POST['invitationCode']) : '';
		/*
		$resp = recaptcha_check_answer(RECAPTCHAPRIVATEKEY, $_SERVER["REMOTE_ADDR"], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
		if(!$resp->is_valid && !LOCAL){
			throw new Adrlist_CustomException('The reCAPTCHA wasn\'t entered correctly. Please enter the new reCAPTCHA.','reCAPTCHA said: ' . $resp->error . '.');
		}
		$debug->add('The recaptcha response is valid.');
		*/
		$Dbc->beginTransaction();
		//See if this email address is already in use.
		$getUserIdQuery = $Dbc->prepare("SELECT
	userId AS 'userId'
FROM
	users
WHERE
	primaryEmail = ?");
		$getUserIdQuery->execute(array($_POST['email']));
		$row = $getUserIdQuery->fetch(PDO::FETCH_ASSOC);
		if(empty($row['userId'])){
			//There are no users with the email address, so insert the user record.
			$insertUserQuery = $Dbc->prepare("INSERT INTO
	users
SET
	primaryEmail = ?,
	userPassword = ?,
	firstName = ?,
	lastName = ?,
	dateAdded = ?");
			$insertUserQuery->execute(array($_POST['email'],$passEncoded,$_POST['firstName'],$_POST['lastName'],DATETIME));
			$userId = $Dbc->lastInsertId();
			if(!empty($invitationCode)){
				$debug->add('$invitationCode: ' . "$invitationCode");
				//The user is responding to an invitation. Verify the invitation code matches the email.
				$verifyInviteQuery = $Dbc->prepare("SELECT
	email as 'email'
FROM
	invitations
WHERE
	invitationCode = ? AND
	email = ? AND
	respondDate IS NULL");
				$verifyInviteQuery->execute(array($invitationCode,$_POST['email']));
				$verifyInvite = $verifyInviteQuery->fetch(PDO::FETCH_ASSOC);
				if($verifyInvite['email'] === '' || $verifyInvite['email'] === NULL){
					//The invitation code wasn't found or didn't match the email address. The user will still be created.
					$message .= '<div class="red" style="padding:10px;">An invitation wasn\'t found. It may have been cancelled by the person who made the invitation.</div>';
				}else{
					$invitedEmail = true;
					//The invitation code and email have been verified. Look for more invitations.
					$invitationsQuery = $Dbc->prepare("SELECT
	invitationId AS 'invitationId',
	folderId AS 'folderId',
	folderRoleId AS 'folderRoleId',
	listId AS 'listId',
	listRoleId AS 'listRoleId',
	senderId AS 'senderId'
FROM
	invitations
WHERE
	email = ? AND
	respondDate IS NULL");
					$invitationsQuery->execute(array($_POST['email']));
					$folderArray = array();//A nested associative array: requestingUserId => array(folderId,userFolderRoleId).
					//Insert the new user's roles from the invitation(s).
					while($invitationsRow = $invitationsQuery->fetch(PDO::FETCH_ASSOC)){
						if(!empty($invitationsRow['folderId']) && !empty($invitationsRow['folderRoleId'])){
							//Add the folder to an array for creating list roles.
							$folderArray[$invitationsRow['senderId']][$invitationsRow['folderId']] = $invitationsRow['folderRoleId'];
							//Insert the folder role.
							$insertFolderRole = $Dbc->prepare("INSERT INTO
	userFolderSettings
SET
	folderId = ?,
	userId = ?,
	folderRoleId = ?,
	dateAdded = ?");
							$insertFolderRole->execute(array($invitationsRow['folderId'],$userId,$invitationsRow['folderRoleId'],DATETIME));
						}
						if(!empty($invitationsRow['listId']) && !empty($invitationsRow['listRoleId'])){
							//Insert the list role.
							$insertListRole = $Dbc->prepare("INSERT INTO
	userListSettings
SET
	listId = ?,
	userId = ?,
	listRoleId = ?,
	dateAdded = ?");
							$insertListRole->execute(array($invitationsRow['listId'],$userId,$invitationsRow['listRoleId'],DATETIME));
						}
						//Update the invitation respond date.
						$respondDateQuery = $Dbc->prepare("UPDATE
	invitations
SET
	respondDate = ?
WHERE
	invitationId = ?");
						$respondDateQuery->execute(array(DATETIME,$invitationsRow['invitationId']));
					}
					//Insert roles for each list in the sharedFolders array.
					if(!empty($folderArray) && is_array($folderArray)){
						$debug->printArray($folderArray,'$folderArray');
						foreach($folderArray as $requestingUserId => $sharedFoldersArray){
							distributeRoles($requestingUserId,$userId,$sharedFoldersArray,true);
						}
					}elseif(!empty($folderArray)){
						error(__LINE__,'','$sharedFoldersArray must be an associative array near line ' . __LINE__ . '.<br>');
					}
				}
			}
			//Create the user's default userSettings.
			$insertUserSettingsQuery = $Dbc->prepare("INSERT
INTO
	userSiteSettings
SET
	userId = ?,
	rememberMeCode = ?,
	timeZone = ?,
	siteRoleId = ?");
			$insertUserSettingsQuery->execute(array($userId,$rememberMeCode,$_POST['timeZone'],1));
			//There is no default billing for a user. The user can select a plan, or there may be a promotion when starting an account.
			//We must insert a userBillingAction first.
			$userBillingActionStmt = $Dbc->prepare("INSERT
INTO
	userBillingActions
SET
	userId = ?,
	billingOfferId = ?,
	billingActionId = ?,
	vendorId = ?,
	billingDatetime = ?");
			$userBillingActionStmt->execute(array($userId,1,10,3,DATETIME));
			$userBillingActionId = $Dbc->lastInsertId();
			$billingQuery = $Dbc->prepare("INSERT
INTO
	userBilling
SET
	userId = ?,
	billingOfferId = ?,
	userBillingActionId = ?,
	dateAdded = ?");
			$billingQuery->execute(array($userId,1,$userBillingActionId,DATETIME));
			//Send a welcome email.
			$subject = 'Welcome to ' . THENAMEOFTHESITE . '!';
			$body = '<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="left"><font face="' . FONT . '" size="' . SIZE5 . '"><b>Welcome to ' . THENAMEOFTHESITE . '!</b><br>
&nbsp;</font></td>
	</tr>
	<tr>
		<td align="left"><font face="' . FONT . '" size="' . SIZE3 . '"></font>Create your first ADR list by logging in: <a href="' . LINKLOGIN . '/?email=' . $_POST['email'] . '">' . LINKLOGIN . '</a>.<br>
			<div>&nbsp;</div>
			<div>&nbsp;</div>
			<div>&nbsp;</div>
		</td>
	</tr>
</table>';
			$textBody = "Welcome to " . THENAMEOFTHESITE . ".\nCreate your first list by logging in: https://" . DOMAIN . "/login?email=" . $_POST['email'] . "\nThis is an automated message. Please do not reply.";
			email(EMAILDONOTREPLY,$_POST['email'],$subject,$body,$textBody);
			setcookie(REMEMBERME, $rememberMeCode, time()+60*60*24*365, COOKIEPATH, COOKIEDOMAIN, false);
			$Dbc->commit();
			$success = true;
			$returnThis['pass'] = $_POST['password'];
		}else{
			$message .= "The email address you entered is already in use. Please choose another or try logging in.<br>";
			$debug->add('The email address belongs to userId: ' . $row['userId']. '.');
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'createNewUser'){
			returnData();
		}
	}
	returnData();
}