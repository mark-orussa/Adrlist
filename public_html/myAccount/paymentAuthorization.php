<?php require_once('../../includes/auth.php');
$fileInfo = array('title' => 'Payment Authorization', 'fileName' => 'myAccount/paymentAuthorization.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes(array('Classes/Amazon/.config.inc.php','paymentAuthorizationMethods.php'));
$buildPage->addJs(array('paymentAuthorization.js'));
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textLarge" id="countDown">Processing the transaction...</div>
</body>
</html>', $debug->output();
/*
We are finishing this transaction. You will be redirected to your account momentarily.
*/