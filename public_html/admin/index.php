<?php require_once('../../includes/siteAdmin.php');
$fileInfo = array('title' => 'Admin', 'fileName' => 'admin/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
echo $buildPage->output(), '
<ul class="sectionTitle">
	<li><a href="', LINKADMINTOOLS, '" data-ajax="false">Admin Tools</a></li>
	<li><a href="', LINKADMIN, '/amazonBilling.php" data-ajax="false">Amazon Billing</a></li>
	<li><a href="', LINKERRORREPORTING, '" data-ajax="false">Error Report</a></li>
	<li><a href="', LINKFAQEDIT, '" data-ajax="false">FAQ Edit</a></li>
	<li><a href="', LINKPAYPAL, '" data-ajax="false">PayPal</a></li>
	<li><a href="', LINKADMIN, '/transferLists.php" data-ajax="false">Transfer Lists</a></li>
	<li><a href="', LINKUSERMANAGEMENT, '" data-ajax="false">User Management</a></li>
</ul>',
$buildPage->buildFooter();?>