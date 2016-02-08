<?php require_once('../../includes/auth.php');
require_once(INCLUDEPATH . 'scenesMethods.php');
$fileInfo = array('title' => 'Scenes', 'fileName' => 'scenes/index.php');
$debug->newFile($fileInfo['fileName']);
$buildTop = new buildTop();
$buildTop->addJs('scenes.js');
echo $buildTop->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">', $fileInfo['title'], '</div>
	<div class="textCenter red">', $message , '</div>
	<div id="addScenesReturn">
		', buildAddScene(), '
	</div>
	<div id="scenesReturn">
		', buildScenes(), '
	</div>
</div>
', buildFooter();?>