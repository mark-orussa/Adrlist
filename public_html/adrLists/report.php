<?php require_once('../../includes/auth.php');
$fileInfo = array('title' => 'Report for', 'fileName' => 'adrLists/report.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('reportMethods.php');
$buildPage->addJs('report.js');
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="relative textCenter">
	', buildReport(), buildTRT(),'
	</div>
</div>
', $buildPage->buildFooter();?>