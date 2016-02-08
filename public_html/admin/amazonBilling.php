<?php require_once('../../includes/siteAdmin.php');
$fileInfo = array('title' => 'Amazon Billing', 'fileName' => 'admin/amazonBilling.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('amazonBillingMethods.php');
$buildPage->addJs(array('jquery/jquery.datetimepicker.js','amazonBilling.js','dateHandling.js'));
$buildPage->addCss(array('jquery.datetimepicker.css'));
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">
		', $fileInfo['title'], '
	</div>
	<div class="textCenter" id="amazonBillingHolder">
		', buildAmazonBilling(), '
	</div>
</div>
', $buildPage->buildFooter();?>