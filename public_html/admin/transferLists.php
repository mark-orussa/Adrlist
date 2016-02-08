<?php require_once('../../includes/siteAdmin.php');
$fileInfo = array('title' => 'Transfer Lists', 'fileName' => 'admin/transferLists.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('transferListsMethods.php');
$buildPage->addJs('transferLists.js');
echo $buildPage->output(), '
<div class="layout" id="main">
	<div id="getOldLinesHolder"></div>
	<div class="red textCenter">Caution! Any actions here will have serious effect on the database.</div>
	<div class="textCenter">
		<input id="getOldLines" type="button" value="Get Old Lines">
		<input id="convertHistLines" type="button" value="Convert Line History">
		<input id="convertHistCharacters" type="button" value="Convert Character History">
		<input id="convertHistLists" type="button" value="Convert List History">
	</div>
</div>',
$buildPage->buildFooter();?>