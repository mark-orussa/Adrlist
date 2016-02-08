<?php require_once ('../../includes/config.php');//Place at top of all pages before all other includes.
header("HTTP/1.1 401 Unauthorized");
$fileInfo = array('title' => '401 Error', 'fileName' => 'errors/401.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('errorReportingMethods.php');
//errorReporting(401);
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">
		', $fileInfo['title'], '
	</div>
	<div class="textCenter">
		Sorry, you don\'t have permission to view this page.
	</div>
</div>
', $buildPage->buildFooter();?>