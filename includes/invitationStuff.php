<?php 
$fileInfo = array('fileName' => 'includes/invitationStuff.php');
$debug->newFile($fileInfo['fileName']);

function buildInvitation(){//Build the invitation section.
	global $debug, $message, $success;
	$output = '<ul class="break">
		<li class="sectionTitle">Invite a new user</li>
	</ul>
';
	//A user will only ever have a site role of None, Read, or Site Admin, which equate to Blocked, Allowed, and Site Admin.
	//See if the user is linked to any folder accounts.
	$foldersQuery = "SELECT
	folders.folderName AS 'folderName',
	folders.folderId AS 'folderId'
FROM
	folders
LEFT JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
	(userFolderSettings.folderRoleId >= '2')
WHERE
	userFolderSettings.userId = '" . $_SESSION['userId'] . "'
ORDER BY
	folders.folderName";
	if($foldersResult = mysql_query($foldersQuery)){
		if(mysql_affected_rows() == 0){
			$output .= '<span class="red">You must be a Folder Manager or Owner to send invitations.</span>';
			$debug->add("The users isn't linked to any folders.");
		}else{
			$folders = '<select id="invitationFolder">
<option value="">Select a folder:</option>';
			while($row = mysql_fetch_assoc($foldersResult)){
				$folders .= '<option value="' . $row['folderId'] . '">' . $row['folderName'] . '</option>
';
			}
			$folders .= '</select><span class="red" id="responseInvitationFolder" style="padding:0px 0px 0px 10px"></span>
';
			$output .= 'Invite someone to share your lists with. Once they create an account you\'ll see their name in the "Folders" section. The user\'s account will be linked to your folder and you can set their list roles. You can always remove the user later.
		<div style="padding:10px 0px 0px 0px">
			<div class="break">
				<div class="invitationLeft">Folder:&nbsp;</div>
				<div class="invitationRight">' . $folders . '</div>
			</div>
			<div class="break">
				<div class="invitationLeft">Folder Role:&nbsp;</div>
				<div class="invitationRight"><select id="invitationFolderRole">
						<option value="0">None</option>
						<option value="1" selected="selected">Read</option>
						<option value="3">Account Admin</option>
					</select> <img alt="" class="question top" height="16" qid="24" onClick="" src="' . LINKIMAGES . '/question.png" width="16"></div>
			</div>
			<div class="hide" id="invitationListHolder">
				<div class="break">
					<div class="invitationLeft">ADR List:&nbsp;</div>
					<div class="invitationRight" id="returnInvitationLists"></div>
				</div>
				<div class="break">
					<div class="invitationLeft">ADR List Role:&nbsp;</div>
					<div class="invitationRight">' . buildRoles('invitationListRole', 1) .  faqLink(24) . '</div>
				</div>
			</div>
			<div class="break">
				<div class="invitationLeft">Recipient\'s Email:&nbsp;</div>
				<div class="invitationRight"><input autocapitalize="off" autocorrect="off" id="invitationToAddress" type="email" size="25"> <span class="red" id="invitationToAddressResponse"></span></div>
			</div>
			<div class="break">
				<div class="invitationLeft">Message:&nbsp;</div>
				<div class="invitationRight italic">' . $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared an ADR list with you. To accept this invitation click the link below:<br>
<textarea id="invitationMessage" style="width:500px">(optional personal message here)</textarea></div>
			</div>
			<div class="break">
				<div class="invitationLeft">&nbsp;</div>
				<div class="left" id="sendInvitationButton" onClick=""><img alt="" class="link middle" height="24" src="' . LINKIMAGES . '/send.png" width="24"><span class="linkPadding middle">Send</span> <span class="middle red" id="invitationResponse" style="padding:0px 10px 0px 0px"></span></div>
				<div class="left" id="viewInvitationsTrigger" onClick=""><div id="viewInvitationsShow"><img alt="" class="link middle" height="24" src="' . LINKIMAGES . '/mailDown.png" width="24"><span class="linkPadding middle">Show Invitations</span></div><div class="hide" id="viewInvitationsHide" onClick=""><img alt="" class="link middle" height="24" src="' . LINKIMAGES . '/mailUp.png" width="24"><span class="linkPadding middle">Hide Invitations</span></div> <span class="middle red" id="viewInvitationsResponse"></span></div>
			</div>
		</div>
		<div id="viewInvitationsHolder" style="display:none"></div>';
		}
	}else{
		error(__LINE__);
		pdoError(__LINE__, $foldersQuery, '$foldersQuery');
	}
	return $output;
}

function buildInvitationLists(){//Build a folder's lists for the invitation section. The current user must be a list admin (3) or list owner (4).
	global $debug, $message, $success;
	$output = '';
	if(isset($_POST['folderId'])){
		$folderId = intval($_POST['folderId']);
		$selectFolderListsQuery = "SELECT
		lists.listId as 'listId',
		lists.listName as 'listName'
	FROM
		lists
	JOIN
		(userListSettings JOIN users ON userListSettings.userId = users.userId) ON lists.listId = userListSettings.listId AND
		userListSettings.listRoleId > 1 AND
		users.userId = '" . $_SESSION['userId'] . "'
	JOIN
		folderListMap ON lists.listId = folderListMap.listId AND
		folderListMap.folderId = '$folderId'";
		if($result = mysql_query($selectFolderListsQuery)){
			if(mysql_affected_rows() == 0){
				$message .= 'This folder has no lists.<span id="invitationList"></span><span id="invitationListRole"></span>';
				pdoError(__LINE__, $selectFolderListsQuery, '$selectFolderListsQuery', 1);
			}else{
				$output = '<select id="invitationList">
<option value=""></option>
';
				while($row = mysql_fetch_assoc($result)){
					$output .= '<option value="' . $row['listId'] . '">' . $row['listName'] . '</option>
';
				}
				$output .= '</select> (optional)
';
				$success = true;
				$returnThis['returnCode'] = $output;
			}
		}else{
			error(__LINE__);
			pdoError(__LINE__, $selectFolderListsQuery, '$selectFolderListsQuery');
		}
	}else{
		error(__LINE__);
		if(!isset($_POST['folderId'])){
			$debug->add('$_POST[\'folderId\'] is not set.');
		}else{
			$debug->add('Something else is wrong.');
		}
	}
	returnData();
}


function deleteInvitation(){
	global $debug, $message, $success;
	$output = '';
	if(isset($_POST['invitationId'])){
		$invitationId = intval($_POST['invitationId']);
		$deleteInvitationQuery = "DELETE FROM
	invitations
WHERE
	invitationId = '$invitationId'";
		if($result = mysql_query($deleteInvitationQuery)){
			if(mysql_affected_rows() == 0){
				$message .= 'You haven\'t sent any invitations.';
				pdoError(__LINE__, $deleteInvitationQuery, '$deleteInvitationQuery', 1);
			}else{
				$success = true;
				$message .= 'Deleted';
				$returnThis['returnViewInvitations'] = viewInvitations();
			}
		}else{
			error(__LINE__);
			pdoError(__LINE__, $deleteInvitationQuery, '$deleteInvitationQuery');
		}
	}else{
		error(__LINE__);
		if(!isset($_POST['invitationId'])){
			$debug->add('$_POST[\'invitationId\'] is not set.');
		}else{
			$debug->add('Something else is wrong.');
		}
	}
	returnData();
}

function sendInvitation(){//Send the invitation. Many steps are performed in this function.
	global $debug, $message, $success;
	if(isset($_POST['folderId']) && isset($_POST['invitationFolderRole']) && !empty($_POST['invitationToAddress'])){
		$folderId = intval($_POST['folderId']);
		$invitationFolderRole = intval($_POST['invitationFolderRole']);
		$toAddress = trim($_POST['invitationToAddress']);
		$invitationMessage = $_POST['invitationMessage'] != '(optional person message here)' ? trim($_POST['invitationMessage']) : '';
		$fromAddress = $_SESSION['primaryEmail'];
		$code = sha1($toAddress . TIMESTAMP);
		$link = LINKJOIN . '/?invitationCode=' . $code;
		$body = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body bgcolor="#' . COLORBLACK . '" marginheight="0" marginwidth="0" text="#000000" topmargin="0">
<table width="800" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="left">' . buildHeaderForEmail() . '</td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE5 . '"><b>You\'ve Been Invited!</b><br>
&nbsp;</font></td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE3 . '">' . $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared an ADR list with you. To accept this invitation follow the link below:<br>
Link: <a href="' . $link . '">' . $link . '</a><br>
<br>
' . $invitationMessage . '<br>
&nbsp;</font><br>
<div style="height:100px">&nbsp;</div></td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE1 . '">This message was sent to you on behalf of ' . $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . '. Your email address has not been added to any mailing lists or given to third parties.</font><br>
&nbsp;</td>
	</tr>
</table>		
</body>
</html>';
//<a href="' . LINKPRIVACY . '">Read our Privacy Policy here.</a>
		$subject = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared an ADR list with you at ' . THENAMEOFTHESITE;
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		$headers .= 'To: ' . $toAddress . "\r\n";
		$headers .= 'From: ' . $fromAddress . "\r\n";
		//$headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
		//$headers .= 'Bcc: birthdaycheck@example.com' . "\r\n";
		//$headers .= 'Reply-to: ' . $fromAddress . '\r\n';

		//Check the users table to see if the email address already exists. If it does, make the link and refresh the 'Linked Accounts' section to show the user.
		$userCheckQuery = "SELECT
	users.userId as 'userId'
FROM
	users
WHERE
	users.primaryEmail = '" . $toAddress . "'";
		if($result = mysql_query($userCheckQuery)){
			if(mysql_affected_rows() == 0){
				$debug->add("A user with the email address of $toAddress does not exist in the database.");
				//Check the 'invitations' table to see if an invitation has already been made, but not responded to. This means the recipient hasn't joined yet. If there is no existing invitation make one.
				$invitationCheckQuery = "SELECT
	invitations.email AS 'email'
FROM
	invitations
WHERE
	invitations.email = '" . $toAddress . "' AND
	invitations.senderId = '" . $_SESSION['userId'] . "' AND
	invitations.responded IS NULL";
				if(mysql_query($invitationCheckQuery)){
					if(mysql_affected_rows() == 0){
						//Insert the invitation in the database.
						if(!empty($_POST['invitationListId']) && isset($_POST['invitationListRole'])){
							$invitationListId = intval($_POST['invitationListId']);
							$invitationListRole = intval($_POST['invitationListRole']);
							$invitationInsertQuery = "INSERT INTO
	invitations
SET
	email = '$toAddress',
	invitationCode = '$code',
	folderId = '$folderId',
	folderRoleId = '$invitationFolderRole',
	listId = '$invitationListId',
	listRoleId = '$invitationListRole',
	senderId = '" . $_SESSION['userId'] . "',
	sentDate = '" . DATETIME . "'";
						}else{
							$invitationInsertQuery = "INSERT INTO
	invitations
SET
	email = '$toAddress',
	invitationCode = '$code',
	folderId = '$folderId',
	folderRoleId = '$invitationFolderRole',
	senderId = '" . $_SESSION['userId'] . "',
	sentDate = '" . DATETIME . "'";
						}
						if(mysql_query($invitationInsertQuery)){
							$lastInvitationId = mysql_insert_id();
							if(mysql_affected_rows() == 0){
								error(__LINE__);
								pdoError(__LINE__, $invitationInsertQuery, '$invitationInsertQuery', 1);
							}else{
								if(!mail($toAddress, $subject, $body, $headers)){
									error(__LINE__);
									$$debug->add('There was an error trying to send this email<br>
From Address: ' . $fromAddress . '<br>
' . "To Address: " . $toAddress . '<br>
' . "Headers: " . $headers . '<br>
' . "Subject: " . $subject . '<br>
' . "Body: " . $body . '.');
									$deleteInvitationQuery = "DELETE FROM
	invitations
WHERE
	invitationId = '$lastInvitationId'";
									if(mysql_query($deleteInvitationQuery)){
										if(mysql_affected_rows() == 0){
											pdoError(__LINE__, $deleteInvitationQuery, '$deleteInvitationQuery', 1);
										}
									$debug->add('The invitation was deleted.');
									}else{
										pdoError(__LINE__, $deleteInvitationQuery, '$deleteInvitationQuery');
									}
								}else{
									$message .= 'The invitation was sent.';
									$debug->add('An email has been sent.<br>
$body: ' . "$body.");
									$success = true;
									$returnThis['returnCode'] = buildInvitation();
								}
							}
						}else{
							error(__LINE__);
							pdoError(__LINE__, $invitationInsertQuery, '$invitationInsertQuery');
						}
					}else{
						$message .= "You've already sent an invitation to $toAddress, but the recipient has not responded yet. ";
						$debug->add("An invitation already exists for $toAddress.");
					}
				}else{
					error(__LINE__);
					pdoError(__LINE__, $invitationCheckQuery, '$invitationCheckQuery');
				}
			}else{
				$row = mysql_fetch_assoc($result);
				//A user with a matching email address exists. Check to see if the user is already linked to this folder account.
				$linkCheckQuery = "SELECT
	userFolderSettings.folderId
FROM
	userFolderSettings
JOIN
	users ON userFolderSettings.userId = users.userId AND
	userFolderSettings.folderId = '$folderId' AND
	users.userId = '" . $row['userId'] . "'";
				$debug->add('$linkCheckQuery: ' . "$linkCheckQuery.");
				if($result = mysql_query($linkCheckQuery)){
					if(mysql_affected_rows() == 0){
						//The user does not have a link to this folder account. Make a link in the database and send a different email to the newly linked user to notify him/her of the link.
						$debug->add('Zero lines were affected by the query: $linkCheckQuery.');
						$createLinkQuery = "INSERT INTO
	userFolderSettings
SET
	folderId = '$folderId',
	userId = (SELECT users.userId FROM users WHERE users.primaryEmail = '" . $toAddress . "'),
	folderRoleId = (SELECT roles.roleId FROM roles WHERE roles.role = '$invitationFolderRole')";
						if($result = mysql_query($createLinkQuery)){
							if(mysql_affected_rows() == 0){
								error(__LINE__);
								pdoError(__LINE__, $createLinkQuery, '$createLinkQuery', 1);
							}else{
								$body = '<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body bgcolor="#' . COLORBLACK . '" marginheight="0" marginwidth="0" text="#000000" topmargin="0">
<table width="800" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#FFFFFF">
	<tr>
		<td align="left">' . buildHeaderForEmail() . '</td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE3 . '">' . $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . ' has shared an ADR list with you. <a href="' . LINKLOGIN . '">Login to view the list</a>.<br>
&nbsp;</font></td>
	</tr>
	<tr>
		<td align="center"><font face="' . FONT . '" size="' . SIZE1 . '">This message was sent to you on behalf of ' . $_SESSION['firstName'] . ' ' . $_SESSION['lastName'] . '. Your email address has not been added to any mailing lists or given to third parties.<br>
&nbsp;</font></td>
	</tr>
</table>		
</body>
</html>';
// <a href="' . LINKPRIVACY . '">Read our Privacy Policy here.</a>
								if(!mail($toAddress, $subject, $body, $headers)){
									$message .= 'There was an error trying to send the email. Please try resending in a few moments. If further attempts fail please contact the webmaster. ';
									$debug->add('There was an error trying to send this email<br>
From Address: ' . $fromAddress . '<br>
' . "To Address: " . $toAddress . '.');
								}else{
									$message .= 'The invitation was sent.';
									$debug->add('An email has been sent.<br>
$toAddress: ' . "$toAddress<br>" . '
$subject: ' . "$subject<br>" . '
$headers: ' . "$headers<br>" . '
$body: ' . "$body");
									$success = true;
									$returnThis['returnBuildFolders'] = buildFolders();
									$returnThis['returnBuildInvitation'] = buildInvitation();
								}
							}
						}else{
							error(__LINE__);
							pdoError(__LINE__, $createLinkQuery, '$createLinkQuery');
						}
					}else{
						$message .= 'The user at ' . $toAddress . ' already has a link to that folder. ';	
					}
				}else{
					error(__LINE__);
					pdoError(__LINE__, $linkCheckQuery, '$linkCheckQuery');
				}
			}
		}else{
			error(__LINE__);
			pdoError(__LINE__, $userCheckQuery, '$userCheckQuery');
		}
	}else{
		error(__LINE__);
		if(empty($_POST['invitationFolder'])){
			$debug->add('$_POST[\'invitationFolder\'] is empty.');
		}elseif(!isset($_POST['invitationFolderRole'])){
			$debug->add('$_POST[\'invitationFolderRole\'] is not set.');
		}elseif(empty($_POST['invitationToAddress'])){
			$debug->add('$_POST[\'invitationToAddress\'] is empty.');
		}else{
			$debug->add('Something else is wrong.');
		}
	}
	returnData();
}

function viewInvitations(){
	global $debug, $message, $success;
	$output = '	<div class="textLeft" id="viewInvitationsReturn">';
	$class = 'rowAlt';
	$getInvitationsQuery = "SELECT
	folders.folderName AS 'folderName',
	invitations.invitationId AS 'invitationId',
	invitations.folderRoleId AS 'folderRoleId',
	invitations.email AS 'email',
	invitations.listRoleId AS 'listRoleId',
	DATE_FORMAT(invitations.sentDate, '%b %e, %Y %l:%i %p') AS 'sentDate',
	DATE_FORMAT(invitations.responded, '%b %e, %Y %l:%i %p') AS 'responded',
	lists.listName AS 'listName'
FROM
	invitations
LEFT JOIN
	lists ON lists.listId = invitations.listId
JOIN
	folders ON folders.folderId = invitations.folderId AND
	invitations.senderId = '" . $_SESSION['userId'] . "'
ORDER BY
	(SELECT userSiteSettings.folderLinksOrderBy FROM userSiteSettings WHERE userSiteSettings.userId = '" . $_SESSION['userId'] . "')";
	if($result = mysql_query($getInvitationsQuery)){
		if(mysql_affected_rows() == 0){
			$message .= 'You haven\'t sent any invitations.';
			pdoError(__LINE__, $getInvitationsQuery, '$getInvitationsQuery', 1);
		}else{
			$output .= '	<div class="break relative" style="width:100%">
		<div class="rowTitle" style="width:140px; padding-left:5px"><br>
Email</div>
		<div class="rowTitle" style="width:120px">Invited to Folder</div>
		<div class="rowTitle" style="width:80px">Folder Role</div>
		<div class="rowTitle" style="width:130px"><br>
Invited to ADR List</div>
		<div class="rowTitle" style="width:80px">List Role</div>
		<div class="rowTitle" style="width:110px"><br>
Sent</div>
		<div class="rowTitle" style="width:110px"><br>
Responded</div>
	</div>';
			while($row = mysql_fetch_assoc($result)){
				if($class == 'rowWhite'){
					$class = 'rowAlt';
				}else{
					$class = 'rowWhite';
				}
				$responded = empty($row['responded']) ? 'No response' : $row['responded'];
				$listName = empty($row['listName']) ? '&nbsp;' : $row['listName'];
				$output .= '	<div class="break relative ' . $class . '">
		<div class="row" style="width:140px; padding-left:5px"><img alt="" class="left" height="16" id="deleteInvitation' . $row['invitationId'] . '" onClick="" src="' . LINKIMAGES . '/xRed.png" width="16"> ' . breakEmail($row['email'], 16) . '</div>
		<div class="row" style="width:120px">' . $row['folderName'] . '</div>
		<div class="row" style="width:80px;">' . roles($row['folderRoleId']) . '</div>
		<div class="row" style="width:130px">' . $listName . '</div>
		<div class="row" style="width:80px">' . roles($row['listRoleId']) . '</div>
		<div class="row textSmall" style="width:110px">' . $row['sentDate'] . '</div>
		<div class="row textSmall" style="width:110px">' . $responded . '</div>
	</div>
';
			}
			$output .= '		</table>
</div>';
			$success = true;
			$returnThis['returnViewInvitations'] = $output;
		}
	}else{
		error(__LINE__);
		pdoError(__LINE__, $getInvitationsQuery, '$getInvitationsQuery');
	}
	if(MODE == 'viewInvitations'){
		returnData();
	}else{
		return $output;
	}
}
?>