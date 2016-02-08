<?php require_once('../../includes/config.php');
$fileInfo = array('title' => 'Plans', 'fileName' => 'plans/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
//$buildPage->addIncludes('planMethods.php');
//$buildPage->addJs('plans.js');
$buildPage->addCss('plans.css');
echo $buildPage->output(),
Adrlist_Billing::buildPlans(),
$buildPage->buildFooter();?>