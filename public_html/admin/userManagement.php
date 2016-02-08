<?php require_once('../../includes/siteAdmin.php');
$fileInfo = array('title' => 'User Management', 'fileName' => 'admin/userManagement.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
$buildPage->addIncludes('userManagementMethods.php');
$buildPage->addJs('userManagement.js');
echo $buildPage->output(), '
<div class="layout" id="main">
	<div class="textCenter textXlarge">
		', $fileInfo['title'], '
	</div>
	<div class="break relative">
		<ul>
			<li class="sectionTitle">List Information</li>
		</ul>
		<div class="absolute" style="right:5px">
			', buildSearch('buildLists','Search Folders and Lists'), '
		</div>
			', buildListLetters(), '
			<div class="textLeft" id="buildUMListsHolder">
			</div>
	</div>
	<div class="break relative">
		<div class="absolute" style="right:5px">
			', buildSearch('buildUsers','Search Users'), '
		</div>
		<div class="sectionTitle textCenter">User Information</div>
		<div class="textLeft" id="buildUsersHolder">
			', buildUsers(), '
		</div>
	</div>
	<div class="bold link textLarge listMaintTrigger" style="margin-top:1em" triggerThis="showUsers">Block Users</div>
Use this feature to prevent a user from logging in.
	<div id="showUsers" class="hide">
		<div class="absolute" style="left:5px;z-index:100">
			', buildSearch('buildBlockUser','Search first or last name or email'), '
		</div>
		<div id="blockUsersHolder">
			', 	buildBlockUsers(), '
		</div>
	</div>
</div>',
$buildPage->buildFooter();
//', buildUserInfo(), '?>