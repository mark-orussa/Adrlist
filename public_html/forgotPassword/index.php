<?php
define('STARTSECURE', true, 1);
include('../../includes/config.php');
$fileInfo = array('title' => 'Forgot Password', 'fileName' => 'forgotPassword/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('forgotPasswordMethods.php');
$buildPage->addJs('forgotPassword.js');
echo $buildPage->output(), '
<div id="resetHolder">
	', buildReset(), '
</div>
', $buildPage->buildFooter();?>