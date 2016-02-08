<?php /*
This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
*/
$fileInfo = array('fileName' => 'includes/myAccountMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'createFolderStep1'){
	createFolderStep1();
}elseif(MODE == 'createFolderStep2'){
	createFolderStep2();
}elseif(MODE == 'createListStep1'){
	createListStep1();
}elseif(MODE == 'createListStep2'){
	createListStep2();
}elseif(MODE == 'buildAdrLists'){
	buildAdrLists();
}elseif(MODE == 'buildFolderUsers'){
	buildFolderUsers();
}elseif(MODE == 'buildListUsers'){
	buildListUsers();
}elseif(MODE == 'buildLists'){
	buildLists();
}elseif(MODE == 'deleteFolder'){
	deleteFolder();
}elseif(MODE == 'deleteListStep1'){
	deleteListStep1();
}elseif(MODE == 'deleteListStep2'){
	deleteListStep2();
}elseif(MODE == 'editList'){
	editList();
}elseif(MODE == 'listPropertiesStep1'){
	listPropertiesStep1();
}elseif(MODE == 'listPropertiesStep2'){
	listPropertiesStep2();
}elseif(MODE == 'lockList'){
	lockList();
}elseif(MODE == 'removeInvitation'){
	removeInvitation();
}elseif(MODE == 'removeFolder'){
	removeFolder();
}elseif(MODE == 'removeList'){
	removeList();
}elseif(MODE == 'removeUserFromFolder'){
	removeUserFromFolder();
}elseif(MODE == 'removeUserFromList'){
	removeUserFromList();
}elseif(MODE == 'folderPropertiesStep1'){
	folderPropertiesStep1();
}elseif(MODE == 'folderPropertiesStep2'){
	folderPropertiesStep2();
}elseif(MODE == 'shareFolderStep1'){
	shareFolderStep1();
}elseif(MODE == 'shareFolderStep2'){
	shareFolderStep2();
}elseif(MODE == 'shareListStep1'){
	shareListStep1();
}elseif(MODE == 'shareListStep2'){
	shareListStep2();
}elseif(MODE == 'transferListStep1'){
	transferListStep1();
}elseif(MODE == 'transferListStep2'){
	transferListStep2();
}elseif(MODE == 'transferListStop'){
	transferListStop();
}elseif(MODE == 'unlockList'){
	unlockList();
}elseif(MODE == 'updateFolderRole'){
	updateFolderRole();
}elseif(MODE == 'updateListRole'){
	updateListRole();
}elseif(MODE == 'updatePendingRole'){
	updatePendingRole();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function buildLists(){
	//Build the lists section.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_SESSION['userId'])){
			throw new Adrlist_CustomException('','$_SESSION[\'userId\'] is empty.');
		}
		//Get the framerate values.
		$frameratesArray = getFramerates();
		//Get the user's lists and folders with lists.
		$countStmt = "SELECT
	COUNT(*) AS 'count'
FROM
	userListSettings
JOIN
	lists ON lists.listId = userListSettings.listId
LEFT JOIN
	folders ON folders.folderId = lists.folderId
LEFT JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
	userFolderSettings.userId = userListSettings.userId AND
	userFolderSettings.folderRoleId > 0
WHERE
	userListSettings.userId = ?";
		$listsStmt = "SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName',
	lists.frId AS 'frId',
	lists.locked AS 'locked',
	lists.created AS 'listCreated',
	lists.modified AS 'listModified',
	folders.folderId AS 'folderId',
	folders.folderName AS 'folderName',
	folders.created AS 'folderCreated',
	folders.modified AS 'folderModified',
	userFolderSettings.folderRoleId AS 'folderRoleId',
	userListSettings.listRoleId AS 'listRoleId'
FROM
	userListSettings
JOIN
	lists ON lists.listId = userListSettings.listId
LEFT JOIN
	folders ON folders.folderId = lists.folderId
LEFT JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
	userFolderSettings.userId = userListSettings.userId AND
	userFolderSettings.folderRoleId > 0
WHERE
	userListSettings.userId = ? AND
	userListSettings.listRoleId > 0";
		$listsEndStmt = "
ORDER BY
	folders.folderName, folders.folderId, lists.listName, lists.listId";
		if(empty($_POST['searchVal'])){
			$search = false;
			$countStmt = $Dbc->prepare($countStmt);
			$listsStmt = $listsStmt . $listsEndStmt;
			$listsParams = array($_SESSION['userId']);
		}else{
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchval: ' . $searchVal);
			$listsSearchStmt = " AND
	(lists.listName LIKE ? || folders.folderName LIKE ?)";
			$countStmt = $Dbc->prepare($countStmt . $listsSearchStmt . $listsEndStmt);
			$listsStmt = $listsStmt . $listsSearchStmt . $listsEndStmt;
			$listsParams = array($_SESSION['userId'],$searchVal,$searchVal);
		}
		$countStmt->execute($listsParams);
		$count = $countStmt->fetch(PDO::FETCH_ASSOC);
		$itemCount = $count['count'];
		$pagination = new Adrlist_Pagination('buildLists','buildLists',$itemCount,'Search Lists',$search);
		list($offset,$limit) = $pagination->offsetLimit();
		$listsStmt .= "
LIMIT $offset, $limit";
		$listsStmt = $Dbc->prepare($listsStmt);
		$listsStmt->execute($listsParams);
		$foundLists = false;
		$listsArray = array();
		$hiddenRows = array();
		$foundFolders = false;
		$foldersArray = array();
		$hiddenRowsFolders = array();
		$hiddenRowsLists = array();
		while($row = $listsStmt->fetch(PDO::FETCH_ASSOC)){
			$foundLists = true;
			if(!empty($row['folderRoleId']) && !array_key_exists($row['folderId'],$foldersArray)){
				//The folders.
				$listsCount = 0;
				$foundFolders = true;
				//$debug->add('Wrapped text: ' . wordwrap($row['folderName'], 20, "<br>\n"));
				$name = '<button class="ui-btn ui-btn-icon-right ui-icon-carat-r ui-btn-inline" toggle="rowActionsFolder' . $row['folderId'] . '" style="margin:0"><i class="fa fa-folder-open fa-lg"></i><span class="mobileInline tabletInline"><div class="textLeft">' . wordwrap($row['folderName'], 30, '</div><div class="textLeft">') . '</div></span><span class="desktopInline">' . $row['folderName'] . '</span></button>';
				$listsArray['folder' . $row['folderId']] = array(
					$name,
					'',
					role($row['folderRoleId']),
					empty($row['folderCreated']) ? 'n/a' : Adrlist_Time::utcToLocal($row['folderCreated']),
					empty($row['folderModified']) ? 'n/a' : Adrlist_Time::utcToLocal($row['folderModified'])
				);
				//The folders actions.
				$folderRowActions = '<div class="break">Folder Actions' . faqLink(35) . '</div>';
				if($row['folderRoleId'] >= 3){
					$folderRowActions .= '	<button class="folderPropertiesStep1 ui-btn ui-btn-inline ui-mini ui-corner-all" folderId="' . $row['folderId'] . '" folderName="' . $row['folderName'] . '"><i class="fa fa-info-circle fa-lg" ></i>Properties</button><button class="buildFolderUsers ui-btn ui-btn-inline ui-mini ui-corner-all" folderId="' . $row['folderId'] . '"><i class="fa fa-users" ></i>Manage Users</button><button class="shareFolderStep1 ui-btn ui-btn-inline ui-mini ui-corner-all shareFolder" folderId="' . $row['folderId'] . '"><i class="fa fa-plus"></i>Share</button>';
				}
				if($row['folderRoleId'] == 4){
					$folderRowActions .= '<button class="deleteFolder ui-btn ui-btn-b ui-btn-inline ui-mini ui-corner-all" folderId="' . $row['folderId'] . '" folderName="' . $row['folderName'] . '"><i class="fa fa-trash-o" ></i>Delete</button>';
				}else{
					$folderRowActions .= '<button class="removeFolder ui-btn ui-btn-b ui-btn-inline ui-mini ui-corner-all" folderId="' . $row['folderId'] . '" folderName="' . $row['folderName'] . '">
<i class="fa fa-times" ></i>Remove</button>';
				}
				$hiddenRows['folder' . $row['folderId']] = array('rowActionsFolder' . $row['folderId'],$folderRowActions);
			}
			//Lists.
			$name = '<button class="ui-btn ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all ';
			$name .= $row['locked'] != 1 ? 'ui-btn-a' : 'ui-btn-b';
			$name .= '" toggle="rowActionsHolder' . $row['listId'] . '" style="margin:';
			$name .= empty($row['folderId']) ? '0' : '0 0 0 1em';
			$name .= '"><i class="fa fa-file-o fa-lg" ></i><span class="mobileInline tabletInline"><div class="textLeft">' . wordwrap($row['listName'], 30, '</div><div class="textLeft">') . '</div></span><span class="desktopInline">' . $row['listName'] . '</span></button>';
			$listsArray['list' . $row['listId']] = array(
				$name,
				$frameratesArray[$row['frId']],
				role($row['listRoleId']),
				empty($row['listCreated']) ? 'n/a' : Adrlist_Time::utcToLocal($row['listCreated']),
				empty($row['listModified']) ? 'n/a' : Adrlist_Time::utcToLocal($row['listModified'])
			);
			//The list actions.
			$listRowActions = '<div class="break">List Actions' . faqLink(35) . '</div>';
			if($row['locked'] != 1){
				if($row['listRoleId'] >= 2){
					$listRowActions .= '<button class="editList ui-btn ui-btn-inline ui-corner-all ui-mini" listId="' . $row['listId'] . '"><i class="fa fa-edit"></i>Edit</button>';
				}elseif($row['listRoleId'] == 1){
					$listRowActions .= '<button class="editList ui-btn ui-btn-inline ui-corner-all ui-mini" listId="' . $row['listId'] . '"><i class="fa fa-search"></i>View</button>';
				}
			}
			if($row['listRoleId'] >= 3){//The user must be at least a list Manager (3) or Owner (4).
				$listRowActions .= '<button class="ui-btn ui-btn-inline ui-corner-all ui-mini listPropertiesStep1" listId="' . $row['listId'] . '"><i class="fa fa-info-circle fa-lg" ></i>Properties</button><button class="ui-btn ui-btn-inline ui-corner-all ui-mini buildListUsers" listId="' . $row['listId'] . '"><i class="fa fa-users" ></i>Manage Users</button><button class="shareListStep1 ui-btn ui-btn-inline ui-corner-all ui-mini" listId="' . $row['listId'] . '"><i class="fa fa-plus" ></i>Share</button>';
			}
			if($row['listRoleId'] >= 4){//The user must be a list Owner (4).
				$listRowActions .= '<button class="transferListStep1 ui-btn ui-btn-inline ui-corner-all ui-mini" listId="' . $row['listId'] . '"><i class="fa fa-exchange" ></i>Transfer</button>';
				$listRowActions .= $row['locked'] ? '<button class="ui-btn ui-btn-inline ui-corner-all ui-mini unlockList" listId="' . $row['listId'] . '"><i class="fa fa-unlock-alt" ></i>Unlock</button>' : '<button class="ui-btn ui-btn-inline ui-corner-all ui-mini lockList" listId="' . $row['listId'] . '"><i class="fa fa-lock" ></i>Lock</button>';
	$listRowActions .= '<button class="ui-btn ui-btn-b ui-btn-inline ui-corner-all ui-mini deleteListStep1" listId="' . $row['listId'] . '"><i class="fa fa-trash-o" ></i>Delete</button>';
			}else{
				$listRowActions .= '<button class="ui-btn ui-btn-b ui-btn-inline ui-corner-all ui-mini removeList" listId="' . $row['listId'] . '" listname="' . $row['listName'] . '"><i class="fa fa-times" ></i>Remove</button>';
			}
			$hiddenRows['list' . $row['listId']] = array('rowActionsHolder' . $row['listId'],$listRowActions);
		}
		//Get the recently viewed lists.
		$recentListsStmt = $Dbc->prepare("SELECT
	lists.listId AS 'listId',
	lists.listName AS 'listName'
FROM
	userListSettings
JOIN
	lists ON lists.listId = userListSettings.listId
LEFT JOIN
	folders ON folders.folderId = lists.folderId
LEFT JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
	userFolderSettings.userId = userListSettings.userId AND
	userFolderSettings.folderRoleId > 0
WHERE
	userListSettings.userId = ?
ORDER BY
	lists.modified DESC
LIMIT 3");	
		$recentListsParams = array($_SESSION['userId']);
		$recentListsStmt->execute($recentListsParams);
		$recentLists = '';
		while($row = $recentListsStmt->fetch(PDO::FETCH_ASSOC)){
			$recentLists .= '<div class="editList link" listId="' . $row['listId'] . '">' . $row['listName'] . '</div>';
		}
		//Build the output.
		$output .= '<div>';
		$output .= $recentLists ? '	<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all"  toggle="recentlyViewedLists">Recently Edited Lists</button><div class="hide" id="recentlyViewedLists">' . $recentLists . '</div>' : '';
		$output .= '	<button class="ui-btn ui-btn-inline ui-corner-all ui-mini createFolderStep1"><i class="fa fa-plus" ></i>Create Folder</button>' . faqLink(30) . '<button class="ui-btn ui-btn-inline ui-corner-all ui-mini createListStep1"><i class="fa fa-plus" ></i>Create List</button>
</div>
	' . $pagination->output('adrlistViewOptions');
		if(!$foundLists && !$foundFolders){
			if($search){
				$output .= '<div class="red textCenter" style="padding:5px 0px 10px 0px;">There are no matches for "' . $_POST['searchVal'] . '".</div>';
			}else{
				$output .= '<div class="textCenter" style="padding:5px 0px 10px 0px;">You are not associated with any folders or lists.</div>';
			}
			pdoError(__LINE__,$listsStmt, $listsParams, true);
		}else{
			//Output the results.
			$titleArray = array(
				array('Name'),
				array('Framerate',3),
				array('Role' . faqLink(24),2),
				array('Created',4),
				array('Modified',5)
			);
			$buildLists = new Adrlist_BuildRows('ADRLists',$titleArray,$listsArray);
			$buildLists->addHiddenRows($hiddenRows);
			$output .= $buildLists->output();
		}
		if(MODE == 'buildLists'){
			$success = true;;
			$returnThis['holder'] = 'buildListsHolder';
			$returnThis['output'] = $output;
			$returnThis['buildLists'] = $output;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildLists'){
		returnData();
	}else{
		return $output;
	}
}

function buildFolderUsers(){
	//Build the users of the selected folder.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}elseif(!is_numeric($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is not numeric.');
		}
		$debug->add('$_SESSION[\'userId\']: ' . $_SESSION['userId'] . '$_POST[\'folderId\']: ' . $_POST['folderId']);
		$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['folderId']);
		$debug->printArray($folderInfo,'$folderInfo');
		if(empty($folderInfo['folderRoleId']) || $folderInfo['folderRoleId'] < 3){
			throw new Adrlist_CustomException("Your role does not allow you to edit this folder.",'');
		}
		//Select the current users of the folder.
		$folderUsersCountStmt = "SELECT
	COUNT(*) AS 'count'
FROM
	users
JOIN
	userFolderSettings ON userFolderSettings.userId = users.userId AND
	users.userId != ? AND
	userFolderSettings.folderId = ?";
		$folderUsersStmt = "SELECT
	users.userId AS 'userId',
	CONCAT_WS( ' ', users.firstName , users.lastName ) AS 'name',
	users.primaryEmail AS 'primaryEmail',
	userFolderSettings.folderRoleId AS 'folderRoleId',
	userFolderSettings.dateAdded AS 'dateAdded'
FROM
	users
JOIN
	userFolderSettings ON userFolderSettings.userId = users.userId AND
	users.userId != ? AND
	userFolderSettings.folderId = ?";
		$folderUsersStmtEnd = "
ORDER BY
	CONCAT_WS( ' ', users.firstName , users.lastName ), users.primaryEmail";
		//Select the users with pending invitations.
		$pendingFolderUsersCountStmt = "SELECT
	COUNT(*) AS 'count'
FROM
	invitations
WHERE
	folderId = ? AND
	respondDate IS NULL AND
	email NOT IN (SELECT users.primaryEmail FROM users)";
		$pendingUsersStmt = "SELECT
	invitationId AS 'invitationId',
	email AS 'email',
	folderRoleId AS 'folderRoleId',
	sentDate AS 'sentDate',
	senderId AS 'senderId'
FROM
	invitations
WHERE
	folderId = ? AND
	respondDate IS NULL AND
	email NOT IN (SELECT users.primaryEmail FROM users)
";
		$pendingUsersEndStmt = "
ORDER BY
	email";
		if(!empty($_POST['searchVal']) && !empty($_POST['searchFor']) && $_POST['searchFor'] == 'folderUsers'){
			//Search folderUsers.
			$searchFolderUsers = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$folderUsersSearchQuery = " AND
	(users.firstName LIKE ? || users.lastName LIKE ? || users.primaryEmail LIKE ?)";
			$folderUsersCountStmt .= $folderUsersSearchQuery;
			$folderUsersCountParams = array($_SESSION['userId'],$_POST['folderId'],$searchVal,$searchVal,$searchVal);
			$folderUsersStmt .= $folderUsersSearchQuery . $folderUsersStmtEnd;
			$folderUsersParams = array($_SESSION['userId'],$_POST['folderId'],$searchVal,$searchVal,$searchVal);
		}else{
			$searchFolderUsers = false;
			$folderUsersCountParams = array($_SESSION['userId'],$_POST['folderId']);
			$folderUsersStmt .= $folderUsersStmtEnd;
			$folderUsersParams = array($_SESSION['userId'],$_POST['folderId']);
		}
		if(!empty($_POST['searchVal']) && !empty($_POST['searchFor']) && $_POST['searchFor'] == 'pendingFolderUsers'){
			$searchPendingFolderUsers = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$pendingUsersSearchQuery = " AND
	email LIKE ?";;
			$pendingFolderUsersCountStmt .= $pendingUsersSearchQuery;
			$pendingFolderUsersCountParams = array($_POST['folderId'],$searchVal);
			$pendingUsersStmt .= $pendingUsersSearchQuery . $pendingUsersEndStmt;
			$pendingUsersParams = array($_POST['folderId'],$searchVal);
		}else{
			$searchPendingFolderUsers = false;
			$pendingFolderUsersCountParams = array($_POST['folderId']);
			$pendingUsersStmt .= $pendingUsersEndStmt;
			$pendingUsersParams = array($_POST['folderId']);
		}
		$folderUsersCountStmt = $Dbc->prepare($folderUsersCountStmt);
		$folderUsersCountStmt->execute($folderUsersCountParams);
		$row = $folderUsersCountStmt->fetch(PDO::FETCH_ASSOC);
		$folderUsersCount = $row['count'];
		$pagination = new Adrlist_Pagination('buildFolderUsers','folderUsers',$folderUsersCount,'Search Users',$searchFolderUsers);
		$pagination->addSearchParameters(array('folderId'=>$_POST['folderId'],'searchFor'=>'folderUsers'));
		$offsetLimit = $pagination->offsetLimit();
		list($offset,$limit) = $offsetLimit;
		$folderUsersStmt = $Dbc->prepare($folderUsersStmt . " LIMIT $offset, $limit");
		$folderUsersStmt->execute($folderUsersParams);
		$pendingFolderUsersCountStmt = $Dbc->prepare($pendingFolderUsersCountStmt);
		$pendingFolderUsersCountStmt->execute($pendingFolderUsersCountParams);
		$row = $pendingFolderUsersCountStmt->fetch(PDO::FETCH_ASSOC);
		$pendingFolderUsersCount = $row['count'];
		//$action,$uniqueId,$itemCount,$defaultSearchValue = false,$offsetLimit = false
		$pendingPagination = new Adrlist_Pagination('buildFolderUsers','pendingFolderUsers',$pendingFolderUsersCount,'Search Pending Users',$searchPendingFolderUsers);
		$pendingPagination->addSearchParameters(array('folderId'=>$_POST['folderId'],'searchFor'=>'pendingFolderUsers'));
		list($pendingOffset,$pendingLimit) = $pendingPagination->offsetLimit();
		$pendingUsersStmt = $Dbc->prepare($pendingUsersStmt . " LIMIT $pendingOffset, $pendingLimit");
		$pendingUsersStmt->execute($pendingUsersParams);
		$listUsersCount = 0;
		$folderUsersArray = array();
		$folderUsersHiddenArray = array();
		while($row = $folderUsersStmt->fetch(PDO::FETCH_ASSOC)){
			$listUsersCount++;
			$name = '<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all" toggle="userAction' . $row['userId'] . '">' . $row['name'] . '</button>';
			$email = '<a href="mailto:' . $row['primaryEmail'] . '">' . breakEmail($row['primaryEmail'], 30) . '</a>';
			$folderUsersArray[$row['userId']] = array(
				$name,
				Adrlist_Time::utcToLocal($row['dateAdded'])
			);
			//The user actions.
			if($folderInfo['folderRoleId'] == 3 && $row['folderRoleId'] >= 3){//Managers cannot change the role of managers or owners.
				$role = role($row['folderRoleId']);
			}else{
				$additionalAttributes = array('class'=> 'changeFolderRole','userId' => $row['userId'],'folderId' => $_POST['folderId']);
				$role = buildRoles('', $row['folderRoleId'],array(0,1,2,3,4),$additionalAttributes);
			}
			$userActions = '<div class="break">User Actions</div>';
			if($folderInfo['folderRoleId'] >= 3){
				$userActions .= '<div class="ui-field-contain"><label for="existingRole' . $row['userId'] . '">Folder Role ' . faqLink(24) . '</label>' . $role . '</div>
<button class="removeUserFromFolder ui-btn ui-btn-inline ui-corner-all ui-mini" folderId="' . $_POST['folderId'] . '" userId="' . $row['userId'] . '" folderName="' . $folderInfo['folderName'] . '"><i class="fa fa-times" ></i>Remove User</button>';
			}
			$folderUsersHiddenArray[$row['userId']] = array('userAction' . $row['userId'],$userActions);
		}
		//Build the pending folder users list.
		$pendingFolderUsersCount = 0;
		$pendingFolderUsersArray = array();
		$pendingFolderUsersHiddenArray = array();
		while($pendingRow = $pendingUsersStmt->fetch(PDO::FETCH_ASSOC)){
			$pendingFolderUsersCount++;
			$name = '<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all" toggle="pendingUserAction' . $pendingRow['invitationId'] . '">' . $pendingRow['email'] . '</button';
			//$email = '<a href="mailto:' . $pendingRow['email'] . '">' . breakEmail($pendingRow['email'], 40) . '</a>';
			$pendingFolderUsersArray[$pendingRow['invitationId']] = array(
				$name,
				Adrlist_Time::utcToLocal($pendingRow['sentDate'])
			);
			if($folderInfo['folderRoleId'] == 3 && $pendingRow['folderRoleId'] >= 3){//Managers cannot change the role of managers or owners.
				$role = role($pendingRow['folderRoleId']);
			}else{
				$additionalAttributes = array('class'=>'changePendingRole','invitationId'=>$pendingRow['invitationId'],'adrType'=>'folder','typeId'=>$_POST['folderId']);
				$role = buildRoles('',$pendingRow['folderRoleId'],array(0,1,2,3,4),$additionalAttributes);
			}
			//The pending folder user actions.
			$userActions = '<div class="break">User Actions</div>';
			if($folderInfo['folderRoleId'] >= 3){
				$userActions .= '<div class="ui-field-contain"><label for="pendingRole' . $row['userId'] . '">Folder Role ' . faqLink(24) . '</label>' . $role . '</div>
<button adrType="folder" class="removeInvitation ui-btn ui-btn-inline ui-corner-all ui-mini" invitationId="' . $pendingRow['invitationId'] . '" typeId="' . $_POST['folderId'] . '"><i class="fa fa-times" ></i>Remove User</button>';
			}
			$pendingFolderUsersHiddenArray[$pendingRow['invitationId']] = array('pendingUserAction' . $pendingRow['invitationId'],$userActions);
		}
			//Build Folder Users.
			$output .= '<div class="textCenter textLarge">
	<i class="fa fa-folder-open" ></i><span class="bold">' . $folderInfo['folderName'] . '</span> Users
</div>';
		if(empty($listUsersCount)){
			pdoError(__LINE__,$folderUsersStmt,$folderUsersParams,true);
			$output .= '<div class="break red" style="padding:5px 0px 10px 0px;">
	There are no users.
</div>';
		}
		if($searchFolderUsers){
			$results = intThis($searchFolderUsers);
			$output .= '<div class="break red">';
			$output .= $results == 1 ? $results . ' result': $results . ' results';
			$output .= ' for "' . $_POST['searchVal'] . '".</div>';
		}
		$output .= empty($listUsersCount) ? '' : '<div class="break red">Note: changes made to users\' folder roles are distributed to all of the lists inside the folder.</div>';
		$titleRowArray = array(
			array('Name'),
			array('Shared On',1)
		);
		$buildRows = new Adrlist_BuildRows('folderUsers',$titleRowArray,$folderUsersArray);
		$buildRows->addHiddenRows($folderUsersHiddenArray);
		$output .= $pagination->output('folderUsersViewOptions') . $buildRows->output() . '<div class="hr2" style="margin:1em"></div>';
		//Build pending users.
		$output .= '<div class="break textCenter textLarge" style="margin:1em 0 0 0">
	Pending Users
</div>';
		if(empty($pendingFolderUsersCount)){
			pdoError(__LINE__,$pendingUsersStmt,$pendingUsersParams,true);
			$output .= '<div class="break red" style="padding:5px 0px 10px 0px;">
	There are no users.
</div>';
		}
		if($searchPendingFolderUsers){
			$results = intThis($pendingFolderUsersCount);
			$output .= '<div class="break red">';
			$output .= $results == 1 ? $results . ' result': $results . ' results';
			$output .= ' for "' . $_POST['searchVal'] . '".</div>';
		}
		$pendingTitleRowArray = array(
			array('Pending User'),
			array('Shared On',1)
		);
		$buildPendingRows = new Adrlist_BuildRows('pendingFolderUsers',$pendingTitleRowArray,$pendingFolderUsersArray);
		$buildPendingRows->addHiddenRows($pendingFolderUsersHiddenArray);
		$output .= $pendingPagination->output('folderPendingUsersViewOptions') . $buildPendingRows->output();
		if(MODE == 'buildFolderUsers'){
			$success = true;
			$returnThis['output'] = $output;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildFolderUsers'){
		returnData();
	}else{
		return $output;
	}
}

function buildListUsers(){
	//Build the users of the selected list.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif(!is_numeric($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is not numeric.');
		}
		//Get the user's list role.
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		if($listInfo === false || $listInfo['listRoleId'] < 3){
			//The user must be a Manager (3) or higher to view list users.
			throw new Adrlist_CustomException("Your role does not allow you to edit this list.",'');
		}
		//Select the existing users.
		$listUsersCountStmt = "SELECT
	COUNT(users.userId) AS 'count'
FROM
	users
JOIN
	userListSettings ON userListSettings.userId = users.userId AND
	userListSettings.listId = ?
WHERE
	users.userId != ?";
		$listUsersStmt = "SELECT
	users.userId AS 'userId',
	CONCAT_WS(' ',users.firstName,users.lastName) AS 'name',
	users.primaryEmail AS 'primaryEmail',
	userListSettings.listRoleId AS 'listRoleId',
	userListSettings.dateAdded AS 'dateAdded'
FROM
	users
JOIN
	userListSettings ON userListSettings.userId = users.userId AND
	userListSettings.listId = ?
WHERE
	users.userId != ?";
		/*GROUP BY
	users.primaryEmail*/
		$listUsersEndStmt = "
ORDER BY
	CONCAT_WS(' ',users.firstName,users.lastName), users.primaryEmail";
		//Select the users with pending invitations.
		$pendingUsersCountStmt = "SELECT
	COUNT(email) AS 'count'
FROM
	invitations
WHERE
	listId = ? AND
	respondDate IS NULL AND
	email NOT IN (SELECT users.primaryEmail FROM users)";
		$pendingUsersStmt = "SELECT
	invitationId AS 'invitationId',
	email AS 'email',
	listRoleId AS 'listRoleId',
	sentDate AS 'sentDate',
	senderId AS 'senderId'
FROM
	invitations
WHERE
	listId = ? AND
	respondDate IS NULL AND
	email NOT IN (SELECT users.primaryEmail FROM users)";
		$pendingUsersEndStmt = "
ORDER BY
	email";
		if(!empty($_POST['searchVal']) && !empty($_POST['searchFor']) && $_POST['searchFor'] == 'listUsers'){
			$searchListUsers = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$listUsersSearchQuery = " AND
	(users.firstName LIKE ? || users.lastName LIKE ? || users.primaryEmail LIKE ?)";
			$listUsersStmt = $listUsersStmt . $listUsersSearchQuery . $listUsersEndStmt;
			$listUsersParams = array($_POST['listId'],$_SESSION['userId'],$searchVal,$searchVal,$searchVal);
			$listUsersCountStmt .= $listUsersSearchQuery;
		}else{
			$searchListUsers = false;
			$listUsersStmt .= $listUsersEndStmt;
			$listUsersParams = array($_POST['listId'],$_SESSION['userId']);
		}
		if(!empty($_POST['searchVal']) && !empty($_POST['searchFor']) && $_POST['searchFor'] == 'pendingListUsers'){
			$searchPendingListUsers = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$pendingUsersSearchQuery = " AND
email LIKE ?";
			$pendingUsersStmt = $pendingUsersStmt . $pendingUsersSearchQuery . $pendingUsersEndStmt;
			$pendingUsersParams = array($_POST['listId'],$searchVal);
			$pendingUsersCountStmt .= $pendingUsersSearchQuery;
		}else{
			$searchPendingListUsers = false;
			$pendingUsersStmt = $pendingUsersStmt . $pendingUsersEndStmt;
			$pendingUsersParams = array($_POST['listId']);
		}
		$listUsersCountStmt = $Dbc->prepare($listUsersCountStmt);
		$listUsersCountStmt->execute($listUsersParams);
		$row = $listUsersCountStmt->fetch(PDO::FETCH_ASSOC);
		$itemCount = $row['count'];
		$pagination = new Adrlist_Pagination('buildListUsers','listUsers',$itemCount,'Search Users',$searchListUsers);
		$pagination->addSearchParameters(array('listId'=>$_POST['listId'],'searchFor'=>'listUsers'));
		list($offset,$limit) = $pagination->offsetLimit();
		$listUsersStmt = $Dbc->prepare($listUsersStmt . " LIMIT $offset, $limit");
		$listUsersStmt->execute($listUsersParams);
		
		$pendingUsersCountStmt = $Dbc->prepare($pendingUsersCountStmt);
		$pendingUsersCountStmt->execute($pendingUsersParams);
		$row = $pendingUsersCountStmt->fetch(PDO::FETCH_ASSOC);
		$itemCount = $row['count'];
		$pendingPagination = new Adrlist_Pagination('buildListUsers','pendingListUsers',$itemCount,'Search Pending Users',$searchPendingListUsers);
		$pendingPagination->addSearchParameters(array('listId'=>$_POST['listId'],'searchFor'=>'pendingListUsers'));
		list($pendingOffset,$pendingLimit) = $pendingPagination->offsetLimit();
		$pendingUsersStmt = $Dbc->prepare($pendingUsersStmt . " LIMIT $pendingOffset, $pendingLimit");
		$pendingUsersStmt->execute($pendingUsersParams);

		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		$listUsersCount = 0;
		$listUsersArray = array();
		$listUsersHiddenRow = array();
		while($row = $listUsersStmt->fetch(PDO::FETCH_ASSOC)){
			$listUsersCount++;
			$name = '<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all" toggle="existingUser' . $row['userId'] . '">' . $row['name'] . '</button>';
			$email = '<a href="mailto:' . $row['primaryEmail'] . '">' . breakEmail($row['primaryEmail'], 30) . '</a>';
			$date = $row['dateAdded'] != '0000-00-00 00:00:00' ? Adrlist_Time::utcToLocal($row['dateAdded']) : 'n/a';
			$listUsersArray[$row['userId']] = array(
				$name,
				$date
			);
			//The user rowActions.
			$userActions = '';
			if($listInfo['listRoleId'] <= 3 && $row['listRoleId'] >= 3){//List managers cannot change the role of managers or owners.
				$role = role($row['listRoleId']);
			}else{
				$additionalAttributes = array('class'=>'changeListRole','userId'=>$row['userId'],'listId'=>$_POST['listId']);
				$role = buildRoles('changeListRole' . $row['userId'], $row['listRoleId'],array(0,1,2,3),$additionalAttributes);
			}
			if($listInfo['listRoleId'] >= 3){
				$userActions .= '<div class="ui-field-contain"><label for="existingRole' . $row['userId'] . '">List Role ' . faqLink(24) . '</label>' . $role . '</div>';
				$userActions .= $row['listRoleId'] < 3 || $listInfo['listRoleId'] == 4 ? '<button class="removeUserFromList ui-btn ui-btn-inline ui-corner-all ui-mini" listId="' . $_POST['listId'] . '" userId="' . $row['userId'] . '" listName="' . $listInfo['listName'] . '"><i class="fa fa-times" ></i>Remove User</button>' : '';
			}
			$listUsersHiddenRow[$row['userId']] = array('existingUser' . $row['userId'],$userActions);
		}
		//Build pending list users.
		$pendingListUsersCount = 0;
		$pendingUsersArray = array();
		$pendingUsersHiddenRow = array();
		while($pendingRow = $pendingUsersStmt->fetch(PDO::FETCH_ASSOC)){
			$pendingListUsersCount++;
			$name = '<button class="ui-btn ui-mini ui-btn-icon-right ui-icon-carat-r ui-btn-inline ui-corner-all" toggle="pendingUser' . $pendingRow['invitationId'] . '">' . $pendingRow['email'] . '</button>';
			$email = '<a href="mailto:' . $pendingRow['email'] . '">' . breakEmail($pendingRow['email'], 40) . '</a>';
			$sentDate = $pendingRow['sentDate'] != '0000-00-00 00:00:00' ? Adrlist_Time::utcToLocal($pendingRow['sentDate']) : 'n/a';
			$pendingUsersArray[$pendingRow['invitationId']] = array(
				$name,
				$sentDate
			);
			//The pending list user rowActions.
			$userActions = '';
			if($listInfo['listRoleId'] == 3 && $pendingRow['listRoleId'] >= 3){//Managers cannot change the role of other managers.
				$role = role($pendingRow['listRoleId']);
			}else{
				$additionalAttributes = array('class'=>'changePendingRole','invitationId'=>$pendingRow['invitationId'],'adrtype'=>'list','typeid'=>$_POST['listId']);
				$role = buildRoles('',$pendingRow['listRoleId'],array(0,1,2,3),$additionalAttributes);
			}
			if($listInfo['listRoleId'] >= 3){
				$userActions .= '<div class="ui-field-contain"><label for="pendingRole' . $pendingRow['invitationId'] . '">List Role ' . faqLink(24) . '</label>' . $role . '</div>
<button adrType="list" class="removeInvitation ui-btn ui-btn-inline ui-corner-all ui-mini" invitationId="' . $pendingRow['invitationId'] . '" typeId="' . $_POST['listId'] . '"><i class="fa fa-times" ></i>Remove User</button>';
			}
			$pendingUsersHiddenRow[$pendingRow['invitationId']] = array('pendingUser' . $pendingRow['invitationId'],$userActions);
		}
		//Build list users.
			$output .= '<div class="textCenter textLarge">
	<i class="fa fa-file-o" ></i><span class="bold">' . $listInfo['listName'] . '</span> Users
</div>';
		if(empty($listUsersCount)){
			pdoError(__LINE__,$listUsersStmt, $listUsersParams, true);
			$output .= '<div class="break red" style="padding:5px 0px 10px 0px;">
	There are no users.
</div>';
		}
		if($searchListUsers){
			$results = intThis($listUsersCount);
			$output .= '<div class="break red">';
			$output .= $results == 1 ? $results . ' result': $results . ' results';
			$output .= ' for "' . $_POST['searchVal'] . '".</div>';
		}
		$listUsersTitleArray = array(
			array('Name'),
			array('Shared On',1)
		);
		$buildListUsers = new Adrlist_BuildRows('existingListUsers',$listUsersTitleArray,$listUsersArray);
		$buildListUsers->addHiddenRows($listUsersHiddenRow);
		$output .= $pagination->output('listUsersViewOptions') . $buildListUsers->output();
		$output .= '<div class="hr3" style="margin:2em 0;"></div>';
		//Build pending users.
		$output .= '<div class="break textCenter textLarge" style="margin:1em 0 0 0">
	Pending Users
</div>';
		if(empty($pendingListUsersCount)){
			pdoError(__LINE__,$pendingUsersStmt, $pendingUsersParams, true);
			$output .= '<div class="break red" style="padding:5px 0px 10px 0px;">
	There are no pending users.
</div>';
		}
		if($searchPendingListUsers){
			$results = intThis($pendingListUsersCount);
			$output .= '<div class="break red">';
			$output .= $results == 1 ? $results . ' result': $results . ' results';
			$output .= ' for "' . $_POST['searchVal'] . '".</div>';
		}
		$pendingUsersTitleArray = array(
			array('Pending User'),
			array('Shared On',1)
		);
		$buildPendingUsers = new Adrlist_BuildRows('pendingListUsers',$pendingUsersTitleArray,$pendingUsersArray);
		$buildPendingUsers->addHiddenRows($pendingUsersHiddenRow);
		$output .= $pendingPagination->output('pendingListUsersViewOptions') . $buildPendingUsers->output();
		if(MODE == 'buildListUsers'){
			$success =  true;
			$returnThis['output'] = $output;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildListUsers'){
		returnData();
	}else{
		return $output;
	}
}

function createFolderStep1(){
	//Show the create folder dialogue.
	global $debug, $message, $success, $Dbc, $returnThis;
		//The list name field.
	$folderName = '<div class="ui-field-contain">
	<label for="createFolderName" unused="ui-hidden-accessible">Folder Name</label>
	<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="createFolderName" goswitch="createFolderStep2" name="createFolderName" placeholder="" value="" type="text">
</div>';
	//Build the output.
	$output = '<div class="myAccountTitle">
	Create a Folder
</div>' . $folderName . '
<div>
	<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all" id="createFolderStep2">Create Folder</button>' . cancelButton() . '
</div>
';
	$returnThis['createFolderStep1'] = $output;
	if(MODE == 'createFolderStep1'){
		$success = true;
		returnData();
	}
}

function createFolderStep2(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['createFolderName'])){
			throw new Adrlist_CustomException('','$_POST[\'createFolderName\'] is empty.');
		}
		$Dbc->beginTransaction();
		$stmt = $Dbc->prepare("INSERT INTO
	folders
SET
	folderName = ?,
	cId = ?,
	created = ?,
	mId = ?,
	modified = ?");
		$params = array($_POST['createFolderName'],$_SESSION['userId'],DATETIME,$_SESSION['userId'],DATETIME);
		$stmt->execute($params);
		$folderId = $Dbc->lastInsertId();
		$debug->add('$folderId: ' . "$folderId");
		//Insert the user's folder role.
		$stmt = $Dbc->prepare("INSERT INTO
	userFolderSettings
SET
	folderId = ?,
	userId = ?,
	folderRoleId = ?,
	dateAdded = ?");
		$params = array($folderId,$_SESSION['userId'],4,DATETIME);
		$stmt->execute($params);
		$Dbc->commit();
		$returnThis['buildLists'] = buildLists();
		if(MODE == 'createFolderStep2'){
			$success = true;
			$message .= 'Created the folder "' . $_POST['createFolderName'] . '".<br>';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'createFolderStep2'){
		returnData();
	}
}

function createListStep1(){
	//Show the create list dialogue. The user can select a folder to add the list to.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		//Get the user's folders.
		$stmt = $Dbc->prepare("SELECT
	folders.folderId AS 'folderId',
	folders.folderName AS 'folderName',
	userFolderSettings.folderRoleId AS 'folderRoleId'
FROM
	folders
JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
	userFolderSettings.userId = ?");
		$params = array($_SESSION['userId']);
		$stmt->execute($params);
		$selectFolder = '';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			if($row['folderRoleId'] == 4){
				$selectFolder .= '	<option value="' . $row['folderId'] . '">' . $row['folderName'] . '</option>
';
			}
		}
		$locked = $_SESSION['activeLists'] >= $_SESSION['credits'] ? '<div class="red textCenter">This list will be locked because your credit balance is zero. ' . faqLink(29) . '</div>' : '';
		//The list name field.
		$listName = '<div class="ui-field-contain">
	<label for="createListName" unused="ui-hidden-accessible">List Name</label>
	<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="createListName" goswitch="createListStep2" name="createListName" placeholder="" value="" type="text">
</div>';
		//The folder select.
		if(!empty($selectFolder)){
			$folderSelect = '<div class="ui-field-contain">
	<label for="createListIntoFolder" unused="ui-hidden-accessible">into folder<br>
(optional)</label>
	<select id="createListIntoFolder" goswitch="createListStep2">
		<option value="">(no folder)</option>
		' . $selectFolder . '</select>
	</select>
</div>
<div class="hide" id="hideDistributeOption">
	<fieldset data-role="controlgroup">
		<input name="createListDistributeRoles" id="createListDistributeRoles" type="checkbox">
		<label for="createListDistributeRoles">Distribute folder roles as list roles</label>
	</fieldset>
	What does this mean ' . faqLink(46) . '
</div>';
		}else{
			$folderSelect = '<span class="hide" id="createListIntoFolder">0</span>';
		}
		//Build the framerate list.
		$frameratesArray = getFramerates();
		$framerateOutput = '<div class="ui-field-contain">
	<label for="createListFramerate" unused="ui-hidden-accessible">Framerate</label>
	<select id="createListFramerate" goswitch="createListStep2">';
		foreach($frameratesArray as $frId => $framerate){
			$framerateOutput .= '<option value="' . $frId . '">' . $framerate . '</option>';
		}
		$framerateOutput .= '
	</select>
</div>';
		//Build the output.
		$output .= '<div class="myAccountTitle">
	Create a List
</div>' . $listName . $folderSelect . $framerateOutput . '
<div>
	<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all" id="createListStep2">Create List</button>' . cancelButton() . '
</div>';
		$success = MODE == 'createListStep1' ? true : $success;
		$returnThis['createListStep1'] = $output;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'createListStep1'){
		returnData();
	}else{
		return $output;
	}
}

function createListStep2(){
	//Create a new list.
	global $debug, $message, $success, $Dbc, $returnThis, $transactionStarted;
	$output = '';
	try{
		if(!isset($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is not set.');
		}elseif(empty($_POST['createListName'])){
			throw new Adrlist_CustomException('','$_POST[\'createListName\'] is empty.');
		}elseif(empty($_POST['createListFramerate'])){
			throw new Adrlist_CustomException('','$_POST[\'createListFramerate\'] is empty.');
		}elseif(!isset($_POST['distributeFolderRoles'])){
			throw new Adrlist_CustomException('','$_POST[\'distributeFolderRoles\'] is not set.');
		}
		$_POST['folderId'] = intThis($_POST['folderId']);
		$Dbc->beginTransaction();
		$createListStmt = "INSERT INTO
	lists
SET
	locked = :locked,
	folderId = :folderId,
	listName = :listName,
	frId = :frId,
	cId = :cId,
	created = :created,
	mId = :mId,
	modified = :modified";
		if($_SESSION['credits'] <= $_SESSION['activeLists']){
			$locked = true;
			$lockedParam = 1;
		}else{
			$locked = false;
			$lockedParam = 0;
		}
	
		$createListStmt = $Dbc->prepare($createListStmt);
		if(empty($_POST['folderId'])){
			//A folder was not selected.
			$folderSelected = false;
			$userListRoleId = 4;
			//$createListParams = array($lockedParam,$_POST['createListName'],$_POST['createListFramerate'],$_SESSION['userId'],DATETIME,$_SESSION['userId'],DATETIME);
			$createListStmt->bindValue(':locked', $lockedParam, PDO::PARAM_INT);
			$createListStmt->bindValue(':folderId', null, PDO::PARAM_INT);
			$createListStmt->bindValue(':listName', $_POST['createListName'], PDO::PARAM_STR);
			$createListStmt->bindValue(':frId', $_POST['createListFramerate'], PDO::PARAM_INT);
			$createListStmt->bindValue(':cId', $_SESSION['userId'], PDO::PARAM_INT);
			$createListStmt->bindValue(':created', DATETIME, PDO::PARAM_STR);
			$createListStmt->bindValue(':mId', $_SESSION['userId'], PDO::PARAM_INT);
			$createListStmt->bindValue(':modified', DATETIME, PDO::PARAM_STR);

			//pdoError(__LINE__,$createListStmt,$createListParams);
		}else{
			//A folder was selected. Double check the user has a sufficient role to add lists to this folder.
			$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['folderId']);
			$currentUserFolderRole = $folderInfo['folderRoleId'];
			if($currentUserFolderRole < 4){
				throw new Adrlist_CustomException('Your role for the selected folder does not allow you to add lists.');
			}
			$transactionStarted = true;
			//See if the user already has a role for a list with this name.
			$stmt = $Dbc->prepare("SELECT
	lists.listName
FROM
	lists
JOIN
	userListSettings ON userListSettings.listId = lists.listId AND
	lists.listName = ? AND
	userListSettings.userId = ?");
			$stmt->execute(array($_POST['createListName'],$_SESSION['userId']));
			if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				throw new Adrlist_CustomException('There is a list by that name for which you have a role. Please choose a different name or it will get confusing.','');
			}
			$folderSelected = true;
			//Insert the list.
			//$createListParams = array($lockedParam,$_POST['folderId'],$_POST['createListName'],$_POST['createListFramerate'],$_SESSION['userId'],DATETIME,$_SESSION['userId'],DATETIME);

			$createListStmt->bindValue(':locked', $lockedParam, PDO::PARAM_INT);
			$createListStmt->bindValue(':folderId', $_POST['folderId'], PDO::PARAM_INT);
			$createListStmt->bindValue(':listName', $_POST['createListName'], PDO::PARAM_STR);
			$createListStmt->bindValue(':frId', $_POST['createListFramerate'], PDO::PARAM_INT);
			$createListStmt->bindValue(':cId', $_SESSION['userId'], PDO::PARAM_INT);
			$createListStmt->bindValue(':created', DATETIME, PDO::PARAM_STR);
			$createListStmt->bindValue(':mId', $_SESSION['userId'], PDO::PARAM_INT);
			$createListStmt->bindValue(':modified', DATETIME, PDO::PARAM_STR);

			//pdoError(__LINE__,$createListStmt,$createListParams);
		}

		$createListStmt->execute();
		$listId = $Dbc->lastInsertId();
		$listId = intVal($listId);
		//Get the user's default list settings.
		$listSettings = getDefaultListSettings();
		//Insert user's list role and settings.
		$userListStmt = $Dbc->prepare('INSERT INTO
	userListSettings
SET
	userId = ?,
	listId = ?,
	listRoleId = ?,
	dateAdded = ?,
	limitCount = ?,
	orderBy = ?,
	orderDirection = ?,
	viewCharacters = ?,
	showCharacterColors = ?');
		$userListStmt->execute(array($_SESSION['userId'],$listId,4,DATETIME,$listSettings['defaultLimit'],$listSettings['defaultOrderBy'],$listSettings['defaultOrderDirection'],'viewAll',$listSettings['defaultShowCharacterColors']));
		if($folderSelected){
			$distributeFolderRoles = $_POST['distributeFolderRoles'] == 'true' ? true : false;
			//If a folder was selected we will add list roles for all folder users.
			if(!createListRoles($_SESSION['userId'],$listId,$_POST['folderId'],$distributeFolderRoles)){
				$Dbc->rollback();
				throw new Adrlist_CustomException('','createListRoles() returned false.');
			}
		}
		$Dbc->commit();
		$returnThis['locked'] = !$locked;
		$returnThis['buildLists'] = buildLists();
		if(MODE == 'createListStep2'){
			$message .= 'Added the list "' . $_POST['createListName'] . '".';
			$message .= $locked ? '<div style="margin-top:2em">This list has been automatically locked because you do not have enough credits.</div>' : '';
			$success = true;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'createListStep2'){
		returnData();
	}else{
		return $output;
	}
}

function deleteFolder(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is not set.','');
		}
		//See if the folder has lists that must be transferred before deletion.
		$stmt = $Dbc->prepare("SELECT
	folders.folderName as 'folderName',
	lists.listId AS 'listId'
FROM
	folders
JOIN
	lists ON folders.folderId = lists.folderId AND
	folders.folderId = ?");
		$stmt->execute(array($_POST['folderId']));
		$foundRows = false;
		if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$foundRows = true;
		}
		if(empty($foundRows)){
			//The user must be an owner to delete a folder.
			$stmt = $Dbc->prepare("DELETE FROM
	folders
USING
	folders
JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
	userFolderSettings.userId = ? AND
	userFolderSettings.folderRoleId >= ? AND
	folders.folderId = ?");
			$stmt->execute(array($_SESSION['userId'],4,$_POST['folderId']));
			$debug->add('Tried to delete folderId: ' . $_POST['folderId']);
			//We don't need to delete useruserFolderRole because the relational database should delete it for us.
			$returnThis['buildLists'] = buildLists();
			if(MODE == 'deleteFolder'){
				$success = true;
				$message .= 'Deleted the folder.';
			}
		}else{
			$message .= 'Folders containing lists cannot be deleted. The lists must first be transferred or moved out of the folder.<br>';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'deleteFolder'){
		returnData();
	}
}

function deleteListStep1(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is not set.','');
		}
		$output .= '<div class="myAccountTitle">
	Delete List
</div>
<i class="fa fa-exclamation-triangle fa-4x red" style="padding:5px"></i>Deleting this list will permanently delete all information associated with it. This includes lines, characters, comments, etc. This action cannot be undone.
<fieldset class="center textCenter" data-role="controlgroup">
	<input data-wrapper-class="center textCenter" name="deleteListCheckbox" id="deleteListCheckbox" type="checkbox">
	<label for="deleteListCheckbox">By checking this box you confirm your understanding of the above</label>
</fieldset>
<div>
	<button class="ui-btn ui-btn-inline ui-corner-all ui-mini" id="deleteListStep2" listId="' . $_POST['listId'] . '"><i class="fa fa-trash" ></i>Delete List</button>' . cancelButton() . '
</div>';
		$success = MODE == 'deleteListStep1' ? true : $success;
		$returnThis['deleteListStep1'] = $output;
	}catch(Adrlist_CustomException $e){}
	if(MODE == 'deleteListStep1'){
		returnData();
	}
}

function deleteListStep2(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is not set.');
		}elseif(!is_numeric($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is not numeric.');
		}
		$_POST['listId'] = intThis($_POST['listId']);
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		$debug->printArray($listInfo,'$listInfo');
		if($listInfo === false || $listInfo['listRoleId'] == 4){//The user must be an owner to delete a list.
			//Delete the list.
			$stmt = $Dbc->prepare("DELETE FROM
	lists
WHERE
	listId= ?");
			$stmt->execute(array($_POST['listId']));
			$debug->add('Tried to delete listId: ' . $_POST['listId']);
			//The relational database will delete other associated table entries for us.
			$returnThis['buildLists'] = buildLists();
			if(MODE == 'deleteListStep2'){
				$success = true;
				$message .= 'Deleted the list.<br>';
			}
		}else{
			$message .= 'Your role does not allow you to delete that list.<br>';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'deleteListStep2'){
		returnData();
	}
}

function editList(){
	//Go to the edit list page.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.','');
		}
		$stmt = $Dbc->prepare("UPDATE
	userSiteSettings
SET
	listId = ?
WHERE
	userId = ?");
		$stmt->execute(array($_POST['listId'],$_SESSION['userId']));
		$returnThis['returnCode'] = LINKEDITLIST;
		$success = MODE == 'editList' ? true : $success;
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'editList'){
		returnData();
	}
}

function folderPropertiesStep1(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}
		$_POST['folderId'] = intval($_POST['folderId']);
		$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['folderId']);
		$debug->printArray($folderInfo,'$folderInfo');
		$folderRoleId = $folderInfo['folderRoleId'];
		if(empty($folderRoleId) || $folderRoleId < 3){
			throw new Adrlist_CustomException('Your role does not allow you to change the name of this folder.','');
		}
		$folderName = '<div class="ui-field-contain">
	<label for="renameFolderInput" unused="ui-hidden-accessible">Folder Name</label>
	<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="renameFolderInput" goswitch="folderPropertiesStep2" name="renameFolderInput" placeholder="" value="" type="text">
</div>';
		//Build the output.
		$output = '<div class="myAccountTitle">
	Rename Folder
</div>' . $folderName . '
<div>
	<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-heart" id="folderPropertiesStep2" folderId="' . $_POST['folderId'] . '">Save</button>' . cancelButton() . '
</div>
';
		$success = MODE == 'folderPropertiesStep1' ? true : $success;
		$returnThis['folderPropertiesStep1'] = $output;
	}catch(Adrlist_CustomException $e){}
	if(MODE == 'folderPropertiesStep1'){
		returnData();
	}
}

function folderPropertiesStep2(){
	//User must be a Manager (3) or Owner (4).
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}elseif(empty($_POST['folderName'])){
			throw new Adrlist_CustomException('Please enter a folder name','$_POST[\'folderName\'] is empty.');
		}
		$_POST['folderId'] = intThis($_POST['folderId']);
		$_POST['folderName'] = trim($_POST['folderName']);
		$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['folderId']);
		$folderRoleId = $folderInfo['folderRoleId'];
		if(empty ($folderRoleId) || $folderRoleId < 3){
			throw new Adrlist_CustomException('Your role does not allow you to change the name of this list.' . $folderRoleId,'');
		}
		$stmt = $Dbc->prepare("UPDATE
	folders
JOIN
	userFolderSettings ON folders.folderId = userFolderSettings.folderId AND
	userFolderSettings.userId = ? AND
	userFolderSettings.folderRoleId = ? AND
	folders.folderId = ?
SET
	folders.folderName = ?");
		$params = array($_SESSION['userId'],$folderRoleId,$_POST['folderId'],$_POST['folderName']);
		$stmt->execute($params);
		updateFolderHist();
		$returnThis['buildLists'] = buildLists();
		if(MODE == 'folderPropertiesStep2'){
			$success = true;
			$message .= 'Saved';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'folderPropertiesStep2'){
		returnData();
	}
}

function listPropertiesStep1(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}
		$_POST['listId'] = intval($_POST['listId']);
		//Get the listname, frId, and folderId.
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		if($listInfo === false || $listInfo['listRoleId'] < 3){
			throw new Adrlist_CustomException('Your role does not allow you to change the properties of this list.','');
		}
		//Get the folders for which the user is an owner (4). This allows the user to move the list into a folder.
		$userFoldersStmt = $Dbc->prepare("SELECT
	folders.folderId AS 'folderId',
	folders.folderName AS 'folderName',
	userFolderSettings.folderRoleId AS 'folderRoleId'
FROM
	userFolderSettings
JOIN
	folders ON folders.folderId = userFolderSettings.folderId
WHERE
	userFolderSettings.userId = ? AND
	userFolderSettings.folderRoleId = ?");
		$params = array($_SESSION['userId'],4);
		$userFoldersStmt->execute($params);
		$selectFolder = '';
		while($row = $userFoldersStmt->fetch(PDO::FETCH_ASSOC)){
			if(!empty($listInfo['folderId']) && $row['folderId'] == $listInfo['folderId']){
				$selectFolder .= '	<option value="' . $row['folderId'] . '" selected="selected">' . $row['folderName'] . '</option>
';
			}else{
				$selectFolder .= '	<option value="' . $row['folderId'] . '">' . $row['folderName'] . '</option>
';
			}
		}
		//Build the framerate list.
		$frameratesArray = getFramerates();
		$frameRateOutput = '<div class="ui-field-contain">
	<label for="listPropertyFramerate">Framerate</label>
		<select id="listPropertyFramerate" goswitch="listPropertiesStep2">';
		foreach($frameratesArray as $frId => $framerate){
			$frameRateOutput .= '<option value="' . $frId . '"';
			$frameRateOutput .= $frId == $listInfo['frId'] ? ' selected="selected"' : '';
			$frameRateOutput .= '>' . $framerate . '</option>';
		}
		$frameRateOutput .= '</select>
	</div>';
		//Build the output.
		$output .= '<div class="myAccountTitle">
	List Properties
</div>
<div class="ui-field-contain">
	<label for="listPropertyName">List Name</label>
	<input autocapitalize="on" autocorrect="off" data-wrapper-class="true" id="listPropertyName" goswitch="listPropertiesStep2" name="listPropertyName" placeholder="" value="' . $listInfo['listName'] . '" type="text">
</div>';
		if(!empty($selectFolder)){
			$output .= '<div class="ui-field-contain">
	<label for="newFolderId">in folder</label>
	<select id="newFolderId" goswitch="listPropertiesStep2">
		<option value="0">(no folder)</option>
' . $selectFolder . '</select>
</div>
<div class="red">
	Note: By changing the folder of this list, all users with a role for this list will automatically be given a role for the selected folder.' . faqLink(24) . '
</div>
';
		}
		$output .= $frameRateOutput . '
<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-heart" id="listPropertiesStep2" listId="' . $_POST['listId'] . '">Save</button>' . cancelButton();
		$returnThis['listPropertiesStep1'] = $output;
		$success = MODE == 'listPropertiesStep1' ? true : $success;
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'listPropertiesStep1'){
		returnData();
	}
}

function listPropertiesStep2(){
	//User must be Manager (3) or Owner (4).
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif(empty($_POST['newListName'])){
			throw new Adrlist_CustomException('','$_POST[\'newListName\'] is empty.');
		}elseif(!isset($_POST['newFolderId'])){
			throw new Adrlist_CustomException('','$_POST[\'newFolderId\'] is not set.');
		}elseif(empty($_POST['newListFramerate'])){
			throw new Adrlist_CustomException('','$_POST[\'newListFramerate\'] is empty.');
		}
		$_POST['listId'] = intThis($_POST['listId']);
		$_POST['newListName'] = trim($_POST['newListName']);
		$_POST['newListFramerate'] = intThis($_POST['newListFramerate']);
		$_POST['newFolderId'] = intThis($_POST['newFolderId']);
		//Check the user's list role.
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		if($listInfo === false || $listInfo['listRoleId'] < 3){
			throw new Adrlist_CustomException('Your role does not allow you to change the properties of this list.','');
		}
		$Dbc->beginTransaction();
		//Build the update statement and params.
		$updateFolderPropertiesStmt = "UPDATE
	lists
JOIN
	userListSettings ON lists.listId = userListSettings.listId AND
	userListSettings.userId = ? AND
	lists.listId = ?
SET
	lists.listName = ?,
	lists.frId = ?";
		$updateFolderPropertiesParams = array($_SESSION['userId'],$_POST['listId'],$_POST['newListName'],$_POST['newListFramerate']);
		if(empty($_POST['newFolderId'])){
			//Set folderID to NULL.
			$updateFolderPropertiesStmt .= ",
lists.folderId = ?";
			$updateFolderPropertiesParams[] = NULL;
		}else{
			//A folder was selected. Verify the user's folderRoleId.
			$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['newFolderId']);
			$folderRoleId = $folderInfo['folderRoleId'];
			if(empty($folderRoleId) || $folderRoleId < 4){
				//We don't care if there is no role or if the role is zero. Either way, deny access.
				throw new Adrlist_CustomException('Your role does not allow you to add lists to that folder.','');
			}
			//Update the folder properties and set the folderID.
			$updateFolderPropertiesStmt .= ",
lists.folderId = ?";
			$updateFolderPropertiesParams[] = $_POST['newFolderId'];
			//Make sure all list users have a folderRoleId.
			//Select the list users.
			$listUsersStmt = $Dbc->prepare("SELECT
	users.userId AS 'userId',
	userListSettings.listRoleId AS 'listRoleId'
FROM
	users
JOIN
	userListSettings ON userListSettings.userId = users.userId AND
	userListSettings.listId = ?");
			$listUsersStmt->execute(array($_POST['listId']));
			$listUsers = array();
			$insertFolderRoleStmt = $Dbc->prepare("INSERT INTO
	userFolderSettings
SET
	folderId = ?,
	userId = ?,
	folderRoleId = ?,
	dateAdded = ?");
			while($listUsersRow = $listUsersStmt->fetch(PDO::FETCH_ASSOC)){
				$listUsers[] = array('userId' => $listUsersRow['userId'],'listRoleId' => $listUsersRow['listRoleId']);
				//Check if the list users has a folderRoleId.
				$folderInfo = getFolderInfo($listUsersRow['userId'],$_POST['newFolderId']);
				$folderRoleId = $folderInfo['folderRoleId'];
				if($folderRoleId === false && $listUsersRow['listRoleId'] != 4){
					//The user has no current folderRoleId and is not the owner of the folder. The default folderRoleId will be Member (1).
					$insertFolderRoleParams = array($_POST['newFolderId'],$listUsersRow['userId'],1,DATETIME);
					$insertFolderRoleStmt->execute($insertFolderRoleParams);
				}
			}
			$debug->printArray($listUsers,'$listUsers');
			updateFolderHist($_POST['newFolderId']);
		}
		$updateFolderPropertiesStmt = $Dbc->prepare($updateFolderPropertiesStmt);
		$updateFolderPropertiesStmt->execute($updateFolderPropertiesParams);
		$rowCount = $updateFolderPropertiesStmt->rowCount();
		updateListHist($_POST['listId']);
		$Dbc->commit();
		$returnThis['buildLists'] = buildLists();			
		if(MODE == 'listPropertiesStep2'){
			$success = true;
			$message .= 'Saved';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'listPropertiesStep2'){		
		returnData();
	}
}

function lockList(){
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		if($listInfo['listRoleId'] < 4){
			throw new Adrlist_CustomException('Your role does not allow you to lock this list.','$listInfo[\'listRoleId\']: ' . $listInfo['listRoleId']);
		}
		$unlockStmt = $Dbc->prepare("UPDATE
	lists
SET
	locked = 1
WHERE
	listId = ?");
		$unlockStmt->execute(array($_POST['listId']));
		$locked = reconcileLists($_SESSION['userId']);
		if(MODE == 'lockList'){
			$success = true;
			$returnThis['locked'] = $locked;
			$returnThis['buildLists'] = buildLists();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'lockList'){
		returnData();
	}
}

function removeInvitation(){
	//Remove an invitation to share a folder or list.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['invitationId'])){
			throw new Adrlist_CustomException('','$_POST[\'invitationId\'] is empty.');
		}elseif(empty($_POST['type'])){
			throw new Adrlist_CustomException('','$_POST[\'type\'] is empty.');
		}elseif(empty($_POST['typeId'])){
			throw new Adrlist_CustomException('','$_POST[\'typeId\'] is empty.');
		}
		if($_POST['type'] == 'list'){
			$listInfo = getListInfo($_SESSION['userId'],$_POST['typeId']);
			$role = $listInfo['listRoleId'];
		}else{
			$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['typeId']);
			$role = $folderInfo['folderRoleId'];
		}
		//Verify the user has a sufficient role to delete invitations.
		if($role === false || $role < 3){//Must be at least a Manager (3) to share lists.
			throw new Adrlist_CustomException('Your role does not allow you to edit this ' . $_POST['type'] . '.','');
		}
		$stmt = $Dbc->prepare("DELETE FROM
	invitations
WHERE
	invitationId = ?");
		$stmt->execute(array($_POST['invitationId']));
		$success = MODE == 'removeInvitation' ? true : $success;
		$message .= 'Removed User';
		if($_POST['type'] == 'list'){
			$_POST['listId'] = $_POST['typeId'];
			$returnThis['buildListUsers'] = buildListUsers();
		}else{
			$_POST['folderId'] = $_POST['typeId'];
			$returnThis['buildFolderUsers'] = buildFolderUsers();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'removeInvitation'){
		returnData();
	}
}

function removeFolder(){
	//Remove yourself from a folder. This is not the same as removing a user from a folder.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_SESSION['userId'])){
			throw new Adrlist_CustomException('','$_SESSION[\'userId\'] is empty.');
		}elseif(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}elseif(!is_numeric($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}
		$Dbc->beginTransaction();
		//Delete the user's folderRoleId.
		$stmt = $Dbc->prepare("DELETE FROM
	userFolderSettings
WHERE
	userId = ? AND
	folderId = ?");
		$stmt->execute(array($_SESSION['userId'], $_POST['folderId']));
		//Delete the user's listRoleId for all lists in the folder.
		$deleteListRoleIdStmt = $Dbc->prepare("DELETE
	userListSettings
FROM
	userListSettings
JOIN
	lists ON lists.listId = userListSettings.listId AND
	lists.folderId = ? AND
	userListSettings.userId = ?");
		$deleteListRoleIdParams = array($_POST['folderId'],$_SESSION['userId']);
		$deleteListRoleIdStmt->execute($deleteListRoleIdParams);
		$Dbc->commit();
		$returnThis['buildLists'] = buildLists();
		if(MODE == 'removeFolder'){
			$success = true;
			$message .= 'Removed Folder';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'removeFolder'){
		returnData();
	}
}

function removeUserFromFolder(){
	//Remove a user from a folder. This is not the same as removing yourself from a folder. If the user has pending invited users those invitations will be deleted.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['userId'])){
			throw new Adrlist_CustomException('','$_POST[\'userId\'] is empty.');
		}elseif(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}elseif(!is_numeric($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is not numeric.');
		}
		//Verify the user has sufficient permission to remove a user.
		$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['folderId']);
		$folderRoleId = $folderInfo['folderRoleId'];
		if(empty($folderRoleId) || $folderRoleId < 3){
			throw new Adrlist_CustomException("You role doesn't allow you to remove users from this folder.",'');
		}
		$Dbc->beginTransaction();
		//Delete any pending invitations the user has created.
		$invitedStmt = $Dbc->prepare("DELETE FROM
	invitations
WHERE
	senderId = ? AND
	folderId = ? AND
	respondDate IS NULL");
		$invitedParams = array($_POST['userId'],$_POST['folderId']);
		$invitedStmt->execute($invitedParams);
		//Delete the user's folderRoleId.
		$stmt = $Dbc->prepare("DELETE FROM
	userFolderSettings
WHERE
	userId = ? AND
	folderId = ?");
		$stmt->execute(array($_POST['userId'],$_POST['folderId']));
		//Delete the user's listRoleId's too.
		$updateListsRoles = $Dbc->prepare("DELETE FROM
	userListSettings
USING
	userListSettings
JOIN
	(lists JOIN folders ON lists.folderId = folders.folderId) ON lists.listId = userListSettings.listId AND
	folders.folderId = ?
WHERE
	userListSettings.userId = ?");
		$updateListsRoles->execute(array($_POST['folderId'],$_POST['userId']));
		$Dbc->commit();
		if(MODE == 'removeUserFromFolder'){
			$success = true;
			$message .= 'Removed User';
		}
		$returnThis['buildFolderUsers'] = buildFolderUsers();
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'removeUserFromFolder'){
		returnData();
	}
}

function removeList(){
	//Remove yourself from a list. This is not the same as removing a user from a list. This will also remove pending shares to this list started by the current user.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_SESSION['userId'])){
			throw new Adrlist_CustomException('','$_SESSION[\'userId\'] is empty.');
		}elseif(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif(!is_numeric($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is not numeric.');
		}
		$Dbc->beginTransaction();
		//Delete the user's listRoleId for all lists in the folder.
		$deleteListRoleStmt = $Dbc->prepare("DELETE
	userListSettings
FROM
	userListSettings
WHERE
	userId = ?");
		$deleteListRoleParams = array($_SESSION['userId']);
		$deleteListRoleStmt->execute($deleteListRoleParams);
		//Check for pending shares started by the current user.
		$removePendingSharesStmt = $Dbc->prepare("DELETE FROM
	invitations
WHERE
	senderId = ? AND
	listId = ?");
		$removePendingSharesParams = array($_SESSION['userId'],$_POST['listId']);
		$removePendingSharesStmt->execute($removePendingSharesParams);
		$Dbc->commit();
		$returnThis['buildLists'] = buildLists();
		if(MODE == 'removeList'){
			$success = true;
			$message .= 'Removed List';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'removeList'){
		returnData();
	}
}

function removeUserFromList(){
	//Remove a user from a list. This is not the same as deleting a list, nor is it the same as removing yourself from a list. It will also delete any pending invitations the user made for the list.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['userId'])){
			throw new Adrlist_CustomException('','$_POST[\'userId\'] is empty.');
		}elseif(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif(!is_numeric($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is not numeric.');
		}
		//Verify the user has sufficient permission to remove a user.
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		if($listInfo === false || $listInfo['listRoleId'] < 3){
			throw new Adrlist_CustomException("Your role doesn't allow you to remove users from this list.",'');
		}
		$stmt = $Dbc->prepare("DELETE FROM
	userListSettings
WHERE
	userId = ? AND
	listId = ?");
		$stmt->execute(array($_POST['userId'], $_POST['listId']));
		//Delete any pending invitations the user has created.
		$invitedStmt = $Dbc->prepare("DELETE FROM
	invitations
WHERE
	senderId = ? AND
	listId = ? AND
	respondDate IS NULL");
		$invitedParams = array($_POST['userId'],$_POST['listId']);
		$invitedStmt->execute($invitedParams);
		$returnThis['buildListUsers'] = buildListUsers();
		if(MODE == 'removeUserFromList'){
			$success = true;
			$message .= 'Removed User';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'removeUserFromList'){
		returnData();
	}
}
	
function shareFolderStep1(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'plan\'] is empty.');
		}elseif(!is_numeric($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'plan\'] is not numeric.');
		}
		$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['folderId']);
		if($folderInfo === false || $folderInfo['folderRoleId'] < 3){//The user must be a manager or owner to share a list.
			throw new Adrlist_CustomException('You must be a manager or owner to share this folder.','');
		}
		$output .= '<div class="myAccountTitle">
	Share Folder
</div>
Enter the email address of the person you want to share this folder with.
<div class="ui-field-contain">
	<label for="shareFolderInput" unused="ui-hidden-accessible">Email</label>
	<input autocapitalize="off" autocorrect="off" autoreset="true" data-wrapper-class="true" id="shareFolderInput" goswitch="shareFolderStep2" name="shareFolderInput" placeholder="" value="" type="email">
</div>
<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-heart" id="shareFolderStep2" folderId="' . $_POST['folderId'] . '">Save</button>' . cancelButton();
		$returnThis['output'] = $output;
		if(MODE == 'shareFolderStep1'){
			$success = true;
			returnData();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
}

function shareFolderStep2(){
	//Share a folder = send an invitation.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}elseif(empty($_POST['email'])){
			throw new Adrlist_CustomException('','$_POST[\'email\'] is empty.');
		}elseif(emailValidate($_POST['email']) === false){
			throw new Adrlist_CustomException('The email address your entered is not valid.', '$_POST[\'email\'] failed the emailValidate() test.');
		}elseif($_POST['email'] == $_SESSION['primaryEmail']){
			throw new Adrlist_CustomException('Why are your trying to share the folder with yourself?','');
		}
		$_POST['email'] = trim($_POST['email']);
		$sendEmail = false;
		$Dbc->beginTransaction();
		$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['folderId']);
		//Verify the user has an appropriate folder role to add users - Manager (3) or Owner (4).
		$folderRoleId = $folderInfo['folderRoleId'];
		if(empty($folderRoleId) || $folderRoleId < 3){
			throw new Adrlist_CustomException("Your role does not allow you to add users to this folder.",'');
		}
		//See if the user already exists.
		$stmt = $Dbc->prepare("SELECT
	userId AS 'userId'
FROM
	users
WHERE
	primaryEmail = ? || secondaryEmail = ?");
		$stmt->execute(array($_POST['email'],$_POST['email']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(empty($row['userId'])){
			//The recipient does not have an account. See if the recipient already has an invitation to this folder.
			$invitationCheck = $Dbc->prepare("SELECT
	email AS 'email'
FROM
	invitations
WHERE
	email = ? AND
	folderId = ? AND
	respondDate IS NULL");
			$invitationCheck->execute(array($_POST['email'],$_POST['folderId']));
			$row = $invitationCheck->fetch(PDO::FETCH_ASSOC);
			if(empty($row['email'])){
				//There is no existing invitation. Insert an invitation record.
				$invitationCode = sha1($_POST['email'] . time());
				$stmt = $Dbc->prepare("INSERT INTO
	invitations
SET
	email = ?,
	invitationCode = ?,
	folderId = ?,
	folderRoleId = ?,
	senderId = ?,
	sentDate = ?");
				$stmt->execute(array($_POST['email'],$invitationCode,$_POST['folderId'],1,$_SESSION['userId'],DATETIME));
				$sendEmail = true;
				$subject = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared a folder with you at ' . THENAMEOFTHESITE;
				$bodyText = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the folder "' . $folderInfo['folderName']. '" with you at ' . THENAMEOFTHESITE . '. View this folder by creating an account: ' . LINKCREATEACCOUNT . '?invitationCode=' . $invitationCode . '
				

This is an automated message. Please do not reply.';
				$bodyHtml = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the folder "' . $folderInfo['folderName'] . '" with you at ' . THENAMEOFTHESITE . '.<br>
<br>
View this folder by creating an account: <a href="' . LINKCREATEACCOUNT . '?invitationCode=' . $invitationCode . '">' . LINKCREATEACCOUNT . '</a><br>';
				if(email($_SESSION['primaryEmail'],$_POST['email'],$subject,$bodyHtml,$bodyText)){
					$message .= 'You shared this folder with ' . $_POST['email'] . '.';
				}else{
					$Dbc->rollback();
					error(__LINE__,'We ran into trouble trying to send an email to the user. Please verify the email address and try sharing this folder again.');
				}
			}else{
				$message .= 'This folder has already been shared with the user at ' . $_POST['email'] . '.';
			}
		}else{
			//The user has an account. See if they have a role for the folder.
			$newUserFolderInfo = getFolderInfo($row['userId'],$_POST['folderId']);
			if(empty($newUserFolderInfo)){
				//The user does not have an existing folder role, so insert one.
				$stmt = $Dbc->prepare("INSERT INTO
	userFolderSettings
SET
	userId = ?,
	folderId = ?,
	folderRoleId = ?,
	dateAdded = ?");
				$stmt->execute(array($row['userId'],$_POST['folderId'],1,DATETIME));
				//Distribute a role to the user for the folder's lists.
				distributeRoles($_SESSION['userId'],$row['userId'],array($_POST['folderId']=>1),true);
				$sendEmail = true;
				//Email the recipient.
				$subject = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared a folder with you at ' . THENAMEOFTHESITE;
				$bodyText = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the folder "' . $folderInfo['folderName'] . '" with you at ' . THENAMEOFTHESITE . '. Log in to your account to view this folder: ' . LINKLOGIN . '


This is an automated message. Please do not reply.';
				$bodyHtml = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the folder "' . $folderInfo['folderName'] . '" with you at ' . THENAMEOFTHESITE . '. Log in to your account to view this folder: <a href="' . LINKLOGIN . '">' . LINKLOGIN . '</a><br>';
				if(email($_SESSION['primaryEmail'],$_POST['email'],$subject,$bodyHtml,$bodyText)){
					$message .= 'You shared this folder with ' . $_POST['email'] . '.';
				}else{
					$Dbc->rollback();
					error(__LINE__,'We ran into trouble trying to send an email to the user. Please verify the email address and try sharing this folder again.');
				}
			}else{
				$message .= 'The user already has a role for this folder.<br>';
			}
		}
		$Dbc->commit();
		$success = MODE == 'shareFolderStep2' ? true : $success;
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}catch(Exception $e){
		error(__LINE__,'We encountered a problem. If this persists please contact support.','<pre>' . $e . '</pre>');
	}
	if(MODE == 'shareFolderStep2'){
		returnData();
	}
}

function shareListStep1(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'plan\'] is empty.');
		}elseif(!is_numeric($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'plan\'] is not numeric.');
		}
		$_POST['listId'] = intThis($_POST['listId']);
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		$debug->printArray($listInfo,'$listInfo');
		if($listInfo === false || $listInfo['listRoleId'] < 3){//The user must be a manager or owner to share a list.
			throw new Adrlist_CustomException('You must be a manager or owner to share this list.','');
		}
		$output .= '<div class="myAccountTitle">
	Share List
</div>
Enter the email address of the person you want to share this list with.
<div class="ui-field-contain">
	<label for="shareListInput" unused="ui-hidden-accessible">Email</label>
	<input autocapitalize="off" autocorrect="off" autoreset="true" data-wrapper-class="true" id="shareListInput" goswitch="shareListStep2" name="shareListInput" placeholder="" value="" type="email">
</div>
<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-heart" id="shareListStep2" listId="' . $_POST['listId'] . '">Save</button>' . cancelButton();
		$returnThis['output'] = $output;
		if(MODE == 'shareListStep1'){
			$success = true;
			returnData();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
}

function shareListStep2(){
	//Share a list = send an invitation.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif(empty($_POST['email'])){
			throw new Adrlist_CustomException('','$_POST[\'email\'] is empty.');
		}elseif(emailValidate($_POST['email']) === false){
			throw new Adrlist_CustomException('The email address your entered is not valid.','$_POST[\'email\'] failed the emailValidate() test.');
		}elseif($_POST['email'] == $_SESSION['primaryEmail']){
			throw new Adrlist_CustomException('Why are your trying to share the folder with yourself?','$_POST[\'email\'] == $_SESSION[\'primaryEmail\'].');
		}elseif($_POST['email'] == $_SESSION['secondaryEmail']){
			throw new Adrlist_CustomException('The email address you entered is linked to your account?','$_POST[\'email\'] == $_SESSION[\'secondaryEmail\'].');
		}
		$_POST['email'] = trim($_POST['email']);
		$Dbc->beginTransaction();
		//Get the list's information.
		$currentUserListInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		//Verify the current user has an appropriate listRoleId to add users - Manager (3) or Owner (4).
		if($currentUserListInfo  === false || $currentUserListInfo ['listRoleId'] < 3){
			throw new Adrlist_CustomException("Your role does not allow you to add users to this list.",'');
		}
		//Verify the current user has a folderRoleId of at least Member (1). This is because a user may be a manager of a list, but not of it's folder.
		if($currentUserListInfo ['folderRoleId'] === 0){
			//The requesting user is implicitly denied access to a folder.
			throw new Adrlist_CustomException("Your role does not allow you to add members to this folder.",'');
		}
		//The current user has access to the folder, or the list is not in a folder.
		$folderRoleId = $currentUserListInfo ['folderId'] ? 1 : NULL;//If a folder exists, the default folderRoleId is Member (1), otherwise NULL.
		//Check if the recipient has an account.
		$userCheckStmt = $Dbc->prepare("SELECT
	userId AS 'userId'
FROM
	users
WHERE
	primaryEmail = ? OR
	secondaryEmail = ?");
		$userCheckStmt->execute(array($_POST['email'],$_POST['email']));
		$userCheckRow = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
		$subject = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared a list with you at ' . THENAMEOFTHESITE;
		if(empty($userCheckRow['userId'])){
			//The recipient does not have an account. See if they already have an invitation to this list.
			$invitationCheckStmt = $Dbc->prepare("SELECT
	email AS 'email'
FROM
	invitations
WHERE
	email = ? AND
	listId = ?");
			$invitationCheckStmt->execute(array($_POST['email'],$_POST['listId']));
			$invitationCheckRow = $invitationCheckStmt->fetch(PDO::FETCH_ASSOC);
			if(!empty($invitationCheckRow['email'])){
				throw new Adrlist_CustomException('This list has already been shared with that user.','');
			}
			//The user has no existing invitation to this list. Insert an invitation record.
			$invitationCode = sha1($_POST['email'] . time());
			$insertInvitationStmt = $Dbc->prepare("INSERT INTO
	invitations
SET
	email = ?,
	invitationCode = ?,
	folderId = ?,
	folderRoleId = ?,
	listId = ?,
	listRoleId = ?,
	senderId = ?,
	sentDate = ?");
			$insertInvitationStmt->execute(array($_POST['email'],$invitationCode,$currentUserListInfo['folderId'],$folderRoleId,$_POST['listId'],1,$_SESSION['userId'],DATETIME));
			$bodyText = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the ADR list "' . $currentUserListInfo['listName'] . '" with you at ' . THENAMEOFTHESITE . '. 
View this list by creating an account: ' . LINKCREATEACCOUNT . '
';
			$bodyHtml = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the ADR list "' . $currentUserListInfo['listName'] . '" with you at ' . THENAMEOFTHESITE . '. <br>
		<br>
			<a href="' . LINKCREATEACCOUNT . '?invitationCode=' . $invitationCode . '">View this list by creating an account here.</a><br>';
			if(email($_SESSION['primaryEmail'],$_POST['email'],$subject,$bodyHtml,$bodyText)){
				$message .= 'You shared this list with ' . $_POST['email'] . '.';
				$Dbc->commit();
				if(MODE == 'shareListStep2'){
					$success = true;
					$returnThis['buildListUsers'] = buildListUsers();
				}
			}else{
				$Dbc->rollback();
				throw new Adrlist_CustomException('','Could not send an email to the user.');
			}
		}else{
			//The recipient has an existing account.
			if($currentUserListInfo['folderId']){
				//The list is part of a folder. Check if the recipient has a role for the folder.
				$recipientFolderInfo = getFolderInfo($userCheckRow['userId'],$currentUserListInfo['folderId']);
				$recipientFolderRoleId = $recipientFolderInfo['folderRoleId'];
				if($recipientFolderRoleId === 0){
					$success = false;
					throw new Adrlist_CustomException('The user you are trying to share this list with has been implicitly denied a role for the containing folder. You must grant the user a minimum folder role of "View" before sharing this list.');
				}elseif(empty($recipientFolderRoleId)){
					//The user does not have an existing folder role, so insert one.
					$insertFolderRole = $Dbc->prepare("INSERT INTO
	userFolderSettings
SET
	folderId = ?,
	userId = ?,
	folderRoleId = ?,
	dateAdded = ?");
					$insertFolderRole->execute(array($currentUserListInfo['folderId'],$userCheckRow['userId'],1,DATETIME));
				}
			}
			//See if the recipient has a listRoleId. This is very redundant. The current user should not have been able to share this list if the recipient alreay has a list role.
			$recipientListInfo = getListInfo($userCheckRow['userId'],$_POST['listId']);
			if($recipientListInfo === false){
				//The user exists and does not have an existing role, so insert the list role. First, get the user's default list settings.
				$listSettings = getDefaultListSettings($userCheckRow['userId']);
				//Insert a list setting for this list.
				$listSettingsStmt = $Dbc->prepare("INSERT INTO
	userListSettings
SET
	userId = ?,
	listId = ?,
	listRoleId = ?,
	dateAdded = ?,
	limitCount = ?,
	orderBy = ?,
	orderDirection = ?,
	viewCharacters = ?,
	showCharacterColors = ?");
				$listSettingsStmt->execute(array($userCheckRow['userId'],$_POST['listId'],1,DATETIME,$listSettings['defaultLimit'],$listSettings['defaultOrderBy'],$listSettings['defaultOrderDirection'],'',$listSettings['defaultShowCharacterColors']));
				$bodyText = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the ADR list "' . $currentUserListInfo['listName'] . '" with you at ' . THENAMEOFTHESITE . '. Log in to your account to view this list: ' . LINKLOGIN . '
';
				$bodyHtml = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared the ADR list "' . $currentUserListInfo['listName'] . '" with you at ' . THENAMEOFTHESITE . '.<br>
<br>
Log in to your account to view this list: <a href="' . LINKLOGIN . '">' . LINKLOGIN . '</a><br<br>';
				if(email($_SESSION['primaryEmail'],$_POST['email'],$subject,$bodyHtml,$bodyText)){
					$message .= 'You shared this list with the user at ' . $_POST['email'] . '.';
					$Dbc->commit();
					if(MODE == 'shareList'){
						$success = true;
					}
				}else{
					$Dbc->rollback();
					throw new Adrlist_CustomException('We ran into trouble trying to send an email to the user. Please try again<br><br>
 If the problem persists, <a href="' . LINKSUPPORT . '" data-ajax="false">contact support</a>.','');
				}
			}elseif($recipientListInfo['listRoleId'] === 0){
				throw new Adrlist_CustomException('The user you are trying to share this list with has been implicitly denied a role. You cannot share this list with that person.','');
			}else{
				throw new Adrlist_CustomException('The user already has access to this list.','');
			}
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'shareListStep2'){
		returnData();
	}
}

function transferListStep1(){
	/*
	Builds the transfer list form.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}
		$Dbc->beginTransaction();
		//Get the list info.
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		//Verify the user has a sufficient role to transfer the list.
		if($listInfo === false || $listInfo['listRoleId'] < 4){
			throw new Adrlist_CustomException('Your role does not allow you to transfer this list.','');
		}
		//Check for a pending transfer.
		$pendingTransferStmt = $Dbc->prepare("SELECT
	tlId AS 'tlId',
	firstName AS 'firstName',
	lastName AS 'lastName',
	intendedEmail AS 'intendedEmail',
	transferListCode AS 'transferListCode',
	listId AS 'listId',
	listRoleId AS 'listRoleId',
	senderId AS 'senderId',
	sentDate AS 'sentDate',
	respondDate AS 'respondDate'
FROM
	transferList
WHERE
	listId = ?");
		$pendingTransferStmt->execute(array($_POST['listId']));
		$pendingTransfer = $pendingTransferStmt->fetch(PDO::FETCH_ASSOC);
		$debug->printArray($pendingTransfer,'$pendingTransfer');
		if(empty($pendingTransfer)){
			//Prepare to insert a record. 
		$output .= '<div class="myAccountTitle">
	Transfer "' . $listInfo['listName'] . '"
</div>
Enter the email address of the person you want to transfer this list to.
<div class="ui-field-contain">
	<label for="intendedEmail" unused="ui-hidden-accessible">Email</label>
	<input autocapitalize="off" autocorrect="off" autoreset="true" data-wrapper-class="true" id="intendedEmail" goswitch="transferListStep2" name="intendedEmail" placeholder="" value="" type="email">
</div>
<div class="ui-field-contain">
	<label for="intendedEmailRetype" unused="ui-hidden-accessible">Re-enter Email</label>
	<input autocapitalize="off" autocorrect="off" autoreset="true" data-wrapper-class="true" id="intendedEmailRetype" goswitch="transferListStep2" name="intendedEmailRetype" placeholder="" value="" type="email">
</div>
<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-action" id="transferListStep2" listId="' . $_POST['listId'] . '">Transfer</button>' . cancelButton();
			$success = true;
			$returnThis['transferListStep1'] = $output;
		}else{
			//Show the pending transfer info.
		$output .= '<div class="myAccountTitle">
	Pending Transfer for "' . $listInfo['listName'] . '"
</div>
<div class="columnParent">
	<div class="break">
		<div style="font-weight:none" class="columnLeft">
			Recipient\'s Email Address:
		</div>
		<div class="columnRight">
			' . $pendingTransfer['intendedEmail'] . '
		</div>
	</div>
	<div class="break">
		<div class="columnLeft">
			Transfer Started:
		</div>
		<div class="columnRight">
			' . Adrlist_Time::utcToLocal($pendingTransfer['sentDate']) . '
		</div>
	</div>
</div>
<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-delete" id="transferListStop" listId="' . $_POST['listId'] . '">Stop Transfer</button>' . cancelButton();
			if(MODE == 'transferListStep1'){
				$success = true;
				$returnThis['transferListStep1'] = $output;
			}
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'transferListStep1'){
		returnData();
	}
}

function transferListStep2(){
	/*
	This function behaves very much like shareList(). The user enters the email address of the intended recipient and an email is sent. Upon acceptance the list ownership is changed. This does not affect the list roles of non-owners. The list will be moved out of any containing folders.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		$emailValidate = emailValidate($_POST['intendedEmail']);
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif($emailValidate === false){
			throw new Adrlist_CustomException('The email address you entered is not valid.','$_POST[\'intendedEmailAddress\'] is not valid.');
		}elseif(empty($_POST['intendedEmail'])){
			throw new Adrlist_CustomException('','$_POST[\'intendedEmail\'] is empty.');
		}elseif(empty($_POST['intendedEmailRetype'])){
			throw new Adrlist_CustomException('','$_POST[\'intendedEmailRetype\'] is empty.');
		}elseif($_POST['intendedEmail'] != $_POST['intendedEmailRetype']){
			throw new Adrlist_CustomException('The email addresses don\'t match.','$_POST[\'intendedEmail\'] != $_POST[\'intendedEmailRetype\']');
		}elseif($_POST['intendedEmail'] == $_SESSION['primaryEmail'] || $_POST['intendedEmail'] == $_SESSION['secondaryEmail']){
			throw new Adrlist_CustomException('The email address you entered is linked to your account.','$_POST[\'intendedEmail\'] == user\'s email.');
		}
		$Dbc->beginTransaction();
		//Check for a pending transfer.
		$pendingTransferStmt = $Dbc->prepare("SELECT
	tlId AS 'tlId'
FROM
	transferList
WHERE
	listId = ?");
		$pendingTransferStmt->execute(array($_POST['listId']));
		$pendingTransferRow = $pendingTransferStmt->fetch(PDO::FETCH_ASSOC);
		if(empty($pendingTransferRow['tlId'])){
			//Verify the user has a sufficient role to transfer the list.
			$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
			$debug->printArray($listInfo,'$listInfo');
			if($listInfo === false || $listInfo['listRoleId'] < 4){
				$message .= 'Your role does not allow you to transfer this list.<br>';
			}else{
				//Insert a record of transfer.
				$transferListCode = sha1($_POST['intendedEmail'] . time());
				$insertTransferStmt = $Dbc->prepare("INSERT INTO
	transferList
SET
	intendedEmail = ?,
	transferListCode = ?,
	listId = ?,
	senderId = ?,
	sentDate = ?");
				$insertTransferParams = array($_POST['intendedEmail'],$transferListCode,$_POST['listId'],$_SESSION['userId'],DATETIME);
				$insertTransferStmt->execute($insertTransferParams);
				//Email the recipient.
				$subject = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has transferred an ADR list to you at ' . THENAMEOFTHESITE;
				$bodyText = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has transferred the ADR list "' . $listInfo['listName'] . '" to you at ' . THENAMEOFTHESITE . '. Log in to your account to view this list: ' . LINKLOGIN . '
';
				$bodyHtml = '
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE3 . '">' . $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has transferred the ADR list "' . $listInfo['listName'] . '" to you at ' . THENAMEOFTHESITE . '. Log in to your account to view this list: <a href="' . LINKLOGIN . '">' . LINKLOGIN . '</a></td>
	</tr>
</table>		
';
				if(email(EMAILDONOTREPLY,$_POST['intendedEmail'],$subject,$bodyHtml,$bodyText)){
					$message .= 'The list "' . $listInfo['listName'] . '" will be transferred to the user at ' . $_POST['intendedEmail'] . '.';
					if(MODE == 'transferListStep2'){
						$success = true;
					}
					$Dbc->commit();
				}else{
					$Dbc->rollback();
					error(__LINE__,'We ran into trouble trying to send an email to the user. Please verify the email address and try sharing this list again.');
				}
				//Email the sender.
				$subject = 'You have transferred an ADR list at' . THENAMEOFTHESITE;
				$bodyText = 'You transferred the ADR list "' . $listInfo['listName'] . '" to the user at ' . $_POST['intendedEmail'] . ' at ' . THENAMEOFTHESITE . '. Log in to your account to view this list: ' . LINKLOGIN . '
';
				$bodyHtml = '<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE3 . '">You transferred the ADR list "' . $listInfo['listName'] . '" to the user at ' . $_POST['intendedEmail'] . ' at ' . THENAMEOFTHESITE . '. Log in to your account to view this list: <a href="' . LINKLOGIN . '">' . LINKLOGIN . '</a></td>
	</tr>
</table>
';
				email($_SESSION['primaryEmail'],$_SESSION['primaryEmail'],$subject,$bodyHtml,$bodyText);
			}
		}else{
			$message .= 'There is already a transfer pending for this list.';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'transferListStep2'){
		returnData();
	}
}

function transferListStop(){
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}
		$Dbc->beginTransaction();
		//Get the list name.
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		if($listInfo === false){
			error(__LINE__);
			$debug->add('Could not get the list info.<br>');
		}elseif($listInfo['listRoleId'] < 4){//Verify the user has a sufficient role to cancel the transfer.
			$message .= 'Your role does not allow you to stop the transfer.';
		}else{
			//Get the intended recipient's email address.
			$transferInfoStmt = $Dbc->prepare("SELECT
	intendedEmail AS 'intendedEmail'
FROM
	transferList
WHERE
	listId = ?");
			$transferInfoStmt->execute(array($_POST['listId']));
			$transferInfoRow = $transferInfoStmt->fetch(PDO::FETCH_ASSOC);
			//Delete the record of transfer.
			$deleteTransferStmt = $Dbc->prepare("DELETE FROM
	transferList
WHERE
	listId = ?");
			$deleteTransferStmt->execute(array($_POST['listId']));
			$subject = 'List transfer cancelled at ' . THENAMEOFTHESITE;
			$bodyText = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has stopped the transfer of the ADR list "' . $listInfo['listName'] . '" to you at ' . THENAMEOFTHESITE . '. No response from you is necessary.
';
			$bodyHtml = '<table width="100%" cellpadding="10" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE3 . '">' . $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has stopped the transfer of the ADR list "' . $listInfo['listName'] . '" to you at ' . THENAMEOFTHESITE . '.  No response from you is necessary.</td>
	</tr>
</table>
';
			if(email(EMAILDONOTREPLY,$transferInfoRow['intendedEmail'],$subject,$bodyHtml,$bodyText)){
				$Dbc->commit();
				if(MODE == 'transferListStop'){
					$success = true;
					$message .= 'You stopped the transfer of the list "' . $listInfo['listName'] . '".';
				}
			}else{
				$Dbc->rollback();
				error(__LINE__,'We ran into trouble trying to send an email to the user. Please verify the email address and try sharing this list again.');
			}
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'transferListStop'){
		returnData();
	}
}

function unlockList(){
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		if(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif(empty($_SESSION['credits'])){
			throw new Adrlist_CustomException('You don\'t have any credits. Credits can be purchased in <a href="' . LINKMYACCOUNT . '" data-ajax="false">My Account</a>','$_SESSION[\'credits\'] is empty.');
		}
		$listInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		if($listInfo['listRoleId'] < 4){
			throw new Adrlist_CustomException('Your role does not allow you to unlock this list.','$listInfo[\'listRoleId\']: ' . $listInfo['listRoleId']);
		}
		$unlockStmt = $Dbc->prepare("UPDATE
	lists
SET
	locked = 0
WHERE
	listId = ?");
		$unlockStmt->execute(array($_POST['listId']));
		updateListHist($_POST['listId']);
		$locked = reconcileLists($_SESSION['userId']);
		if(MODE == 'unlockList'){
			$success = true;
			$returnThis['locked'] = $locked;
			$returnThis['buildLists'] = buildLists();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'unlockList'){
		returnData();
	}
}

function updateFolderRole(){
	//Update the user's folder role id. The role is also applied to all of the folder's lists.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['userId'])){
			throw new Adrlist_CustomException('','$_POST[\'userId\'] is empty.');
		}elseif(empty($_POST['folderId'])){
			throw new Adrlist_CustomException('','$_POST[\'folderId\'] is empty.');
		}elseif(!isset($_POST['newRoleId'])){//The newRoleId may be zero, so check that the value with isset() rather than empty().
			throw new Adrlist_CustomException('','$_POST[\'newRoleId\'] is not set.');
		}
		if(distributeRoles($_SESSION['userId'],$_POST['userId'],array($_POST['folderId']=>$_POST['newRoleId']),false) === true){
			$message .= 'Updated';
			$returnThis['buildFolderUsers'] = buildFolderUsers();
		}
		if($_POST['newRoleId'] < 3){
			//Delete pending shares started by this user for lists in this folder when their role is reduced below Manager (3).
			$deletePendingSharesStmt = $Dbc->prepare("DELETE FROM
		invitations
	WHERE
		senderId = ? AND
		listId IN (SELECT listId FROM lists WHERE folderId = ?)");
			$deletePendingSharesStmt->execute(array($_POST['userId'],$_POST['folderId']));
		}
		$success = MODE == 'updateFolderRole' ? true : $success;
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'updateFolderRole'){
		returnData();
	}
}

function updateListRole(){//Needs work.
	/*
	Update a user's listRoleId. We are working with two different sets of users here: the current user making the change and the user whose role is being changed.
	If the user's role is reduced to less than Manager, any pending shared list invitations will be deleted. We must also make sure that if the list is in a folder, the user has at least a member role in the folder.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['userId'])){
			throw new Adrlist_CustomException('','$_POST[\'userId\'] is empty.');
		}elseif(empty($_POST['listId'])){
			throw new Adrlist_CustomException('','$_POST[\'listId\'] is empty.');
		}elseif(!isset($_POST['newRoleId'])){//The newRoleId may be zero, so check that the value isset rather than empty.
			throw new Adrlist_CustomException('','$_POST[\'newRoleId\'] is not set.');
		}
		$Dbc->beginTransaction();
		//Verify the current user has a sufficient listRoleId.
		$currentUserListInfo = getListInfo($_SESSION['userId'],$_POST['listId']);
		$debug->printArray($currentUserListInfo,'$currentUserListInfo');
		if($currentUserListInfo === false || $currentUserListInfo['listRoleId'] < 3){
			throw new Adrlist_CustomException('Your list role does not allow you to edit this user\'s role.','');
		}
		//Update the user's list role.
		$stmt = $Dbc->prepare("UPDATE
	userListSettings
SET
	listRoleId = ?
WHERE
	userId = ? AND
	listId = ?
LIMIT 1");
		$params = array($_POST['newRoleId'],$_POST['userId'],$_POST['listId']);
		$stmt->execute($params);
		//We don't need any rows to have success.
		$rowCount = $stmt->rowCount();
		if(empty($rowCount)){
			pdoError(__LINE__, $stmt, $params, true);
		}
		//See if the list is in a folder and if the user has a folder role.
		if(!empty($currentUserListInfo['folderId']) && $_POST['newRoleId'] >= 1){
			//The list is in a folder. Make sure the user has a folder role.
			$debug->add('Inside the folder section.');
			$userListInfo = getListInfo($_POST['userId'],$_POST['listId']);//Get the list info for the user whose role is being changed.
			$folderRoleId = $userListInfo['folderRoleId'];
			if($folderRoleId === false){
				$debug->add('Adding a folder role.');
				//The user has no folder role, so insert one.
				$folderStmt = "INSERT INTO
	userFolderSettings
SET
	folderId = ?,
	userId = ?,
	folderRoleId = ?,
	dateAdded = ?";
				$folderParams = array($currentUserListInfo['folderId'],$_POST['userId'],1,DATETIME);
			}elseif($folderRoleId == 0){
				$debug->add('Updating the folder role.');
				//The user has a folder role of zero, so set it to 1.
				$folderStmt = "UPDATE
	userFolderSettings
SET
	folderRoleId = ?
WHERE
	folderId = ? AND
	userId = ?";
				$folderParams = array(1,$currentUserListInfo['folderId'],$_POST['userId']);
			}
			if(isset($folderStmt)){
				$folderStmt = $Dbc->prepare($folderStmt);
				$folderStmt->execute($folderParams);
			}
		}
		//If the user's list role is reduced to less than Manager (3) any pending shares they initiated will be deleted.
		$debug->add('$_POST[\'newRoleId\']: ' . $_POST['newRoleId'] . '');
		if($_POST['newRoleId'] < 3){
			$debug->add('Delete the pending shares.');
			//Delete pending shares started by this user for this list.
			$deletePendingSharesStmt = $Dbc->prepare("DELETE FROM
	invitations
WHERE
	senderId = ? AND
	listId = ?");
			$deletePendingSharesStmt->execute(array($_POST['userId'],$_POST['listId']));
		}
		$Dbc->commit();
		$returnThis['buildListUsers'] = buildListUsers();
		if(MODE == 'updateListRole'){
			$success = true;
			$message .= 'Updated';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'updateListRole'){
		returnData();
	}
}

function updatePendingRole(){
	//Update the pending user's role id. Invitations are handled in one database table, so one function can handle both.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['invitationId'])){
			throw new Adrlist_CustomException('','$_POST[\'invitationId\'] is empty.');
		}elseif(empty($_POST['type'])){
			throw new Adrlist_CustomException('','$_POST[\'type\'] is empty.');
		}elseif(empty($_POST['typeId'])){
			throw new Adrlist_CustomException('','$_POST[\'typeId\'] is empty.');
		}elseif(!isset($_POST['newRoleId'])){//The newRoleId may be zero, so check that the value isset rather than empty.
			throw new Adrlist_CustomException('','$_POST[\'newRoleId\'] is not set.');
		}
		if($_POST['type'] == 'list'){
			$type = 'list';
			$listInfo = getListInfo($_SESSION['userId'],$_POST['typeId']);
			$role = $listInfo['listRoleId'];
		}else{
			$type = 'folder';
			$folderInfo = getFolderInfo($_SESSION['userId'],$_POST['typeId']);
			$role = $folderInfo['folderRoleId'];
		}
		//Verify the user has a sufficient role to delete invitations.
		if(empty($role) || $role < 3){
			throw new Adrlist_CustomException('Your role does not allow you to edit this ' . $_POST['type'] . '.','');
		}
		//Update the roleId.
		$stmt = $Dbc->prepare("UPDATE
	invitations
SET
	{$type}RoleId = ?
WHERE
	invitationId = ?
LIMIT 1");
		$params = array($_POST['newRoleId'],$_POST['invitationId']);
		$stmt->execute($params);
		$rowCount = $stmt->rowCount();
		if(empty($rowCount)){
			pdoError(__LINE__, $stmt, $params, true);
		}
		//Get the id of the folder or list to pass to the buildUser functions.
		$getIdQuery = $Dbc->prepare("SELECT
	{$type}Id AS '{$type}Id'
FROM
	invitations
WHERE
	invitationId = ?");
		$getIdQuery->execute(array($_POST['invitationId']));
		$row = $getIdQuery->fetch(PDO::FETCH_ASSOC);
		if($type == 'folder'){
			$_POST['folderId'] = $row['folderId'];
			//We will not update user's role id for all of the folder's lists. That occurs when the pending user creates an account.
			$returnThis['buildUsers'] = buildFolderUsers();
		}else{
			$_POST['listId'] = $row['listId'];
			$returnThis['buildUsers'] = buildListUsers();
		}
		if(MODE == 'updatePendingRole'){
			$success = true;
			$message .= 'Updated';
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'updatePendingRole'){
		returnData();
	}
}