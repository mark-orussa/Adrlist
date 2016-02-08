<?php require_once('../../includes/ssl.php');
if(!empty($_SESSION['auth'])){
	header('Location:' . LINKADRLISTS);
}
$fileInfo = array('title' => 'Join', 'fileName' => 'join/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('joinMethods.php');
$buildPage->addJs(array('join.js','https://www.google.com/recaptcha/api/js/recaptcha_ajax.js'));
/*
if(!isset($_GET['invitationCode'])){
	$buildPage->addJs('http://www.google.com/recaptcha/api/js/recaptcha_ajax.js');
}
*/
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">
		', $fileInfo['title'], '
	</div>
	<div class="textCenter red" id="responseElement">
		', $message, '
	</div>
	<noscript class="red textCenter">Javascript is required.</noscript>
	<div id="joinUser" style="display:none">
		', buildJoin(), '
	</div>
</div>
', $buildPage->buildFooter();