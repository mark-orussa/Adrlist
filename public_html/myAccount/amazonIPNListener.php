<?php require_once('../../includes/config.php');
$fileInfo = array('title' => 'Amazon IPN Listener', 'fileName' => 'myAccount/amazonIPNListener.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes(array('Classes/Amazon/.config.inc.php','amazonIPNListenerMethods.php'));