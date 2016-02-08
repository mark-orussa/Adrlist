<?php require_once('../../includes/config.php');
$fileInfo = array('title' => 'Features', 'fileName' => 'features/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('indexMethods.php');
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="break textCenter" style="font-size:2em;line-height:2em">', $fileInfo['title'], '</div>
', buildFeatures(), '	
</div>',
$buildPage->buildFooter();