<?php require_once('../../includes/auth.php');
$fileInfo = array('title' => 'ADR Lists', 'fileName' => 'adrLists/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('adrListsMethods.php');
$buildPage->addJs('adrLists.js');
echo $buildPage->output(), '
<div id="buildListsHolder">
	', buildLists(), '
</div>
',  $buildPage->buildFooter();?>