<?php
require_once('recaptchalib.php');
$fileInfo = array('fileName' => 'includes/loginMethods.php');
$debug->newFile($fileInfo['fileName']);
if(MODE == 'buildLogin'){
	buildLogin();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function buildLogin(){
	/*
	This function builds the login form for existing users and the "create new account" link for new users. If an invitation code is detected it will lock in the email address.
	*/
	global $debug, $message, $Dbc;
	$output = '';
	try{
		//See if the user has selected to remember their login email address.
		if(!empty($_COOKIE[REMEMBERME])){
			$stmt = $Dbc->prepare("SELECT
	users.primaryEmail AS 'primaryEmail'
FROM
	users
JOIN
	userSiteSettings ON userSiteSettings.userId = users.userId AND
	userSiteSettings.rememberMeCode = ?");
			$stmt->execute(array($_COOKIE[REMEMBERME]));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if(empty($row)){
				error(__LINE__);
				pdoError(__LINE__, $stmt, 1);
			}
		}
	//Build the output.
	$checked = '';
	$email = '';
	if(!empty($_GET['email'])){
		$email = $_GET['email'];
	}elseif(!empty($row['primaryEmail'])){
		$checked = ' checked="yes"';
		$email = $row['primaryEmail'];
	}
	$output .= '<div class="validationWarningPlaceholder textCenter"></div>
<div class="textCenter center">
	<input autocapitalize="off" autocorrect="off" data-clear-btn="true" data-wrapper-class="center" id="loginEmail" goswitch="loginButton" name="loginEmail" placeholder="Email" value="' . $email . '" type="email">
	<input autocapitalize="off" autocorrect="off" data-clear-btn="true" data-wrapper-class="center" id="loginPassword" goswitch="loginButton" name="loginPassword" placeholder="Password" value="" type="password">
	<form>
		<label class="ui-hidden-accessible" for="rememberMe">Remember Me</label>
		<input data-role="flipswitch" name="rememberMe" id="rememberMe" data-on-text="Remember" data-off-text="Forget" data-wrapper-class="custom-size-flipswitch" type="checkbox"' . $checked . '>
	</form>
	<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-lock" id="loginButton">Login</button>
	<div class="hr1" style="margin:1em"></div>
	<fieldset class="ui-grid-a">
		<a data-ajax="false" href="' . LINKFORGOTPASSWORD . '" class="ui-btn ui-mini ui-btn-inline ui-corner-all"><i class="absolute fa fa-question-circle fa-2x" style="color:#AAA;left:.2em;top:.2em"></i><span style="margin-left:1.5em">Forgot Password<span></a>
		<a data-ajax="false" href="' . LINKCREATEACCOUNT . '" class="ui-btn ui-mini ui-btn-icon-left ui-icon-plus ui-btn-inline ui-corner-all">Create Account</a>
	</fieldset>
</div>
';
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	return $output;
}