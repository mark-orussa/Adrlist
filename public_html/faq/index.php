<?php require_once('../../includes/config.php');
$fileInfo = array('title' => 'FAQ', 'fileName' => 'faq/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('faqMethods.php');
$buildPage->addCss('faq.css');
$buildPage->addJs(array('faq.js'));
$output = $buildPage->output() . '
<div class="break" id="buildFaqsHolder">
	'. buildFaqs(). '
</div>
'. $buildPage->buildFooter();
echo $output;