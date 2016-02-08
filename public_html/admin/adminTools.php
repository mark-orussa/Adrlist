<?php require_once('../../includes/siteAdmin.php');
$fileInfo = array('title' => 'Admin Tools', 'fileName' => 'admin/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('adminToolsMethods.php');
$buildPage->addJs(array('adminTools.js','jquery/jquery.datetimepicker.js'));
$buildPage->addCss(array('jquery/jquery.datetimepicker.css'));
echo $buildPage->output(), '
<div>
	', debugSwitch(), '
</div>
<div>
	', maintSwitch(), '
</div>
<div id="listMaintHolder">
	', buildListMaint(), '
</div>
', $buildPage->buildFooter();?>