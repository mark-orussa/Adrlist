<?php require_once ('../../includes/config.php');//Place at top of all pages before all other includes.
$fileInfo = array('title' => 'Error Report', 'fileName' => 'errors/report.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('errorReportingMethods.php');
$buildPage->addJs(array('errorReporting.js','jquery/jquery.datetimepicker.js'));
$buildPage->addCss(array('jquery.datetimepicker.css'));
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">
		', $fileInfo['title'], '
	</div>
	<div class="textCenter" id="dailyDigestHolder">
		', buildDailyDigest(), '
	</div>
</div>
', $buildPage->buildFooter();?>