<?php require_once('recaptchalib.php');
$fileInfo = array('title' => 'My Account', 'fileName' => 'myAccount/index.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'supportSend'){
	supportSend();
}else{
	$debug->add('There is no matching mode.');
}

function buildSupport(){
	global $debug, $message;
	$outputOld = '<div class="break relative">
	<div class="textCenter textLarge">
		Use the form below to send a message to support.
	</div>
	<ul class="textCenter" style="margin:1em;">
		<li>You may find the answer to your question by searching the <a href="' . LINKFAQ . '">FAQs</a> <img alt="" src="' . LINKIMAGES . '/newWindow.gif">.</li>
	</ul>
	<div class="break" style="margin-top:2em;">
		<div class="columnLeft">Your Name:&nbsp;</div>
		<div class="columnRight"><input id="supportName" goswitch="supportSend" style="width:15em;" tabindex="1" type="text" value="" autocorrect="off" autocapitalize="on"></div>
	</div>
	<div class="break">
		<div class="columnLeft">Your Email:&nbsp;</div>
		<div class="columnRight"><input id="supportEmail" value="" goswitch="supportSend" style="width:20em;" tabindex="2" type="email" autocorrect="off" autocapitalize="off"> <span class="textSmall">(Just so we can get back to you. <a href="' . LINKPRIVACY . '" target="new">Privacy Policy</a></span> <img src="' . LINKIMAGES . '/newWindow.gif" style="height:1em;width:1em;">)</div>
	</div>
	<div class="break">
		<div class="columnLeft">Your Message:&nbsp;</div>
		<div class="columnRight"><textarea id="supportMessage" style="height:10em;width:40em;font-family:inherit" tabindex="3"></textarea></div>
	</div>
	<div class="break" id="recaptchaElement" style="padding:2px 0"></div>
	<div class="break textCenter" style="padding:2em 0">
		<span class="buttonBlue gradientBlue" id="supportSend">Send</span>
	</div>
';
	$output = '<div class="textCenter center">
	<div class="textCenter">
		Use the form below to send a message to support.
	</div>
		<p>You may find the answer to your question by searching the <span class="nowrap"><a data-ajax="false" href="' . LINKFAQ . '" target="_blank">FAQs</a> <i class="fa fa-external-link"></i>.</span></p>
	<div class="ui-field-contain">
		<label for="supportName" unused="ui-hidden-accessible">Your Name</label>
		<input data-wrapper-class="true" id="supportName" goswitch="supportSend" name="supportName" placeholder="" value="" type="text">
	</div>
	<div class="ui-field-contain">
		<label for="supportEmail" unused="ui-hidden-accessible">Your Email</label>
		<input id="supportEmail" goswitch="supportSend" name="supportEmail" placeholder="" value="" type="email">
	</div>
	<div class="ui-field-contain">
		<label for="supportMessage" unused="ui-hidden-accessible">Your Message</label>
		<textarea id="supportMessage" goswitch="supportSend" name="supportMessage" placeholder="" value=""></textarea>
	</div>
	<div>
		<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-mail" id="supportSend">Send</button>
	</div>
</div>
';
/* 
	<div data-role="tabs" id="tabs">
	<div class="ui-corner-all" data-role="navbar">
		<ul>
			<li><a class="ui-btn-active" href="#faqs" data-ajax="false">FAQs</a></li>
			<li><a href="#contact" data-ajax="false">Contact</a></li>
		</ul>
	</div>
	<div id="faqs">
		<ul data-role="listview" data-inset="true">
			<li><a href="#">Acura</a></li>
			<li><a href="#">Audi</a></li>
			<li><a href="#">BMW</a></li>
			<li><a href="#">Cadillac</a></li>
			<li><a href="#">Ferrari</a></li>
		</ul>
	</div>
	<div id="contact" class="ui-body-d ui-content">' . $contact . '</div>
</div>

*/
	return $output;
	//<div class="break" id="recaptchaElement" style="padding:2px 0"></div>
}

function supportSend(){
	//Disabled the recaptcha 2014-03-09.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		$emailValidate = emailValidate($_POST['supportEmail']);
		if(empty($_POST['supportName'])){
			throw new Adrlist_CustomException('','$_POST[\'supportName\'] is empty.');
		}elseif($emailValidate === false){
			throw new Adrlist_CustomException('','$_POST[\'supportEmail\'] is not valid.');
		}elseif(empty($_POST['supportMessage'])){
			throw new Adrlist_CustomException('','$_POST[\'supportMessage\'] is empty.');
		}/*elseif(empty($_POST['recaptcha_challenge_field'])){
			throw new Adrlist_CustomException('','$_POST[\'recaptcha_challenge_field\'] is empty.');
		}elseif(empty($_POST['recaptcha_response_field'])){
			throw new Adrlist_CustomException('','$_POST[\'recaptcha_response_field\'] is empty.');
		}
		$resp = recaptcha_check_answer(RECAPTCHAPRIVATEKEY, $_SERVER["REMOTE_ADDR"], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
		if($resp->is_valid || LOCAL){
			$debug->add('The recaptcha response is valid.');*/
			//See if the user has an account.
			$accountCheckStmt = $Dbc->prepare("SELECT
	userId AS 'userId'
FROM
	users
WHERE
	primaryEmail = ? OR
	secondaryEmail = ?");
			$accountCheckStmt->execute(array($_POST['supportEmail'],$_POST['supportEmail']));
			if($row = $accountCheckStmt->fetch(PDO::FETCH_ASSOC)){
				//Add the question to the user's support section.
				$newMessage = new Adrlist_MessageCenter();
				$message .= 'Thank you for contacting us!<br>
<br>
Your message has been received. A response will be sent to the message center.';
				$newMessage->newMessage($row['userId'],1,'A message sent from the contact page',$_POST['supportMessage']);
			}else{
				//Send the message.
				$subject = $_POST['supportName'] . ' sent a message to support at ' . THENAMEOFTHESITE . '.';
				$bodyText = 'From: ' . $_POST['supportName'] . ' (' . $_POST['supportEmail'] . ')
Sent on: ' . Adrlist_Time::utcToLocal(false,false)->format('F d, Y H:i:s') . '.';
				$bodyHtml = 'From: ' . $_POST['supportName'] . ' (' . $_POST['supportEmail'] . ')<br>
Sent on: ' . Adrlist_Time::utcToLocal(false,false)->format('F d, Y H:i:s') . '<br>
Mesage:<br>
' . nl2br($_POST['supportMessage']);
				//$fromAddress,$toAddress,$subject,$bodyHtml,$bodyText,$senderAddress = NULL,$returnAddress = NULL
				if(email($_POST['supportEmail'],EMAILSUPPORT,$subject,$bodyHtml,$bodyText,$_POST['supportEmail'])){
					$message .= 'Thank you for contacting us! We will get back to you as soon as we can.';
					$success = true;
					$debug->add('used the function email(' . $_POST['supportEmail'] . ',' . EMAILSUPPORT . ',$subject,$bodyHtml,$bodyText,' . EMAILSUPPORT);
					$debug->add('$subject:' . $subject . '<br>
$bodyHtml:' . $bodyHtml . '<br>
$bodyText:' . $bodyText);
				}else{
					throw new Adrlist_CustomException('','There was a problem trying to send an email.');
				}
			}
		/*}else{
			//Set the error code so that we can display it.
			$message .= 'The reCAPTCHA wasn\'t entered correctly. Please enter the new reCAPTCHA.';
			$debug->add('reCAPTCHA said: ' . $resp->error);
		}*/
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'supportSend'){
		returnData();
	}
}
