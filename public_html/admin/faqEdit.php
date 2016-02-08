<?php require_once('../../includes/siteAdmin.php');
$fileInfo = array('title' => 'FAQ Edit', 'fileName' => 'admin/faqEdit.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('faqEditMethods.php');
$buildPage->addCss('faq.css');
$buildPage->addJs('faqEdit.js');
echo $buildPage->output(), '
<div class="layout" id="main">
	<div id="buildFaqs" class="break">
		', buildFaqs(), '
	</div>
</div>',
$buildPage->buildFooter();?>