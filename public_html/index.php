<?php require_once('../includes/config.php');
require('indexMethods.php');
$fileInfo = array('title' => '', 'fileName' => 'index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addErrorMessage($PHPErrorHandler->getErrorMessage());
//$buildPage->addIncludes(array('planMethods.php'));
$buildPage->addJs(array('index.js','jquery/blueberrySlider/jquery.blueberry.js'));
$buildPage->addCss(array('blueberrySlider/blueberry.css'));
echo $buildPage->output(THENAMEOFTHESITE),
buildSlides(),
buildFeatures(),
$buildPage->buildFooter();