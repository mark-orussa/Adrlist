<?php require_once('../../includes/auth.php');
$fileInfo = array('title' => 'My Account', 'fileName' => 'myAccount/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes(array('Classes/Amazon/.config.inc.php','myAccountMethods.php'));
$buildPage->addCss('plans.css');
$buildPage->addJs(array('myAccount.js'));
echo $buildPage->output(), '
<div class="myAccountSection" id="buildBillingHolder">
	', buildBilling(), '
</div>
<div class="myAccountSection">
	', buildMyInformation(), '
</div>
<div class="myAccountSection">
	', buildSettings(), '
</div>
', $buildPage->buildFooter();