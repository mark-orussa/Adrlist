<?php /*
This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
This file and it's functions are to be used solely by admin/userManagement.php in conjunction with js/userManagement.js.
*/
require_once('siteAdmin.php');
$fileInfo = array('title' => 'Login', 'fileName' => 'includes/userManagementMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'buildUMLists'){
	buildUMLists();
}elseif(MODE == 'buildUsers'){
	buildUsers();
}elseif(MODE == 'buildBlockUsers'){
	buildBlockUsers();
}elseif(MODE == 'blockUser'){
	blockUser();
}elseif(MODE == 'deleteUser'){
	deleteUser();
}elseif(MODE == 'updateSiteRole'){
	updateSiteRole();
}elseif(MODE == 'updateFolderRole'){
	updateFolderRole();
}elseif(MODE == 'updateListRole'){
	updateListRole();
}elseif(MODE == 'updateUserInfo'){
	updateUserInfo();
}elseif(MODE == 'updateSiteRole'){
	updateSiteRole();
}elseif(MODE == 'viewUserRole'){
	viewUserRole();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}


function buildBlockUsers(){
	/*
	Block a user from using the site. This is accomplished by setting the userSiteRole to 0.
	$userId = (integer) the userId to block.
	*/
	global $debug, $message, $success, $Dbc;
	$success = false;
	$output = '';
	try{
		//Build the list of users.
		$search = false;
		$limit = isset($_COOKIE['buildBlockUsersLimit']) ? $_COOKIE['buildBlockUsersLimit'] : 10;//How many rows to display at a time.
		$offset = empty($_COOKIE['buildBlockUsersOffset']) ? 0 : $_COOKIE['buildBlockUsersOffset'];//What page to start on.
		$countStmt = $Dbc->prepare("SELECT COUNT(*) AS 'count' FROM users");
		$countStmt->execute();
		$result = $countStmt->fetch(PDO::FETCH_ASSOC);
		$userCount = $result['count'];
		$userListStmt = "SELECT
	users.firstName AS 'firstName',
	users.lastName AS 'lastName',
	users.primaryEmail AS 'primaryEmail',
	userSiteSettings.siteRoleId AS 'siteRoleId'
FROM
	users
JOIN
	userSiteSettings ON users.userId = userSiteSettings.userId";
		//Limit the items shown.
		$limitStmt = NULL;
		if(!empty($limit)){
			//PHP PDO does not allow you to use user-supplied variables for LIMIT values. This is a documented bug.
			$limitStmt .= ' LIMIT ';
			if(empty($offset) || $offset > $userCount){
				$limitStmt .= 0;
			}else{
				$limitStmt .= (int)$offset;
			}
			$limitStmt .= ', ' . (int)$limit;
		}
		if(empty($_POST['searchVal'])){
			$userListStmt .= $limitStmt;
			$userListStmt = $Dbc->prepare($userListStmt);
			$userListStmt->execute();
		}else{
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$userListStmt .= " AND
	(users.firstName LIKE ? OR
	users.lastName LIKE ? OR
	users.primaryEmail LIKE ?
	)";
			$userListStmt .= $limitStmt;
			$userListStmt = $Dbc->prepare($userListStmt);
			$userListParams = array($searchVal,$searchVal,$searchVal);
			$userListStmt->execute($userListParams);
		}
		$users = '	<table class="listMaintTable">
		<tr class="bold">
			<td>#</td><td>First Name</td><td>Last Name</td><td>Primary Email</td><td>Site Role Id</td>
		</tr>';
		$x = $offset + 1;
		$foundRows = false;
		while($row = $userListStmt->fetch(PDO::FETCH_ASSOC)){
			$users .= '		<tr>
			<td class="textRight">' . $x . '</td><td>' . $row['firstName'] . '</td><td>' . $row['lastName'] . '</td><td>' . $row['primaryEmail'] . '</td><td>' . $row['siteRoleId'] . '</td>
		</tr>
';
			$x++;
			$foundRows = true;
		}
		$users .= '	</table>
';
		$pagination = new Adrlist_Pagination($userCount,$offset,$limit,'buildBlockUsers',$search);
		$pagination = $pagination->output();
		$output .= $pagination['paginationTop'] . $users . $pagination['paginationBottom'];
		$success = 1;
		$returnThis['buildBlockUsers'] = $output;
		if(!$foundRows){
			$message .= 'No users found using "' . $searchVal . '"<br>';
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildBlockUsers'){
		returnData();
	}else{
		return $output;
	}
}

function blockUser(){
	/*
	Block a user from using the site. This is accomplished by setting the userSiteRole to 0.
	$_POST['userId'] = (integer) the userId to block.
	*/
	global $debug, $message, $success, $Dbc;
	$success = false;
	$output = '';
	try{
		$blockUserStmt = $Dbc->prepare("UPDATE
	userSiteSettings
SET
	siteRoleId = ?
WHERE
	userId = ?");
		$blockUserParams = array(0,$userId);
		$blockUserStmt->execute();
		$message = 'Blocked user.';
		$success = true;
		returnData();
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
}

function buildUMLists(){//Build the user management lists section.
	global $debug, $message, $success, $Dbc;
	$message = '';
	try{
		//Get the framerate values.
		$frameratesArray = getFramerates();
		//Get the user's lists and folders with lists.
		//Lists not in folders.
		$buildListsQueryStart = "SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	lists.frId AS 'frId',
	lists.created AS 'listCreated',
	lists.modified AS 'listModified'
FROM
	lists";
		//Folders.
		$buildFoldersQueryStart = "SELECT
	folders.folderId AS 'folderId',
	folders.folderName AS 'folderName',
	folders.created AS 'folderCreated',
	folders.modified AS 'folderModified',
	DATE_FORMAT(expires, '%M %D, %Y') AS 'expires'
FROM
	folders";
		if(empty($_POST['searchVal']) && empty($_POST['letters'])){//Search for specific user(s).
			$success = true;//Will reset to show no users.
		}else{
			if(empty($_POST['searchVal']) && !empty($_POST['letters'])){
				$search = false;
				$_POST['letters'] = trim($_POST['letters']);
				$buildListsQuery = $buildListsQueryStart . "
WHERE
	folderId IS NULL AND
	listName RLIKE ?
ORDER BY
	listName";
				$buildFoldersQuery = $buildFoldersQueryStart . "
WHERE
	folderName RLIKE ?
ORDER BY
	folderName";
				$listStmt = $Dbc->prepare($buildListsQuery);
				$listParams = array('^[' . $_POST['letters'] . ']');
				$folderStmt = $Dbc->prepare($buildFoldersQuery);
				$folderParams = array('^[' . $_POST['letters'] . ']');
			}elseif(!empty($_POST['searchVal'])){//Search for specific user(s).
				$search = true;
				$searchVal = '%' . trim($_POST['searchVal']) . '%';
				$debug->add('$searchval: ' . $searchVal);
				$buildListsQuery = $buildListsQueryStart . "
WHERE
	folderId IS NULL AND
	listName LIKE ?
ORDER BY
	listName";
				$buildFoldersQuery = $buildFoldersQueryStart . "
WHERE
	folderName LIKE ?
ORDER BY
	folderName";
				$listStmt = $Dbc->prepare($buildListsQuery);
				$listParams = array($searchVal);
				$folderStmt = $Dbc->prepare($buildFoldersQuery);
				$folderParams = array($searchVal);
			}
			$listStmt->execute($listParams);
			$folderStmt->execute($folderParams);
			$output = '<div class="sectionTitle textCenter">Lists</div>
<div>
	<span class="textLeft" id="addFolderStep1" onClick=""><img alt="" class="linkPadding middle" src="' . LINKIMAGES . '/addFolder.png" style="height:15px;width:15px;"><span class="link middle">Add Folder</span></span>' . faqLink(30) . '
	<span class="textLeft" id="addListStep1" onClick=""><img alt="" class="linkPadding middle" src="' . LINKIMAGES . '/add.png" style="height:15px;width:15px;"><span class="link middle">Add List</span></span>
</div>
<div class="textLeft">
';

		$output .= '
	<div class="rowTitle" style="min-width:100px;width:30%">
		<div class="row textLeft" style="width:40px;padding:0 0 0 5px;">
			Type
		</div>
		Name
	</div>
	<div class="rowTitle" style="min-width:3em;width:8%">Framerate</div>
	<div class="rowTitle" style="min-width:5em;width:8%">Role' . faqLink(24) . '</div>
	<div class="rowTitle" style="min-width:5em;width:15%">Created</div>
	<div class="rowTitle" style="min-width:5em;width:15%">Modified</div>
	<div class="rowTitle" style="min-width:5em;width:24%"><div class="textRight">Actions' . faqLink(35) . '</div></div>
	<div class="hr2"></div>
';
			$class = 'rowWhite';
			$foundLists = false;
			$foundFolders = false;
			$lists = array();
			while($row = $listStmt->fetch(PDO::FETCH_ASSOC)){
				$foundLists = true;
				if(!array_key_exists($row['listId'],$lists)){
				$lists[$row['listId']] = array('listName' => $row['listName'], 'frId' => $row['frId'], 'created' => $row['listCreated'], 'modified' => $row['listModified']);
				}
			}
			$folders = array();
			if(empty($searchVal)){
				while($foldersRow = $folderStmt->fetch(PDO::FETCH_ASSOC)){
					$foundFolders = true;
					//Get the lists inside the folders.
					$listsInFoldersStmt = $Dbc->prepare("SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	lists.frId AS 'frId',
	lists.created AS 'listCreated',
	lists.modified AS 'listModified'
FROM
	lists
JOIN
	folders ON folders.folderId = lists.folderId AND
	folders.folderId = ?");
					$listsInFoldersParams = array($foldersRow['folderId']);
					$listsInFoldersStmt->execute($listsInFoldersParams);
					if(!array_key_exists($foldersRow['folderId'],$folders)){
					$folders[$foldersRow['folderId']] = array('folderName' => $foldersRow['folderName'], 'created' => $foldersRow['folderCreated'], 'modified' => $foldersRow['folderModified']);
					}
					while($listsInFoldersRow = $listsInFoldersStmt->fetch(PDO::FETCH_ASSOC)){
						$foundListsInFolders = true;
					$folders[$foldersRow['folderId']]['lists'][$listsInFoldersRow['listId']] = array('listName' => $listsInFoldersRow['listName'], 'frId' => $listsInFoldersRow['frId'], 'created' => $listsInFoldersRow['listCreated'], 'modified' => $listsInFoldersRow['listModified']);
					}
				}
			}else{
				//When searching, if the term is not found in a folder name, then the folder and it's lists will not be included. This prevents lists matching the search term from appearing. We must therefore create an entirely separate query to search for terms in lists inside of folders.
				$listsInFoldersSearchStmt = $Dbc->prepare("SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	lists.frId AS 'frId',
	lists.created AS 'listCreated',
	lists.modified AS 'listModified',
	folders.folderId AS 'folderId',
	folders.folderName AS 'folderName',
	folders.created AS 'folderCreated',
	folders.modified AS 'folderModified'
FROM
	lists
JOIN
	folders ON folders.folderId = lists.folderId AND
	lists.listName LIKE ?");
				$debug->add('searching lists in folders.');
				$listsInFoldersSearchParams = array($searchVal);
				$listsInFoldersSearchStmt->execute($listsInFoldersSearchParams);
				while($listsInFoldersSearchRow = $listsInFoldersSearchStmt->fetch(PDO::FETCH_ASSOC)){
					$foundFolders = true;
					if(!array_key_exists($listsInFoldersSearchRow['folderId'],$folders)){
					$folders[$listsInFoldersSearchRow['folderId']] = array('folderName' => $listsInFoldersSearchRow['folderName'], 'folderRoleId' => $listsInFoldersSearchRow['folderRoleId'], 'created' => $listsInFoldersSearchRow['folderCreated'], 'modified' => $listsInFoldersSearchRow['folderModified']);
					}
				$folders[$listsInFoldersSearchRow['folderId']]['lists'][$listsInFoldersSearchRow['listId']] = array('listName' => $listsInFoldersSearchRow['listName'], 'listRoleId' => $listsInFoldersSearchRow['listRoleId'], 'frId' => $listsInFoldersSearchRow['frId'], 'created' => $listsInFoldersSearchRow['listCreated'], 'modified' => $listsInFoldersSearchRow['listModified']);
				}
			}
			//$debug->printArray($lists,'$lists');
			$debug->printArray($folders,'$folders');
			/*An nest of arrays:
			Array $folders:
array (
  2 => 
  array (
	'folderName' => 'Junk Food',
	'folderRoleId' => '3',
	'modified' => '2012-02-25 11:26:45',
	'lists' => 
	array (
	  9 => 
	  array (
		'listName' => '\'63 Comet',
		'listRoleId' => '3',
		'modified' => '2012-02-17 13:51:17',
	  ),
	  12 => 
	  array (
		'listName' => 'The Awesome List2',
		'listRoleId' => '2',
		'modified' => '2012-02-25 07:49:32',
	  ),
	),
  ),
  14 => 
  array (
	'folderName' => 'My Super Awesome Folder',
	'folderRoleId' => '1',
	'modified' => NULL,
	'lists' => 
	array (
	  '' => 
	  array (
		'listName' => NULL,
		'listRoleId' => NULL,
		'modified' => NULL,
	  ),
	),
  ),
  1 => 
  array (
	'folderName' => 'Scizors',
	'folderRoleId' => '1',
	'modified' => NULL,
	'lists' => 
	array (
	  1 => 
	  array (
		'listName' => 'River of Sorrow',
		'listRoleId' => '2',
		'modified' => '2011-04-22 07:33:44',
	  ),
	  6 => 
	  array (
		'listName' => 'Hit List',
		'listRoleId' => '2',
		'modified' => '2011-10-04 06:20:19',
	  ),
	  4 => 
	  array (
		'listName' => 'I AM',
		'listRoleId' => '2',
		'modified' => '2010-06-18 23:51:11',
	  ),
	  2 => 
	  array (
		'listName' => 'Norman',
		'listRoleId' => '2',
		'modified' => '2009-08-03 23:12:36',
	  ),
	  8 => 
	  array (
		'listName' => 'Thunderballs S1E1',
		'listRoleId' => '2',
		'modified' => '2011-06-28 20:44:14',
	  ),
	  5 => 
	  array (
		'listName' => 'Locked Down',
		'listRoleId' => '2',
		'modified' => '2012-01-25 09:48:27',
	  ),
	  3 => 
	  array (
		'listName' => 'Wrong Turn At Tahoe',
		'listRoleId' => '2',
		'modified' => '2009-07-27 05:31:23',
	  ),
	),
  ),
)
		*/
			//Build the lists not in folders.
			foreach($lists as $listId => $listValues){
				if($class == 'rowWhite'){
					$class = 'rowAlt';
				}else{
					$class = 'rowWhite';
				}
				$output .= '	<div class="overflowauto relative ' . $class . '">
		<div class="row textLeft" style="min-width:100px;width:30%">
			<div class="row textLeft" style="width:40px;padding:0 0 0 5px;">
				<img alt="" src="' . LINKIMAGES . '/list.png" style="height:15px;width:15px">
			</div>
			<span id="listName' . $listId . '">' . $listValues['listName'] . '</span>
		</div>
		<div class="row" style="min-width:3em;width:8%">' . $frameratesArray[$listValues['frId']] . '</div>
		<div class="row textSmall" style="min-width:5em;width:15%">
			';
				if(empty($listValues['created'])){
					$output .= 'n/a';
				}else{
					$output .= Adrlist_Time::utcToLocal($listValues['created']);
				}
				$output .= '		</div>
		<div class="row textSmall" style="min-width:5em;width:15%">
			';
				if(empty($listValues['modified'])){
					$output .= 'n/a';
				}else{
					$output .= Adrlist_Time::utcToLocal($listValues['modified']);
				}
				$output .= '		</div>
			<div class="bold hand row" id="rowActionsButtonList' . $listId . '" uniqueId="List' . $listId . '" style="min-width:5em;width:24%" onClick="">
				<div class="textRight">
					List Actions <img alt="" class="middle" src="' . LINKIMAGES . '/greenArrowRight.png" style="height:12px;width:12px">
				</div>
			</div>
';		
				//The rowActions for lists not in folders.
				$output .= '<div class="hide right" id="rowActionsHolderList' . $listId . '" listId="' . $listId . '" style="background-color:inherit">
			<ul class="textLeft" style="list-style:none;margin:5px 0px">
				<li class="actions" id="editList' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/edit.png" style="height:20px;width:20px;"><span class="linkPadding">Edit</span>
				</li>
				<li class="actions" id="listPropertiesStep1' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/tools.png" style="height:20px;width:20px;"><span class="linkPadding">Properties</span>
				</li>
				<li class="actions" id="buildListUsers' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/charSearch.png" style="height:20px;width:20px;"><span class="linkPadding">Manage Users</span>
				</li>
				<li class="actions" id="transferList' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/transfer.png" style="height:20px;width:20px;"><span class="linkPadding">Transfer</span>
				</li>
				<li class="actions" id="lockListStep1' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/lock.png"  style="height:20px;width:20px;"><span class="linkPadding">Lock</span>
				</li>
				<li class="actions" id="deleteListStep1' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/trash.png"  style="height:20px;width:20px;"><span class="linkPadding">Delete</span>
				</li>
			</ul>
		</div>
	</div>
';//End rowActions.
			}//End lists not in folders.
			//Build folders.
			foreach($folders as $folderId => $folderValues){
				$output .= '	<div class="folderRow" id="rowActionsFolder' . $folderId . '">
		<div class="hand row textLeft" id="toggleFolderLists' . $folderId . '" folderid="' . $folderId . '" onClick="" style="min-width:100px;width:30%">
			<div class="row textLeft" style="width:40px;padding:0 0 0 5px;">
				<img alt="" src="' . LINKIMAGES . '/folder.png" style="height:20px;width:20px"><span><img id="folderListsImg' . $folderId . '" thing="Lists" src="' . LINKIMAGES . '/bulletArrow';
				$output .= $search ? 'Down' : 'Right';
				$output .= '.png" style="height:20px;width:20px;"></span>
			</div>
			<span id="folderName' . $folderId . '">' . $folderValues['folderName'] . '</span>
		</div>
		<div class="row" style="min-width:3em;width:8%"></div>
		<div class="row textSmall" style="min-width:5em;width:15%">
			';
				if(empty($folderValues['created'])){
					$output .= 'n/a';
				}else{
					$output .= Adrlist_Time::utcToLocal($folderValues['created']);
				}
				$output .= '		</div>
		<div class="row textSmall" style="min-width:5em;width:15%">
			';
				if(empty($folderValues['modified'])){
					$output .= 'n/a';
				}else{
					$output .= Adrlist_Time::utcToLocal($folderValues['modified']);
				}
				$output .= '		</div>
			<div class="bold hand row" id="rowActionsButtonFolder' . $folderId . '" onClick="" uniqueId="Folder' . $folderId . '" style="min-width:5em;width:24%">
				<div class="textRight">
					Folder Actions <img alt="" class="middle" id="folderActionsImg' . $folderId .'" src="' . LINKIMAGES . '/greenArrowRight.png" style="height:12px;width:12px"">
				</div>
			</div>
';		
				//The folder rowActions.
				$output .= '			<div class="hide right" id="rowActionsHolderFolder' . $folderId . '" style="background-color:inherit">
				<ul class="textLeft" style="list-style:none;margin:5px 0px">
					<li class="actions" id="renameFolderStep1' . $folderId . '" folderid="' . $folderId . '" folderName="' . $folderValues['folderName'] . '" onClick="">
						<img alt="" class="middle" src="' . LINKIMAGES . '/pencil.png" style="height:1.6em;width:1.6em"><span class="linkPadding">Rename</span>
					</li>
					<li class="actions" id="buildFolderUsers' . $folderId . '" folderid="' . $folderId . '" onClick="">
						<img alt="" class="middle" src="' . LINKIMAGES . '/charSearch.png" style="height:1.6em;width:1.6em"><span class="linkPadding">Manage Users</span>
					</li>
					<li class="actions" id="deleteFolder' . $folderId . '" folderid="' . $folderId . '" folderName="' . $folderValues['folderName'] . '" onClick="">
						<img alt="" class="middle" src="' . LINKIMAGES . '/trash.png" style="height:1.6em;width:1.6em"><span class="linkPadding">Delete</span>
					</li>
				</ul>
			</div>
		<div';
				$output .= $search ? '' : ' class="hide"';
				$output .= ' id="folderListsHolder' . $folderId . '">
';
				//The lists in the folder.
				$listCount = 0;
				if(empty($folderValues['lists'])){
					$output .= '<div class="break textCenter">There are no lists in this folder.</div>';
				}
				if(array_key_exists('lists',$folderValues)){
					foreach($folderValues['lists'] as $listId => $listValues){
						if($class == 'rowWhite'){
							$class = 'rowAlt';
						}else{
							$class = 'rowWhite';
						}
						$output .= '			<div class=" ' . $class . '" id="rowActionsList' . $listId . '">
				<div class="row textLeft" style="min-height:21px;min-width:170px;width:30%">
					<div class="row textLeft" style="width:40px;padding:0 0 0 5px;">
						<img alt="" src="' . LINKIMAGES . '/list.png" style="height:15px;left:5px;width:15px">
					</div>
					<span id="listName' . $listId . '">' . $listValues['listName'] . '</span>
				</div>
				<div class="row" style="min-width:3em;width:8%">' . $frameratesArray[$listValues['frId']] . '</div>
				<div class="row textSmall" style="min-width:5em;width:15%">
			';
						if(empty($listValues['created'])){
							$output .= 'n/a';
						}else{
							$output .= Adrlist_Time::utcToLocal($listValues['created']);
						}
						$output .= '				</div>
				<div class="row textSmall" style="min-width:5em;width:15%">
			';
						if(empty($listValues['modified'])){
							$output .= 'n/a';
						}else{
							$output .= Adrlist_Time::utcToLocal($listValues['modified']);
						}
						$output .= '				</div>
				<div class="bold hand row" id="rowActionsButtonList' . $listId . '" uniqueId="List' . $listId . '" style="min-width:5em;width:24%">
					<div class="textRight">
						List Actions <img alt="" class="middle" src="' . LINKIMAGES . '/greenArrowRight.png" style="height:12px;width:12px">
					</div>
				</div>
';		
						//The rowActions for lists in folders.
						$output .= '<div class="hide right" id="rowActionsHolderList' . $listId . '" style="background-color:inherit">
			<ul class="textLeft" style="list-style:none;margin:5px 0px">
				<li class="actions" id="editList' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/edit.png" style="height:1.6em;width:1.6em;"><span class="linkPadding">Edit</span>
				</li>
				<li class="actions" id="listPropertiesStep1' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/tools.png" style="height:20px;width:20px;"><span class="linkPadding">Properties</span>
				</li>
				<li class="actions" id="buildListUsers' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/charSearch.png" style="height:1.6em;width:1.6em;"><span class="linkPadding">Manage Users</span>
				</li>
				<li class="actions" id="transferListStep1' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/transfer.png" style="height:1.6em;width:1.6em;"><span class="linkPadding">Transfer</span>
				</li>
				<li class="actions" id="lockListStep1' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/lock.png"  style="height:1.6em;width:1.6em;"><span class="linkPadding">Lock</span>
				</li>
				<li class="actions" id="deleteListStep1' . $listId . '" listId="' . $listId . '" onClick="">
					<img alt="" class="middle" src="' . LINKIMAGES . '/trash.png"  style="height:1.6em;width:1.6em;"><span class="linkPadding">Delete</span>
				</li>
			</ul>
		</div>
	</div>
';//End lists in folders.
					}
				}
				$output .= '		</div>
	</div>
';//End folders.
			}
			if(!$foundLists && !$foundFolders){
				if($search){
					$output .= '<div class="red textCenter" style="padding:5px 0px 10px 0px;">There are no matches for "' . $_POST['searchVal'] . '".</div>';
				}else{
					$output .= '<div class="textCenter" style="padding:5px 0px 10px 0px;">You are not associated with any folders or lists.</div>';
				}
				pdoError(__LINE__,$listStmt, $listParams, true);
				pdoError(__LINE__,$folderStmt, $folderParams, true);
				if(isset($listsInFoldersStmt)){
					pdoError(__LINE__,$listsInFoldersStmt, $listsInFoldersParams, true);
				}
				if(!empty($listsInFoldersSearchStmt)){
					pdoError(__LINE__,$listsInFoldersSearchStmt, $listsInFoldersSearchParams, true);
				}
			}
			$output .= '</div>
';
			$success = true;
			$returnThis['buildUMLists'] = $output;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildUMLists'){
		returnData();
	}else{
		return $output;
	}
}

function buildListLetters(){
	return '	<div class="break" style="line-height:2em">
		Folder Name: <span class="bold link" id="buildListsLettersA-H" letters="A-H" style="padding:0px 20px 0px 20px; letter-spacing:.2em">A&ndash;H</span> <span class="bold link" id="buildListsLettersI-Q" letters="I-Q" style="padding:0px 20px 0px 20px; letter-spacing:.2em"">I&ndash;Q</span> <span class="bold link" id="buildListsLettersR-Z" letters="R-Z" style="padding:0px 20px 0px 20px; letter-spacing:.2em"">R&ndash;Z</span>
	</div>';
}

function buildFolderInfo(){//Deprecated
	global $debug, $message, $success, $Dbc;
	$output = '';
	$folderQueryStart = "SELECT
	folderId AS 'folderId',
	folderName AS 'folderName',
	DATE_FORMAT(expires, '%M %D, %Y') AS 'expires'
FROM
	folders";
	$search = false;
	if(empty($_POST['searchVal']) && empty($_POST['letters'])){//Search for specific user(s).
		$success = true;//Will reset buildInfoHolder to show no users.
	}else{
		if(empty($_POST['searchVal']) && !empty($_POST['letters'])){
			$folderQuery = $folderQueryStart . "
WHERE
	folderName RLIKE ?
ORDER BY
	folderName";
			$folderStmt = $Dbc->prepare($folderQuery);
			$folderParams = array('^[' . trim($_POST['letters']) . ']');
		}elseif(!empty($_POST['searchVal'])){//Search for specific user(s).
			$search = true;
			$folderQuery = $folderQueryStart . "
WHERE
	folders.folderName LIKE ?
ORDER BY
	folders.folderName";
			$folderStmt = $Dbc->prepare($folderQuery);
			$folderParams = array('%' . trim($_POST['searchVal']) . '%');
		}
		$folderStmt->execute($folderParams);
		$foundFolders = false;
		$class = 'rowAlt';
		$content = '';
		while($row = $folderStmt->fetch(PDO::FETCH_ASSOC)){
			$foundFolders = true;
			$folderId = $row['folderId'];
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$content .= '
		<div class="break ' . $class . '"">
			<div class="row" style="width:120px">' . $row['folderName'] . '</div>
			<div class="row" style="width:120px">' . $row['expires'] . '</div>
			<div class="row" style="width:330px"><span class="link" id="viewFolderInfo' . $folderId . '">Edit Info</span> <span class="link" id="viewFolderRole' . $row['folderId'] . '">View Role</span> <span class="link" id="deleteFolderStep1' . $folderId . '">Delete Folder</span></div>
			<div class="red row" style="width:70px"><span class="red row" id="message' . $folderId . '"></span>&nbsp;</div>
			<div class="right textXsmall" style="padding:0px 5px 0px 0px;">Id: ' . $folderId . '</div>
			<div class="break" id="folderInfoHolder' . $folderId . '" style="display:none; line-height:2em; padding:0px 0px 0px 5px">
				First Name: <input id="editFolderName' . $folderId . '" size="12" type="text" value="' . $row['folderName'] . '"> <span class="red" id="folderNameResponse' . $folderId . '" style="padding:0px 0px 0px 5px"></span><br>
				Expires: <input id="editFolderExpires' . $folderId . '" size="20" type="text" value="' . $row['expires'] . '"><span class="red" id="expiresResponse' . $folderId . '" style="padding:0px 0px 0px 5px"></span><br>
				<span class="link" id="updateFolderInfo' . $folderId . '">Update</span>
			</div>
			<div class="break" id="viewFolderRoleHolder' . $folderId . '" style="display:none"></div>
			</div>';
		}
		if(!$foundFolders){
			if($search){
				$output .= '<div class="red textCenter" style="padding:5px 0px 10px 0px;">There are no matches for "' . $_POST['searchVal'] . '".</div>';
				pdoError(__LINE__,$folderStmt, $folderParams, true);
			}else{
				$output .= '<div class="textCenter" style="padding:5px 0px 10px 0px;">You are not associated with any folders or lists.</div>';
			}
		}else{
			$output .= '	<div class="overflowauto" style="height:300px;>
		<div class="break relative">
			<div class="rowTitle" style="width:120px">Folder Name</div>
			<div class="rowTitle" style="width:120px">Expire</div>
			<div class="rowTitle" style="width:350px">Actions</div>
		</div>
' . $content . '
	</div>
';
		}
	}
	$returnThis['returnCode'] = $output;
	if(MODE){
		returnData();
	}else{
		return $output;
	}
}

function buildUsers(){
	global $debug, $message, $success, $Dbc, $returnThis;
		try{
			$output = '';
			//Get the user count.
			$userCountStmt = $Dbc->query("SELECT
	count(userId) AS 'count'
FROM
	users");
			$row = $userCountStmt->fetch(PDO::FETCH_ASSOC);
			$itemCount = $row['count'];
			//Get the pagination info.
			$pagination = new Adrlist_Pagination();
			$offsetLimit = $pagination->getOffsetLimit($_SESSION['userId'],$_SERVER['SCRIPT_NAME'],'buildUsers');
			if(isset($_POST['offset'])){
				$offset = $_POST['offset'];
				$pagination->setOffset($_SESSION['userId'],$_SERVER['SCRIPT_NAME'],'buildUsers',$offset);
			}else{
				$offset = $offsetLimit[0];
			}
			$offset = $offset > $itemCount ? 0 : $offset;//When changing list viewing options the offset may be larger than the count.
			if(isset($_POST['limit'])){
				$limit = $_POST['limit'];
				$pagination->setLimit($_SESSION['userId'],$_SERVER['SCRIPT_NAME'],'buildUsers',$limit);
				$offset = 0;//We must reset the offset when changing the limit.
			}else{
				$limit = $offsetLimit[1];
			}
			//Get the users.
			$usersStmt = $Dbc->prepare("SELECT
	userId AS 'userId',
	primaryEmail AS 'primaryEmail',
	secondaryEmail AS 'secondaryEmail',
	firstName AS 'firstName',
	lastName AS 'lastName',
	dateAdded AS 'dateAdded'
FROM
	users
LIMIT " . $offset . ', ' . $limit);
			$usersStmt->execute();
			$userRows = array();
			while($row = $usersStmt->fetch(PDO::FETCH_ASSOC)){
				$userRows[] = array(
					$row['userId'],
					'<span class="blue bold">P:</span> ' . $row['primaryEmail'] . '<br><span class="blue bold">S:</span> ' . $row['secondaryEmail'],
					'<span class="blue bold">F:</span> ' . $row['firstName'] . '<br><span class="blue bold">L:</span> ' . $row['lastName'],
					$row['dateAdded']
				);
			}
			$pagination->setParameters($itemCount,$offset,$limit,'buildUsers');
			$titleArray = array('userId','Email','Name','Date Added');
			$cssWidths = array('3em','18em','10em','8em');
			$buildRows = new Adrlist_BuildRows($titleArray,$userRows,$cssWidths);
			$pagination = $pagination->output();
			$output .= $pagination['paginationTop'] . $buildRows->outputTitleRow() . $buildRows->outputRows() . $pagination['paginationBottom'];
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			error(__LINE__,'',$debug->printArray($e) . '<pre class="red">' . $e . '</pre>');
		}
	if(MODE == 'buildUsers'){
		$success = true;
		$returnThis['output'] = $output;
		$returnThis['container'] = 'buildUsersHolder';
		returnData();
	}else{
		return $output;
	}
}

function buildUserInfo(){
	global $debug, $message, $success, $Dbc;
	$output = '	<div class="break" style="line-height:2em">
		Last Name: <span class="bold link" id="userLettersA-H" style="padding:0px 20px 0px 20px; letter-spacing:.2em">A&ndash;H</span> <span class="bold link" id="userLettersI-Q" style="padding:0px 20px 0px 20px; letter-spacing:.2em"">I&ndash;Q</span> <span class="bold link" id="userLettersR-Z" style="padding:0px 20px 0px 20px; letter-spacing:.2em"">R&ndash;Z</span>
	</div>';
	try{
		$userInfoStmt = "SELECT
	users.userId AS 'userId',
	users.firstName AS 'firstName',
	users.lastName AS 'lastName',
	users.primaryEmail AS 'primaryEmail',
	DATE_FORMAT(users.joinDate, '%M %D, %Y') AS 'joinDate'
FROM
	users";
		$userInfoStmt .= "ORDER BY
	users.lastName";
		if(empty($_POST['searchVal']) && !empty($_POST['letters'])){
			$letters = "'^[" . trim($_POST['letters'] . "]'");
			$userInfoStmt .= "
WHERE
	users.lastName RLIKE ?";
			$userInfoStmt = $Dbc->prepare($userInfoStmt);
			$userInfoParams = array($letters);
			$userInfoStmt->execute($userInfoParams);
		}elseif(empty($_POST['searchVal']) && empty($_POST['letters'])){
			$search = false;
			$userInfoStmt = $Dbc->prepare($userInfoStmt);
			$userInfoParams = array();
			$userInfoStmt->execute($userInfoParams);
		}else{
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchval: ' . $searchVal);
			$userInfoStmt .= "
WHERE
	(users.firstName LIKE ? || users.lastName LIKE ?' || users.primaryEmail LIKE ?)
";
			$userInfoParams = array($searchVal,$searchVal,$searchVal);
			$userInfoStmt->execute($userInfoParams);
			pdoError(__LINE__,$userInfoStmt, $userInfoParams);
		}
			$output .= '	<div class="overflowauto" style="height:300px;">
		<div class="break relative">
			<div class="rowTitle" style="width:120px">Name</div>
			<div class="rowTitle" style="width:120px">Email</div>
			<div class="rowTitle" style="width:120px">Join Date</div>
			<div class="rowTitle" style="width:350px">Actions</div>
		</div>
';
			$class = 'rowAlt';
		while($row = $listStmt->fetch(PDO::FETCH_ASSOC)){
			$userId = $row['userId'];
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$output .= '
		<div class="break ' . $class . '"">
			<div class="right textXsmall">Id: ' . $userId . '</div>
			<div class="row" style="width:120px">' . $row['firstName'] . ' ' . $row['lastName'] . '</div>
			<div class="row" style="width:120px"><a href="mailto:' . $row['primaryEmail'] . '">' . breakEmail($row['primaryEmail'], 20) . '</a></div>
			<div class="row textSmall" style="width:120px">' . $row['joinDate'] . '</div>
			<div class="row" style="width:330px"><span class="link" id="viewUserInfo' . $userId . '">Edit Info</span> <span class="link" id="viewUserRole' . $row['userId'] . '">View Role</span> <span class="link" id="deleteUserStep1' . $userId . '">Delete User</span></div>
			<div class="red row" style="width:70px"><span class="red row" id="message' . $userId . '"></span>&nbsp;</div>
			<div class="break" id="userInfoHolder' . $userId . '" style="display:none; line-height:2em; padding:0px 0px 0px 5px">
				First Name: <input id="editUserFirstName' . $userId . '" size="12" type="text" value="' . $row['firstName'] . '"> <span class="red" id="firstNameResponse' . $userId . '" style="padding:0px 0px 0px 5px"></span><br>
				Last Name: <input id="editUserLastName' . $userId . '" size="12" type="text" value="' . $row['lastName'] . '"><span class="red" id="lastNameResponse' . $userId . '" style="padding:0px 0px 0px 5px"></span><br>
				Email: <input id="editUserEmail' . $userId . '" size="20" type="text" value="' . $row['primaryEmail'] . '"><span class="red" id="emailResponse' . $userId . '" style="padding:0px 0px 0px 5px"></span><br>
				<span class="link" id="updateUserInfo' . $userId . '">Update</span>
			</div>
			<div class="break" id="viewUserRoleHolder' . $userId . '" style="display:none"></div>
		</div>';
			$foundRows = true;
		}
		$output .= empty($foundRows) ? '<div class="break textCenter">No users found.</div>' : '';
		$output .= '	</div>
';
		$success = true;
		$returnThis['buildUserInfo'] = $output;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildUserInfo'){
		returnData();
	}else{
		return $output;
	}
}

function deleteUser(){
	global $debug, $message, $success;
	$output = '';
	if(isset($_POST['userId'])){
		$userId = intval($_POST['userId']);
		$deleteUserQuery = "DELETE FROM
	users
WHERE
	users.userId = '$userId';";
		if($result = mysql_query($deleteUserQuery)){
			if(mysql_affected_rows() == 0){
				error(__LINE__);
				pdoError(__LINE__, $deleteUserQuery, '$deleteUserQuery', 1);
			}else{
				$success = true;
				$message .= 'Deleted';
				$returnThis['returnCode'] = userInfo();
			}
		}else{
			error(__LINE__);
			pdoError(__LINE__, $deleteUserQuery, '$deleteUserQuery');
		}
	}
	returnData();
}

function updateFolderRole(){
	global $debug, $message, $success;
	$output = '';
	if(isset($_POST['userId']) && isset($_POST['folderId']) && isset($_POST['oldRole']) && isset($_POST['newRole'])){
		$userId = intval($_POST['userId']);
		$folderId = intval($_POST['folderId']);
		$oldRole = intval($_POST['oldRole']);
		$newRole = intval($_POST['newRole']);
		$updateFolderRoleQuery = "UPDATE
	userFolderSettings
SET
	folderRoleId = '$newRole'
WHERE
	userId = '$userId' AND
	folderId = '$folderId' AND
	folderRoleId = '$oldRole'
LIMIT 1";
		if($result = mysql_query($updateFolderRoleQuery)){
			//We don't need any rows to have success.
			if(mysql_affected_rows() == 0){
				pdoError(__LINE__, $updateFolderRoleQuery, '$updateFolderRoleQuery', 1);
			}
			$success = true;
			$message .= 'Updated';
		}else{
			error(__LINE__);
			pdoError(__LINE__, $updateFolderRoleQuery, '$updateFolderRoleQuery');
		}
	}elseif(!isset($_POST['userId'])){
		error(__LINE__);
		$debug->add('$_POST[\'userId\'] is empty.');
	}elseif(!isset($_POST['folderId'])){
		error(__LINE__);
		$debug->add('$_POST[\'listId\'] is empty.');
	}elseif(!isset($_POST['oldRole'])){
		error(__LINE__);
		$debug->add('$_POST[\'oldRole\'] is empty.');
	}elseif(!isset($_POST['newRole'])){
		error(__LINE__);
		$debug->add('$_POST[\'newRole\'] is empty.');
	}else{
		error(__LINE__);
		$debug->add('Something else is wrong.');
	}
	returnData();
}

function updateListRole(){
	global $debug, $message, $success;
	$output = '';
	if(isset($_POST['userId']) && isset($_POST['listId']) && isset($_POST['oldRoleId']) && isset($_POST['newRoleId'])){
		$userId = intval($_POST['userId']);
		$listId = intval($_POST['listId']);
		$oldRoleId = intval($_POST['oldRoleId']);
		$newRoleId = intval($_POST['newRoleId']);
		$updateListRoleQuery = "UPDATE
	userListSettings
SET
	listRoleId = '$newRoleId'
WHERE
	userId = '$userId' AND
	listId = '$listId' AND
	listRoleId = '$oldRoleId'
LIMIT 1";
		if($result = mysql_query($updateListRoleQuery)){
			//We don't need any rows to have success.
			$success = true;
			if(mysql_affected_rows() == 0){
				pdoError(__LINE__, $updateListRoleQuery, '$updateListRoleQuery', 1);
			}
			$message .= 'Updated';
		}else{
			error(__LINE__);
			pdoError(__LINE__, $updateListRoleQuery, '$updateListRoleQuery');
		}
	}elseif(!isset($_POST['userId'])){
		error(__LINE__);
		$debug->add('userId is empty.');
	}elseif(!isset($_POST['listId'])){
		error(__LINE__);
		$debug->add('listId is empty.');
	}elseif(!isset($_POST['roleId'])){
		error(__LINE__);
		$debug->add('roleId is empty.');
	}
	returnData();
}

function updateUserInfo(){
	global $debug, $message, $success;
	$output = '';
	if(isset($_POST['userId']) && isset($_POST['firstName']) && strlen($_POST['firstName']) <= 25 && isset($_POST['lastName']) && strlen($_POST['lastName']) <= 25 && isset($_POST['email'])){
		$userId = intval($_POST['userId']);
		$firstName = trim($_POST['firstName']);
		$lastName = trim($_POST['lastName']);
		$email = trim($_POST['email']);
		if(emailValidate($email)){
			//Check to see if the email address is being used by another user.
			$emailCheckQuery = "SELECT
	CONCAT_WS(' ', users.firstName, users.lastName) AS 'name',
	users.primaryEmail AS 'primaryEmail'
FROM
	users
WHERE
	users.primaryEmail = '$email' AND
	users.userId <> '$userId'";
			if($result = mysql_query($emailCheckQuery)){
				if(mysql_affected_rows() == 0){
					pdoError(__LINE__, $emailCheckQuery, '$emailCheckQuery', 1);
					$updateUserInfoQuery = "UPDATE
	users
SET
	firstName = '" . $firstName . "',
	lastName = '" . $lastName . "',
	primaryEmail = '" . $email . "'
WHERE
	userId = '$userId'";
					if(mysql_query($updateUserInfoQuery)){
						if(mysql_affected_rows() == 0){
							pdoError(__LINE__, $updateUserInfoQuery, '$updateUserInfoQuery', 1);
						}
						$success = true;
						$message .= 'Updated';
						$returnThis['returnCode'] = buildUserInfo();
					}else{
						error(__LINE__);
						pdoError(__LINE__, $updateUserInfoQuery, '$updateUserInfoQuery');
					}
				}else{
					while($row = mysql_fetch_assoc($result)){
						$message .= "That email address is already being used by " . $row['name'] . " . Please enter a another. ";
					}
				}
			}else{
				error(__LINE__);
				pdoError(__LINE__, $emailCheckQuery, '$emailCheckQuery');
			}
		}else{
			$message .= 'Please enter a valid email address. ';
		}
	}elseif(!isset($_POST['userId'])){
		$message .= 'userId is empty. ';
	}elseif(!isset($_POST['firstName'])){
		$message .= 'Please enter a first name. ';
	}elseif(strlen($_POST['firstName']) > 25){
		$message .= 'The first name must be 25 characters or less. ';
	}elseif(!isset($_POST['lastName'])){
		$message .= 'Please enter a last name. ';
	}elseif(strlen($_POST['lastName']) > 25){
		$message .= 'The last name must be 25 characters or less. ';
	}elseif(!isset($_POST['email'])){
		$message .= 'Please enter an email address. ';
	}else{
		error(__LINE__);
		$debug->add('Something else is wrong.');
	}
	returnData();
}

function updateSiteRole(){
	global $debug, $message, $success, $Dbc;
	$output = '';
	if(isset($_POST['userId']) && isset($_POST['newRole'])){
		$_POST['userId'] = intval($_POST['userId']);
		$_POST['newRole'] = intval($_POST['newRole']);
		try{
			$updateUserSiteRoleStmt = $Dbc->prepare("UPDATE
	userSiteSettings
SET
	siteRoleId = ?
WHERE
	userId = ?");
			$updateUserSiteRoleParams = array($newRole,$userId);
			$updateUserSiteRoleStmt->execute($updateUserSiteRoleParams);
			$foundRows = false;
			if($row = $updateUserSiteRoleStmt->fetch(PDO::FETCH_ASSOC)){
				$foundRows = true;
			}
			if(!$foundRows){
				pdoError(__LINE__, $updateUserSiteRoleStmt, $updateUserSiteRoleParams, true);
			}
			//We don't need affected rows for success here.
			$success = true;
			$message .= 'Updated';
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}else{
		error(__LINE__);
		if(empty($_POST['userId'])){
			$message .= '$_POST[\'userId\'] is empty on line ' . __LINE__ . '.';
		}elseif(empty($_POST['newRole'])){
			$message .= '$_POST[\'newRole\'] is empty on line ' . __LINE__ . '.';
		}else{
			$message .= 'Something else is wrong.';
		}
	}
	if(MODE == 'updateSiteRole'){
		returnData();
	}else{
		return $output;
	}
}

function viewUserRole(){
	global $debug, $message, $success, $Dbc;
	$output = '';
	if(isset($_POST['userId'])){
		$userId = intval($_POST['userId']);
		try{
			$viewUserSiteRoleStmt = $Dbc->prepare("SELECT
	siteRoleId AS 'siteRoleId'
FROM
	userSiteSettings
WHERE
	userId = ?");
			$viewUserSiteRoleParams = array($userId);
			$viewUserSiteRoleStmt->execute($viewUserSiteRoleParams);
			$foundRows = false;
			$userSiteRoleRow = NULL;
			$roles = array('0' => 'Blocked', '1' => 'Allow', '5' => 'Site Admin');//('mysql role' => 'display role')
			while($row = $viewUserSiteRoleStmt->fetch(PDO::FETCH_ASSOC)){
				$foundRows = true;
				foreach($roles as $key => $value){//Build all the radio buttons with a unique name containing the userId.
					$userSiteRoleRow .= '<input type="radio" name="role' . $userId . '" value="' . $key . '"';
					if($key == $row['siteRoleId']){
						$userSiteRoleRow .= ' checked';
					}
					$userSiteRoleRow .= '>' . $value;
				}
				$userSiteRoleRow .= ' <span class="link" id="updateSiteRole' . $userId . '">Update</span>';
			}
			if($foundRows){
				$userSiteRole = '	<div class="break textCenter">
		<div class="rowTitle" style="width:375px">Site Role</div>
		<div class="break" style="line-height:2em;">' . $userSiteRoleRow . '
		</div>
	</div>';
			}else{
				$userSiteRole = NULL;
				pdoError(__LINE__, $viewUserSiteRoleStmt, $viewUserSiteRoleParams, true);
			}
			$viewUserFolderRoleStmt = $Dbc->prepare("SELECT
	userFolderSettings.folderId AS 'folderId',
	userFolderSettings.folderRoleId AS 'folderRoleId',
	folders.folderName AS 'folderName'
FROM
	userFolderSettings
JOIN
	folders ON folders.folderId = userFolderSettings.folderId
WHERE
	userFolderSettings.userId = ?");
			$viewUserFolderRoleParams = array($userId);
			$viewUserFolderRoleStmt->execute($viewUserFolderRoleParams);
			$foundRows = false;
			$userFolderRoleRows = NULL;
			$class = 'rowAlt';
			while($row = $viewUserFolderRoleStmt->fetch(PDO::FETCH_ASSOC)){
				$foundRows = true;
				if($class == 'rowWhite'){
					$class = 'rowAlt';
				}else{
					$class = 'rowWhite';
				}
				$userFolderRoleRows .= '		<div class="break ' . $class . '" style="width:375">
			<div class="row ' . $class . '" style="width:175px">' . $row['folderName'] . '</div>
			<div class="row" style="width:120px">' . buildRoles('folderRoleUser' . $userId . 'folderId' . $row['folderId'] . 'folderRoleId' . $row['folderRoleId'], $row['folderRoleId'], 'folderRoleId') . '</div>
			<div class="link row" id="updateFolderRoleUser' . $userId . 'folderId' . $row['folderId'] . '" style="width:55px">Update</div>
		</div>
';
			}
			if($foundRows){
				$userFolderRole = '	<div class="break" style="padding:10px 0px 0px 0px">
		<div class="rowTitle" style="width:175px">Folder Name</div>
		<div class="rowTitle" style="width:175px">Folder Role</div>
		<div class="break left" style="line-height:2em;">' . $userFolderRoleRows . '
		</div>
	</div>';
			}else{
				$userFolderRole = NULL;
				pdoError(__LINE__, $viewUserFolderRoleStmt, $viewUserFolderRoleParams, true);
			}
			$viewUserListRoleStmt = $Dbc->prepare("SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	userListSettings.listRoleId AS 'listRoleId',
	folders.folderName AS 'folderName'
FROM
	lists
JOIN
	folders ON folders.folderId = lists.folderId
JOIN
	userListSettings ON userListSettings.listId = lists.listId AND
	userListSettings.userId = ?
ORDER BY
	folders.folderName, lists.listName");
			$viewUserListRoleParams = array($userId);
			$viewUserListRoleStmt->execute($viewUserListRoleParams);
			$foundRows = false;
			$userListRoleRows = NULL;
			$class = 'rowAlt';
			while($row = $viewUserListRoleStmt->fetch(PDO::FETCH_ASSOC)){
					if($class == 'rowWhite'){
						$class = 'rowAlt';
					}else{
						$class = 'rowWhite';
					}
					$userListRoleRows .= '		<div class="break right ' . $class . '" style="width:400px">
			<div class="row" style="width:120px">' . $row['listName'] . '</div>
			<div class="row" style="width:120px">' . $row['folderName'] . '</div>
			<div class="row" style="width:160px; line-height:2em;">
				' . buildRoles('user' . $userId . 'List' . $row['listId'] . 'Role' . $row['listRoleId'] , $row['listRoleId']) . '&nbsp;<span class="link" id="updateListRole' . $userId . '">Update</span>
			</div>
		</div>
';
			}
			if($foundRows){
				$userListRole = '	<div class="break">
		<div class="rowTitle" style="width:120px">List Name</div>
		<div class="rowTitle" style="width:120px">Folder</div>
		<div class="rowTitle" style="width:160px">List Role</div>
' . $userListRoleRows . '
	</div>';
			}else{
				$userListRole .= 'The user has no role for this list.';
				pdoError(__LINE__, $viewUserListRoleStmt, $viewUserListRoleParams, true);
			}

			$output .= '		<div class="left" style="width:375px">' . $userSiteRole . $userFolderRole . '</div>
		<div class="right" style="width:400px">' . $userListRole . '</div>
';
			$success = true;
			$returnThis['returnCode'] = $output;
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}else{
		error(__LINE__);
		if(empty($_POST['userId'])){
			$message .= '$_POST[\'userId\'] is empty on line ' . __LINE__ . '.';
		}elseif(empty($_POST['newRole'])){
			$message .= '$_POST[\'newRole\'] is empty on line ' . __LINE__ . '.';
		}else{
			$message .= 'Something else is wrong.';
		}
	}
	if(MODE == 'updateSiteRole'){
		returnData();
	}else{
		return $output;
	}
}