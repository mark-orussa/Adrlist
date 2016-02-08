<?php require_once ('../../includes/config.php');//Place at top of all pages before all other includes.
header("HTTP/1.1 404 Not Found");
$fileInfo = array('title' => '404 Error', 'fileName' => 'errors/404.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('errorReportingMethods.php');
//errorReporting(404);
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">
		', $fileInfo['title'], '
	</div>
	<div class="textCenter">
		Sorry, but that page wasn\'t found.
	</div>
</div>
', $buildPage->buildFooter();