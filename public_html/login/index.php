<?php require_once('../../includes/auth.php');
$fileInfo = array('title' => 'Login', 'fileName' => 'login/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('loginMethods.php');
$buildPage->addJs(array('login.js'));//'https://www.google.com/recaptcha/api/js/recaptcha_ajax.js'
echo $buildPage->output(),
buildLogin(),
$buildPage->buildFooter();