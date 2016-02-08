<?php require_once('../../includes/auth.php');
$fileInfo = array('title' => 'Edit List','fileName' => 'lists/listEdit.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$listEdit = new Adrlist_ListEdit();
$buildPage->addJs(array('jscolor/jscolor.js','listEdit.js'));
$buildPage->addCss('listEdit.css');
echo $buildPage->output(), '
', $listEdit->buildEditListHeader(), $listEdit->buildAddLine(), '
<div id="buildLinesHolder">
	', $listEdit->buildLines(), '
</div>
', $buildPage->buildFooter();