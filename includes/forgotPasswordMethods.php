<?php /*
This file and it's functions are to be used solely by ../forgotPassword/index.php in conjunction with ../js/forgotPassword.js.
*/
$fileInfo = array('fileName' => 'includes/forgotPasswordMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'buildReset'){
	buildReset();
}elseif(MODE == 'resetPasswordStep1'){
	resetPasswordStep1();
}elseif(MODE == 'resetPasswordStep2'){
	resetPasswordStep2();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function buildReset(){
	global $debug, $message, $Dbc;
	$output = '';
	if(!empty($_COOKIE['adrListRememberMe'])){
		$emailStmt = $Dbc-> prepare("SELECT
	users.primaryEmail AS 'primaryEmail'
FROM
	users
JOIN
	userSiteSettings ON userSiteSettings.userId = users.userId AND
	rememberMeCode = ?");
		$emailStmt->execute(array($_COOKIE['adrListRememberMe']));
		$email = $emailStmt->fetch(PDO::FETCH_ASSOC);
	}
	//Build the enter password field.
	$resetPasswordOutput = '<div class="ui-field-contain">
	<label for="loginEmail" unused="ui-hidden-accessible">Email</label>
	<input autocapitalize="off" autocorrect="off" data-wrapper-class="true" id="emailReset" goswitch="resetPasswordStep1" name="loginEmail" placeholder="" type="email" value="';
	if(!empty($_SESSION['primaryEmail'])){
		$email = $_SESSION['primaryEmail'];
	}elseif(!empty($email['primaryEmail'])){
		$email = $email['primaryEmail'];
	}
	$resetPasswordOutput .= empty($email) ? '' : $email;
	$resetPasswordOutput .= '">
</div>
<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-lock" id="resetPasswordStep1">Send Email</button>';
	if(!empty($_GET['resetCode']) && strlen($_GET['resetCode']) == 40){
		//Check if the code is in the database and has not been responded to already.
		$resetCodeCheckStmt = $Dbc-> prepare("SELECT
	fpId AS 'fpId'
FROM
	forgotPassword
WHERE
	resetCode = ? AND
	responded IS NULL");
		$resetCodeCheckStmt->execute(array($_GET['resetCode']));
		$resetCodeCheckRow = $resetCodeCheckStmt->fetch(PDO::FETCH_ASSOC);
		if(!empty($resetCodeCheckRow['fpId'])){
			//A valid resetCode exists. Build the reset password fields.
			$newPasswordOutput = '
<div class="ui-field-contain">
	<label for="loginPassword" unused="ui-hidden-accessible">New Password</label>
	<input autocapitalize="off" autocorrect="off" name="loginPassword" id="pass1" goswitch="resetPasswordStep2" placeholder="" value="" type="password">
</div>
<div class="ui-field-contain">
	<label for="loginPassword" unused="ui-hidden-accessible">Re-enter Password</label>
	<input autocapitalize="off" autocorrect="off" name="loginPassword" id="pass2" goswitch="resetPasswordStep2" placeholder="" value="" type="password">
</div>
<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-lock" id="resetPasswordStep2" resetcode="' . $_GET['resetCode'] . '">Save Password</button>';
			$output = $newPasswordOutput;
		}else{
			$output .= '<div class="textCenter" style="margin:2em;">The link has expired. Please follow the instructions below.</div>' . $resetPasswordOutput;
		}
	}else{
		$output = $resetPasswordOutput;
	}
	return '<div class="center textCenter">' . $output . '</div>';
}

function resetPasswordStep1(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['email'])){
			throw new Adrlist_CustomException('','$_POST[\'email\'] is empty.');
		}
		$_POST['email'] = trim($_POST['email']);
		$emailValidate = emailValidate($_POST['email']);
		if($emailValidate !== true){
			throw new Adrlist_CustomException('','$_POST[\'email\'] is not valid.');
		}
		$Dbc->beginTransaction();
		//See if a user with the email exists before sending.
		$emailCheckQuery = $Dbc->prepare("SELECT
	userId AS 'userId'
FROM
	users
WHERE
	primaryEmail = ?");
		$debug->add('$_POST[\'email\']: ' . $_POST['email'] . '.');
		$emailCheckQuery->execute(array($_POST['email']));
		$row = $emailCheckQuery->fetch(PDO::FETCH_ASSOC);
		if(empty($row['userId'])){
			$message .= 'Please <a href="' . LINKSUPPORT . '">contact support</a> for help with accessing your account.<br>';
		}else{
			$resetCode = sha1($_POST['email'] . DATETIME);
			$insertQuery = $Dbc->prepare("INSERT INTO
	forgotPassword
SET
	userId = ?,
	emailEntered = ?,
	resetCode = ?,
	requestMade = ?,
	REMOTE_ADDR = ?,
	HTTP_X_FORWARDED_FOR = ?");
			$httpX = empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '' : $_SERVER['HTTP_X_FORWARDED_FOR'];
			$insertQuery->execute(array($row['userId'],$_POST['email'],$resetCode,DATETIME,$_SERVER['REMOTE_ADDR'],$httpX));
			$resetLink = LINKFORGOTPASSWORD . '/?resetCode=' . $resetCode;//This will build https://adrlist.....
			$subject = 'Reset password at ' . THENAMEOFTHESITE;
			$body = '<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE4 . '"><b>Please follow the link below to reset your password:</b></font></td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE4 . '"><a href="' . $resetLink . '">' . $resetLink . '</a>
			</font>
			<div>&nbsp;</div>
			<div>&nbsp;</div>
			<div>&nbsp;</div>
		</td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE2 . '">The request was sent from ' . $_SERVER['REMOTE_ADDR'] . '. If you did not request to reset your password, please ignore this message.</font></td>
	</tr>
</table>';
			$textBody = "Please follow this link to reset your password: " . $resetLink . "\nIf you did not request to reset your password, please ignore this message.";
			$insertId = $Dbc->lastInsertId();
			if(!empty($insertId) && email(EMAILDONOTREPLY,$_POST['email'],$subject,$body,$textBody) === true){
				$Dbc->commit();
				$success = true;
				$message .= 'An email has been sent to ' . $_POST['email'] . ' with instructions on how to reset your password.
<div class="red textCenter" style="margin:1em 0">Didn\'t get an email? Be sure to check your spam folder.</div>';
				$returnThis['buildReset'] = buildReset();
			}else{
				$Dbc->rollback();
				error(__LINE__,false,'Could not add the record on line ' . __LINE__ . ' in forgotPasswordMethods.php.<br>');
			}
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
		if(MODE == 'resetPasswordStep1'){
			returnData();
		}
	}			
	if(MODE == 'resetPasswordStep1'){
		returnData();
	}
}

function resetPasswordStep2(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['pass'])){
			$debug->add('$_POST[\'pass\'] is empty.');
		}elseif(!passwordValidate($_POST['pass'])){
			throw new Adrlist_CustomException('Password must be 6-20 characters. !@ allowed.','');
		}elseif(empty($_POST['resetCode']) || strlen($_POST['resetCode']) != 40){
			throw new Adrlist_CustomException('There is a problem with the reset code. Please verify that the whole code, as seen in the email, exists in the url.','resetCode is ' . strlen($_POST['resetCode']) . ' characters.');
		}
		if(passwordValidate($_POST['pass'])){
			$password = sha1(trim($_POST['pass']));
			$resetPasswordQuery = $Dbc->prepare("UPDATE
	users, forgotPassword
SET
	users.userPassword = ?,
	forgotPassword.responded = ?,
	forgotPassword.REMOTE_ADDR = ?
WHERE
	users.userId = forgotPassword.userId AND
	forgotPassword.resetCode = ?");
			$resetPasswordQueryParams = array($password,DATETIME,gethostbyname($_SERVER['SERVER_NAME']),$_POST['resetCode']);
			$resetPasswordQuery->execute($resetPasswordQueryParams);
			$success = true;
			$returnThis['url'] = LINKLOGIN . '/?message=Please login using your new password.';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}			
	if(MODE == 'resetPasswordStep2'){
		returnData();
	}else{
		return $output;
	}
}
