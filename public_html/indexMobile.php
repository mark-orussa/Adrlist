<?php require_once('../includes/config.php');
$fileInfo = array('title' => 'Index Mobile', 'fileName' => 'indexMobile.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes(array('indexMethods.php','planMethods.php'));
$buildPage->addJs(array('index.js','carousel.js'));
$buildPage->addCss(array('carousel.css','plans.css'));
echo $buildPage->output(), '
<div data-role="tabs" id="tabs">
	<div data-role="navbar">
		<ul>
			<li><a href="#one" data-ajax="false">one</a></li>
			<li><a href="#two" data-ajax="false">two</a></li>
			<li><a href="' . LINKFAQ . '" data-ajax="false">FAQS</a></li>
		</ul>
	</div>
	<div id="one" class="ui-body-d ui-content">
		<h1>First tab contents</h1>
	</div>
	<div id="two">
		<ul data-role="listview" data-inset="true">
			<li><a href="#">Acura</a></li>
			<li><a href="#">Audi</a></li>
			<li><a href="#">BMW</a></li>
			<li><a href="#">Cadillac</a></li>
			<li><a href="#">Ferrari</a></li>
		</ul>
	</div>
</div>
',
$buildPage->buildFooter();