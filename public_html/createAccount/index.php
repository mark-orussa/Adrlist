<?php require_once('../../includes/auth.php');
if(isset($_SESSION['auth']) && $_SESSION['auth']){
	header('Location:' . LINKADRLISTS);
}
$fileInfo = array('title' => 'Create New Account', 'fileName' => 'createAccount/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes(array('createAccountMethods.php'));
$buildPage->addJs(array('createAccount.js'));//https://www.google.com/recaptcha/api/js/recaptcha_ajax.js
echo $buildPage->output(),
buildCreateAccount(),
$buildPage->buildFooter();?>