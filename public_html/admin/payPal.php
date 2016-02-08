<?php require_once('../../includes/siteAdmin.php');
$fileInfo = array('title' => 'PayPal', 'fileName' => 'admin/payPalMethods.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('payPalMethods.php');
$buildPage->addJs(array('payPal.js'));
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">
		', $fileInfo['title'], '	</div>
	<div class="break relative">
		<div class="absolute" style="right:5px">
			', buildSearch('buildIpn','IPN Tranactions'), '
		</div>
		<ul>
			<li class="sectionTitle">PayPal IPN (Instant Payment Notification) Transactions</li>
		</ul>
	</div>
	<div class="textLeft" id="ipnHolder">
		', buildIpn(), '
	</div>
	<div class="break relative">
		<div class="absolute" style="right:5px">
			', buildSearch('buildIpnErrors','IPN Errors'), '
		</div>
		<ul>
			<li class="sectionTitle">IPN Errors</li>
		</ul>
	</div>
	<div class="textLeft" id="ipnErrorsHolder">
		', buildIpnErrors(), '
	</div>
	<div class="break relative">
		<div class="absolute" style="right:5px">
			', buildSearch('buildPdt','PDT Transactions'), '
		</div>
		<ul>
			<li class="sectionTitle">PayPal PDT (Payment Data Transfer) Transactions</li>
		</ul>
	</div>
	<div class="textLeft" id="pdtHolder">
		', buildPdt(), '
	</div>
	<div class="break relative">
		<div class="absolute" style="right:5px">
			', buildSearch('buildPdtErrors','PDT Errors'), '
		</div>
		<ul>
			<li class="sectionTitle">PDT Errors</li>
		</ul>
	</div>
	<div class="textLeft" id="pdtErrorsHolder">
		', buildPdtErrors(), '
	</div>
</div>',
$buildPage->buildFooter();?>