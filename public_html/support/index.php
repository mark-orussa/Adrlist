<?php require_once('../../includes/config.php');
$fileInfo = array('title' => 'Support', 'fileName' => 'support/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('supportMethods.php');
$buildPage->addJs(array('support.js','https://www.google.com/recaptcha/api/js/recaptcha_ajax.js'));
echo $buildPage->output(),
buildSupport(),
$buildPage->buildFooter();