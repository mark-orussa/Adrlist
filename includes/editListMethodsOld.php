<?php /*
This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
This file and it's functions are to be used solely by ../lists/editList.php in conjunction with ../js/editlist.js.

All functions are listed in alphabetical order.
*/
$fileInfo = array('fileName' => 'includes/editListMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
initializeList();
if(isset($_SESSION['listId']) && ((boolean) $_SESSION['listId'])){
	if(isset($_SESSION['listRoleId']) && $_SESSION['listRoleId'] > 0){
		if($_SESSION['listRoleId'] > 1){//List roles edit or greater.
			if(MODE == 'addComment'){
				addComment();
			}elseif(MODE == 'addLine'){
				addLine();
			}elseif(MODE == 'createNewCharacter'){
				createNewCharacter();
			}elseif(MODE == 'buildCharactersList'){
				buildCharactersList();
			}elseif(MODE == 'deleteLine'){
				deleteLine();
			}elseif(MODE == 'deleteCharacter'){
				deleteCharacter();
			}elseif(MODE == 'deleteComment'){
				deleteComment();
			}elseif(MODE == 'editCharacterPart1'){
				editCharacterPart1();
			}elseif(MODE == 'editCharacterPart2'){
				editCharacterPart2();
			}elseif(MODE == 'editLinePart1'){
				editLinePart1();
			}elseif(MODE == 'editLinePart2'){
				editLinePart2();
			}elseif(MODE == 'markCompleted'){
				markCompleted();
			}elseif(MODE == 'markUncompleted'){
				markUncompleted();
			}elseif(MODE == 'undeleteCharacter'){
				undeleteCharacter();
			}elseif(MODE == 'undeleteLine'){
				undeleteLine();
			}elseif(MODE == 'tcValidateAll'){
				tcValidateAll();
			}elseif(MODE == 'tcValidateSave'){
				tcValidateSave();	
			}
		}elseif($_SESSION['listRoleId'] <= 1){
			$debug->add('$_SESSION[\'listRoleId\'] is less than 1.');
		}
		if(MODE == 'buildViewOptions'){
			buildViewOptions();
		}
		if(MODE == 'buildComments'){
			buildComments();
		}elseif(MODE == 'buildCommentsDefault'){
			buildCommentsDefault();
		}elseif(MODE == 'buildLines'){
			buildLines();
		}elseif(MODE == 'printLinesForEngineer'){
			printLinesForEngineer();
		}elseif(MODE == 'printLinesForTalent'){
			printLinesForTalent();
		}elseif(MODE == 'printLinesPdf'){
			printLinesPdf();
		}elseif(MODE == 'setOffset'){
			setOffset();
		}elseif(MODE == 'setLimit'){
			setLimit();
		}elseif(MODE == 'saveViewOptions'){
			saveViewOptions();
		}
	}elseif($_SESSION['listRoleId'] < 1){
		$message .= 'Your list role does not allow you access.<br>';
		$debug->add('$_SESSION[\'listRoleId\'] is less than 1.');
	}
}else{
	if(!isset($_SESSION['listId'])){
		$debug->add('$_SESSION[\'listId\'] is not defined.');
	}elseif(!((boolean) $_SESSION['listId'])){
		$debug->add('$_SESSION[\'listId\'] does not contain a value.');
	}else{
		$debug->add('Something else is wrong.');	
	}
}
$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');

function addComment(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$newComment = !empty($_POST['newComment']) ? trim($_POST['newComment']) : '';
	try{
		if(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','addComments: $_POST[\'lineId\'] is empty.');
		}elseif(empty($newComment)){
			throw new Adrlist_CustomException('','addComments: $newComment is empty.');
		}
		$stmt = $Dbc->prepare("INSERT INTO
	lineComments
SET
	lineId = ?,
	userId = ?,
	lineComment = ?,
	created = ?");
		$stmt->execute(array($_POST['lineId'],$_SESSION['userId'],$newComment,DATETIME));
		updateListHist($_SESSION['listId']);
		$success = true;
		$message .= 'Added';
		$returnThis['buildComments'] = buildComments($_POST['lineId']);
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'addComment'){
		returnData();
	}
}

function addLine(){
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		if(!isset($_SESSION['listRoleId']) || !$_SESSION['listRoleId']){
			$message .= "Your role doesn't allow you to edit this list.<br>";
		}elseif(empty($_POST['char0'])){
			throw new Adrlist_CustomException('','$_POST[\'char0\'] is empty.');
		}elseif(!isset($_POST['reel'])){
			throw new Adrlist_CustomException('','$_POST[\'reel\'] is not set.');
		}elseif(!isset($_POST['scene'])){
			throw new Adrlist_CustomException('','$_POST[\'scene\'] is not set.');
		}elseif(!isset($_POST['tcIn'])){
			throw new Adrlist_CustomException('','$_POST[\'tcIn\'] is not set.');
		}elseif(!isset($_POST['tcOut'])){
			throw new Adrlist_CustomException('','$_POST[\'tcOut\'] is not set.');
		}elseif(empty($_POST['line'])){
			throw new Adrlist_CustomException('','$_POST[\'line\'] is not set.');
		}elseif(!isset($_POST['notes'])){
			throw new Adrlist_CustomException('','$_POST[\'notes\'] is not set.');
		}
		$reel = trim($_POST['reel']);
		$scene = trim($_POST['scene']);
		$tcIn = trim($_POST['tcIn']);
		$tcOut = trim($_POST['tcOut']);
		$stmt = $Dbc->prepare("INSERT INTO
	linesTable
SET
	listId = ?,
	charId = ?,
	reel = ?,
	scene = ?,
	tcIn = ?,
	tcOut = ?,
	line = ?,
	notes = ?,
	cId = ?,
	created = ?");
		//Loop through the selected characters and insert lines for each.
		for($x=0;!empty($_POST['char'.$x]);$x++){
			$stmt->execute(array($_SESSION['listId'],$_POST['char'.$x],$reel,$scene,$tcIn,$tcOut,$_POST['line'],$_POST['notes'],$_SESSION['userId'],DATETIME));
		}
		
		//Make sure the view is set to active lines when creating a new line.
		updateListHist($_SESSION['listId']);
		$message .= 'Added';
		if(MODE == 'addLine'){
			$success = true;
			$returnThis['buildLines'] = buildLines();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'addLine'){
		returnData();
	}
}

function buildAddLine(){
	global $debug, $message;
	$output = '';
	if(isset($_SESSION['listRoleId']) && $_SESSION['listRoleId'] >= 2){
		$output .= '<div class="borderBlue center textCenter ui-corner-all hide" id="addLineHolder">
	<button class="buildCharactersList ui-btn ui-btn-inline ui-corner-all ui-mini"><i class="fa fa-users"></i>Select Characters</button>' . faqLink(25,'<span class="hide buildCharactersListValidationWarning"></span>') . '
	<div id="selectedCharactersHolder" class="center textCenter"></div>
	<div class="hide center textCenter" id="createNewCharacterHolder">
		<div class="myAccountTitle">Create a New Character</div>
		<input autocapitalize="off" autocorrect="off" data-role="none" data-clear-btn="true" data-wrapper-class="center" id="createNewCharacterName" goswitch="createNewCharacterButton" name="createNewCharacterName" placeholder="Character Name" value="" type="text">
			<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-plus" id="createNewCharacterButton">Add</button><button class="ui-btn ui-btn-b ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-delete" id="createNewCharacterCancelButton">Cancel</button>
	</div>
	<div class="ui-field-contain">
		<label for="addReel" unused="ui-hidden-accessible">Reel</label>
		<input autocapitalize="off" autocorrect="off" data-mini="true" data-wrapper-class="true" id="addReel" goswitch="addLineButton" name="addReel" placeholder="" type="text" value="">
	</div>
	<div class="ui-field-contain">
		<label for="addScene" unused="ui-hidden-accessible">Scene</label>
		<input autocapitalize="off" autocorrect="off" data-mini="true" data-wrapper-class="true" id="addScene" goswitch="addLineButton" name="addReel" placeholder="" type="text" value="">
	</div>
	<div class="ui-field-contain">
		<label for="addTcIn" unused="ui-hidden-accessible">TC In</label>
		<input autocapitalize="off" autocorrect="off" class="tcValidate" data-mini="true" data-wrapper-class="true" entry="add" id="addTcIn" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" maxlength="14" name="addTcIn" otherfield="addTcOut" placeholder="" type="text" value="">
	</div>
	<button lineId="2351" class="swapTc ui-btn ui-mini ui-btn-inline ui-corner-all" entry="add"><i class="fa fa-exchange fa-lg fa-rotate-90"></i>Swap</button>
	<div class="ui-field-contain">
		<label for="addTcOut" unused="ui-hidden-accessible">TC Out</label>
		<input autocapitalize="off" autocorrect="off" class="tcValidate" data-mini="true" data-wrapper-class="true" entry="add" id="addTcOut" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" maxlength="14" name="addTcOut" otherfield="addTcIn" placeholder="" type="text" value="">
	</div>
	<div class="ui-field-contain">
		<label for="addLine" unused="ui-hidden-accessible">Line</label>
		<textarea autocapitalize="off" autocorrect="off" data-mini="true" data-wrapper-class="true" id="addLine" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" name="addLine" placeholder="" rows="5" value=""></textarea>
	</div>
	<div class="ui-field-contain">
		<label for="addNotes" unused="ui-hidden-accessible">Notes</label>
		<textarea autocapitalize="off" autocorrect="off" data-mini="false" data-wrapper-class="true" id="addNotes" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" name="addNotes" placeholder="" rows="5" value=""></textarea>
	</div>
	<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-plus" id="addLineButton">Add Line</button><button class="ui-btn ui-btn-b ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-delete" id="addLineClear">Clear</button>
</div>';
	return $output;
	}
}

function buildCharactersList(){
	//Builds a complex list of characters to select from.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	$params = array($_SESSION['listId']);
	$additionalQuery = '';
	$additionalParams = array();
	for($x=0;!empty($_POST['char'.$x]);$x++){
		$additionalQuery .= 'AND
charId != ?
';
		$params[] = $_POST['char'.$x];
	}
	$debug->printArray($_POST,'$_POST');
	$selectCharactersQuery = "SELECT
	charId AS 'charId',
	characters AS 'charName',
	charColor AS 'charColor',
	deleted AS 'deleted'
FROM
	characters
WHERE
	listId = ?";
	$selectCharactersQuery .= $additionalQuery . "ORDER BY
	characters";
	try{
		//Select all characters that are not marked as deleted.
		$stmt = $Dbc->prepare($selectCharactersQuery);
		$stmt->execute($params);
		$output .= '
<button class="createNewCharacterStep1 ui-btn ui-btn-inline ui-corner-all ui-mini" id=""><i class="fa fa-plus fa-lg"></i>Create New Character</button>
<button class="deselectAllCharacters ui-btn ui-btn-inline ui-corner-all ui-mini" id=""><i class="fa fa-times-circle-o fa-lg"></i>Deselect All</button>
<button class="selectCharactersDone ui-btn ui-btn-inline ui-corner-all ui-mini" id=""><i class="fa fa-check-square-o fa-lg"></i>I\'m Done</button>
<div class="break relative" id="selectCharacterListHolder">
';
		$charactersFound = false;
		$deletedOption = '';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$charactersFound = $row['charId'];
			$deletedColor = empty($row['deleted']) ? '' : 'background-color:#' . COLORLIGHTRED;
			if(!empty($selectedcharId) && is_numeric($selectedcharId) && $selectedcharId == $row['charId']){
				//Do something else when the character is the selected one?
			}else{
				$deleted = $row['deleted'] ? true : false;
				$tempOutput = '	<div charId="' . $row['charId'] . '" charcolor="#' . $row['charColor'] . '" class="selectCharacterContainer ui-corner-all" defaultcolor="#F1F1F1" id="selectCharacterContainer' . $row['charId'] . '" style="background-color:#' . $row['charColor'] . ';margin:.5em;width:13em;">
		<span class="hand middle';
				$tempOutput .= $deleted ? ' mustBeUndeleted' : ' selectThisCharacter';
				$tempOutput .= '" charId="' . $row['charId'] . '" defaultcolor="#000000">' . shortenText($row['charName'], 15, false, true) . '</span>
		<i class="fa fa-square-o fa-lg hand textRight ';
				$tempOutput .= $deleted ? ' mustBeUndeleted' : ' selectThisCharacter';
				$tempOutput .= '" id="selectCharacter' . $row['charId'] . '" charId="' . $row['charId'] . '" style="margin-left:2em"></i><i class="fa fa-pencil fa-lg editCharacterButton hand textRight" charId="' . $row['charId'] . '"></i><i class="fa fa-lg hand textRight fa-';
				$tempOutput .= $deleted ? 'undo mustBeUndeleted"' : 'trash-o deleteCharacter"';
				$tempOutput .= ' charId="' . $row['charId'] . '"></i>
	</div>';
				if($deleted){
					$deletedOption .= $tempOutput;
				}else{
					$output .= $tempOutput;
				}
			}
			$debug->printArray($row,'$row');
		}
		$output .= !empty($deletedOption) ? '<div class="hr2"></div><div class="red break" style="margin-top:1em;">Deleted Characters</div>' . $deletedOption . '</div>' : '';
		$output .= '</div>';
		if(empty($charactersFound)){
			$output .= '<div class="textCenter">No active characters were found. Please create a new character by clicking the button above.</div>';
			pdoError(__LINE__, $stmt, $params, true);
		}
		if(MODE == 'buildCharactersList'){
			$success = true;
			$returnThis['buildCharactersList'] = $output;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildCharactersList'){
		returnData();
	}else{
		return $output;
	}
}

function buildCharacters($selectedcharId = false, $id = '', $tabIndex = 1){
	/*
	Returns a simple drop down list of characters.
	$selected = (optional) numeric charId to set the selected option.
	$id = (optional) id for the select tag.
	$tabIndex = (optional) tabindex.
	*/
	global $debug, $message, $Dbc;
	$output = '';
	try{
		$selectCharactersQuery = "SELECT
	charId AS 'charId',
	characters AS 'charName',
	charColor AS 'charColor',
	deleted AS 'deleted'
FROM
	characters
WHERE
	listId = ?";
		$selectCharactersQuery .= "ORDER BY
	characters";
		//Select all characters that are not marked as deleted.
		$stmt = $Dbc->prepare($selectCharactersQuery);
		$params = array($_SESSION['listId']);
		$stmt->execute($params);
		$output .= '<div class="ui-field-contain">
		';
		$output .= empty($id) ? '<label for="characterSelect" class="select">Character</label><select name="characterSelect" id="characterSelect" data-mini="true" data-inline="true" tabindex="' . $tabIndex . '">' : '<label for="' . $id . '" class="select">Character</label><select name="' . $id . '" id="' . $id . '" data-mini="true" data-inline="true" tabindex="' . $tabIndex . '" data-wrapper-class="center textCenter"><optgroup label="Active Characters">';
		$deletedOption = '';
		$foundRows = false;
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$debug->printArray($row,'$row');
			$foundRows = true;
			if(!empty($selectedcharId) && is_numeric($selectedcharId) && $selectedcharId == $row['charId']){
					$thisOption = '<option selected="selected" value="' . $row['charId'] . '" style="background-color:#' . $row['charColor'] . '">' . $row['charName'] . '</option>';
				if(empty($row['deleted'])){
					$output .= $thisOption;
				}else{
					$deletedOption .= $thisOption;
				}
			}else{
				$thisOption = '<option value="' . $row['charId'] . '" style="background-color:#' . $row['charColor'] . '">' . shortenText($row['charName'], 15, false, true) . '</option>';
				if(empty($row['deleted'])){
					$output .= $thisOption;
				}else{
					$deletedOption .= $thisOption;
				}
			}
		}
		$output .= '</optgroup>';
		$output .= !empty($deletedOption) ? '<optgroup label="Deleted Characters" style="background-color:#' . COLORLIGHTRED . '">' . $deletedOption . '</optgroup>' : '';
		$output .= '</select></div>';
		if(!$foundRows){
			$message .= 'No characters were found.';
			pdoError(__LINE__, $stmt, $params, true);
		}
		$output .= '</select>';
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	return $output;
}

function buildComments($lineId = false){
	global $debug,$message,$success,$Dbc,$returnThis;
	$output = '';
	try{
		if(empty($lineId)){
			if(isset($_POST['lineId']) && !is_numeric($_POST['lineId'])){
				throw new Adrlist_CustomException('','$_POST[\'lineId\'] is not set or not numeric.');
			}else{
				$lineId = intThis($_POST['lineId']);
			}
		}else{
			$lineId = intThis($_POST['lineId']);
		}
		$stmt = $Dbc->prepare("SELECT
	lineComments.commentId AS 'commentId',
	lineComments.lineComment AS 'comment',
	lineComments.created AS 'created',
	users.userId AS 'userId',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName)) as 'user'
FROM
	lineComments
JOIN
	users ON users.userId = lineComments.userId AND
	lineId = ?
ORDER BY
	lineComments.created ASC");
		$params = array($lineId);
		$stmt->execute($params);
		$comments = '';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$comments .= '<div>
	<span style="color:#00BCDC;margin:0px .7em 0px 0px">' . $row['user'] . '</span>' . $row['comment'];
			if($row['userId'] == $_SESSION['userId']){
				$comments .= '	<div class="deleteComment right" commentId="' . $row['commentId'] . '" lineId="' . $lineId . '" style="padding:3px 0px 3px 3px">
		<i class="fa fa-times fa-lg red"></i>
	</div>';
			}
			$comments .= '	<div class="textXsmall">' . Adrlist_Time::utcToLocal($row['created']) . '</div>
</div>
<div class="hr1"></div>';
		}
		$addNewComment = '<div class="relative" id="newCommentHolder' . $lineId . '" style="background-color:inherit">
	<textarea id="newComment' . $lineId . '" lineid="' . $lineId . '" rows="1" style="">Type comment here...</textarea><button class="addComment hide ui-btn ui-btn-inline ui-corner-all ui-mini" lineId="' . $lineId . '">Add comment</button>
</div>';
		if(empty($comments)){
			$output .= 'No comments.' . $addNewComment;
			pdoError(__LINE__,$stmt,$params,1);
		}else{
			$output .= '<div class="comments" id="comments' . $lineId . '">
	' . $comments . '
</div>
' . $addNewComment;
		}
		$output = '<div id="commentMessageHolder' . $lineId . '"></div>' . $output;
		if(MODE == 'buildComments'){
			$success = true;
			$returnThis['buildComments'] = $output;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildComments'){
		returnData();
	}else{
		return $output;
	}
}

function buildCommentsForPrint($lineId = false){
	global $debug,$message,$success,$Dbc,$returnThis;
	$output = '';
	try{
		if(!empty($_POST['lineId'])){
			$lineId = $_POST['lineId'];
		}
		if(empty($lineId)){
			throw new Adrlist_CustomException('','$lineId is empty.');
		}elseif(!is_numeric($lineId)){
			throw new Adrlist_CustomException('','$lineId is not an integer.');
		}
		$lineId = (int)$lineId;
		$stmt = $Dbc->prepare("SELECT
	lineComments.commentId AS 'commentId',
	lineComments.lineComment AS 'comment',
	lineComments.created AS 'created',
	users.userId AS 'userId',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName)) as 'user'
FROM
	lineComments
JOIN
	users ON users.userId = lineComments.userId AND
	lineId = ?
ORDER BY
	lineComments.created ASC");
		$params = array($lineId);
		$stmt->execute($params);
		$comments = '';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$comments .= '<span style="color:#00BCDC">' . $row['user'] . '</span>' . $row['comment'];
			$comments .= '<div class="textXsmall">' . Adrlist_Time::utcToLocal($row['created']) . '</div>';
		}
		if(empty($comments)){
			$output .= 'No comments.';
			pdoError(__LINE__,$stmt,$params,1);
		}else{
			$output .= $comments;
		}
		if(MODE == 'buildCommentsForPrint'){
			$success = true;
			$returnThis['buildCommentsForPrint'] = $output;
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildCommentsForPrint'){
		returnData();
	}else{
		return $output;
	}
}

function buildCommentsDefault($lineId = NULL){
	/*
	Build the default comment section.
	$lineId = (integer) the line id of the line.
	Returns "View Comments" if the line has a comment, otherwise "Add a comment."
	*/
	global $debug,$message,$success,$Dbc,$returnThis;
	$output = '';
	if(empty($lineId)){
		$lineId = empty($_POST['lineId']) ? error(__LINE__,'','$lineId is empty.') : $_POST['lineId'];
	}
	if(!is_numeric($lineId)){
		error(__LINE__,'','$lineId is not numeric.');
	}else{
		$output .= '
				<div class="textCenter" id="commentsShow" lineid="' . $lineId . '">
					<img alt="" class="middle" src="' . LINKIMAGES . '/downArrows.png" style="height:10px;width:10px"><span class="linkPadding" style="color:#BBB;">View Comments</span><img alt="" class="middle" src="' . LINKIMAGES . '/downArrows.png" style="height:10px;width:10px">
				</div>
';
		$success = MODE == 'buildCommentsDefault' ? true : $success;
		$returnThis['buildCommentsDefault'] = $output;
	}
	if(MODE == 'buildCommentsDefault'){
		returnData();
	}else{
		return $output;
	}
}

function buildLines(){
	global $debug,$message,$success,$Dbc,$returnThis;
	try{
		$output = '';
		$lines = '	<div id="editLineHolder" class="editLineHolder" style="display:none"></div>
';
		//Get the ADR lines according to the user's preferences.
		$linesStmt = "SELECT
	characters.charName AS 'charName',
	characters.charColor AS 'charColor',
	linesTable.charId AS 'charId',
	linesTable.lineId AS 'lineId',
	linesTable.reel AS 'reel',
	linesTable.scene AS 'scene',
	linesTable.tcIn AS 'tcIn',
	linesTable.tcOut AS 'tcOut',
	linesTable.line AS 'line',
	linesTable.notes AS 'notes',
	linesTable.cId AS 'cId',
	linesTable.created AS 'created',
	linesTable.mId AS 'mId',
	linesTable.modified AS 'modified',
	linesTable.dId AS 'dId',
	linesTable.deleted AS 'deleted',
	linesTable.comId AS 'comId',
	linesTable.completed AS 'completed'
FROM
	linesTable
JOIN
	characters ON characters.charId = linesTable.charId AND
	linesTable.listId = ?";
		$linesStmtParams = array($_SESSION['listId']);
		//Get the line count for use in pagination.
		$countStmt = "SELECT
	COUNT(*) AS 'count'
FROM
	linesTable
JOIN
	characters ON characters.charId = linesTable.charId
WHERE linesTable.listId = ?";
		$countStmtParams = array($_SESSION['listId']);
		$deletedQuery = empty($_SESSION['showDeletedLines']) ? " AND linesTable.deleted IS NULL" : '';
		$completedQuery = empty($_SESSION['showCompletedLines']) ? " AND linesTable.completed IS NULL" : '';
		$linesStmt .= $deletedQuery . $completedQuery;
		$countStmt .= $deletedQuery . $completedQuery;
		//View reels.
		if($_SESSION['viewReels'] != 'viewAll'){
			$lineReelsStmt = " AND
	linesTable.reel IN (";
			//Loop through the user's selected reels.
			$lineReelsParams = array();
			$viewReelsStmtShortLoop = false;
			$userReelsArray = explode(' ',$_SESSION['viewReels']);//An array of reel ids.
			//$debug->printArray($userReelsArray, '$userReelsArray in build lines.');
			foreach($userReelsArray as $key => $value){
				$lineReelsStmt .= $viewReelsStmtShortLoop ? ', ?' : '?';
				$lineReelsParams[] = $value;
				$viewReelsStmtShortLoop = true;
				//$debug->add('$lineReelsParams in loop: ' . "$value.");
			}
			$lineReelsStmt .= ')';
		}else{
			$lineReelsStmt = false;
			$lineReelsParams = false;
		}
		if($lineReelsStmt !== false){
			$linesStmt .= $lineReelsStmt;
			if($lineReelsParams !== false && is_array($lineReelsParams)){
				$linesStmtParams = array_merge($linesStmtParams,$lineReelsParams);
				$countStmtParams = array_merge($countStmtParams,$lineReelsParams);
			}
			$countStmt .= $lineReelsStmt;
		}
		//Loop through the user's selected characters.
		if(empty($_SESSION['viewCharacters']) && empty($userViewCharactersArray[0])){
			//View all characters.
			$viewCharactersLoopStmt = false;
			$viewCharactersLoopParams = false;
		}else{
			//There is one or more characters specifically selected.
			$viewCharactersLoopStmt = " AND
	linesTable.charId IN (";
			$viewCharactersShortLoop = false;
			$viewCharactersLoopParams = array();
			$userViewCharactersArray = explode(' ',$_SESSION['viewCharacters']);//An array of character Ids.
			foreach($userViewCharactersArray as $key => $value){
				$viewCharactersLoopStmt .= empty($viewCharactersShortLoop) ? '?': ', ?';
				$viewCharactersLoopParams[] = $value;
				$viewCharactersShortLoop = true;
			}
			$viewCharactersLoopStmt .= ")";
		}
		if($viewCharactersLoopStmt !== false){
			$linesStmt .= $viewCharactersLoopStmt;
			$countStmt .= $viewCharactersLoopStmt;
			if($viewCharactersLoopParams !== false && is_array($viewCharactersLoopParams)){
				$linesStmtParams = array_merge($linesStmtParams,$viewCharactersLoopParams);
				$countStmtParams = array_merge($countStmtParams,$viewCharactersLoopParams);
			}
		}
		//Search values.
		if(empty($_POST['searchVal'])){
			$search = false;
		}else{
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$debug->add('$searchval: ' . $searchVal);
			$linesStmt .= "
WHERE
	(characters.charName LIKE ? || linesTable.line LIKE ? || linesTable.notes LIKE ?)";
			$countStmt .= "
AND
	(characters.charName LIKE ? || linesTable.line LIKE ? || linesTable.notes LIKE ?)";
			$linesStmtParams = array_merge($linesStmtParams,array($searchVal,$searchVal,$searchVal));
			$countStmtParams = array_merge($countStmtParams,array($searchVal,$searchVal,$searchVal));
			$_SESSION['offset'] = 0;
		}

		//Order by options.
		$userOrderByArray = explode(' ',$_SESSION['orderBy']);//An array of order by options.
		//$debug->printArray($userOrderByArray,'$userOrderByArray in build lines.');
		$tempOrderByArray = array('tcIn' => 'TC In',
	'character' => 'Character',
	'completed' => 'Completed',
	'deleted' => 'Deleted',
	'reel' => 'Reel',
	'scene' => 'Scene',
	'tcOut' => 'TC Out',
	'createdDate' => 'Created Date',
	'modifiedDate' => 'Modified Date',
	'deletedDate' => 'Deleted Date'
);
		$orderByArray = array();
		foreach($userOrderByArray as $key => $value){
			$orderByArray[$tempOrderByArray[$value]] = $value;
		}
		//$debug->printArray($orderByArray,'$orderByArray in build lines.');
		$invertedOrderDirection = $_SESSION['orderDirection'] == 'ASC' ? 'DESC' : 'ASC';
		$orderByLoopStmt = "
	ORDER BY ";
		$orderByLoopCount = 1;
		$orderByOptionsCount = count($orderByArray);
		foreach($orderByArray as $key => $value){
			$orderByLoopDivider = $orderByLoopCount < $orderByOptionsCount ? ', ':'';
			$orderByLoopStmt .= $value == 'tcIn' ? "LENGTH(linesTable.tcIn) " . $_SESSION['orderDirection'] . ", linesTable.tcIn " . $_SESSION['orderDirection'] . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'reel' ? "LENGTH(linesTable.reel) " . $_SESSION['orderDirection'] . ", linesTable.reel " . $_SESSION['orderDirection'] . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'scene' ? "LENGTH(linesTable.scene) " . $_SESSION['orderDirection'] . ", linesTable.scene " . $_SESSION['orderDirection'] . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'character' ? "characters.charName " . $_SESSION['orderDirection'] . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'completed' ? "linesTable.completed " . $invertedOrderDirection . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'createdDate' ? "linesTable.created " . $_SESSION['orderDirection'] . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'modifiedDate' ? "linesTable.modified " . $_SESSION['orderDirection'] . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'deletedDate' ? "linesTable.deleted " . $invertedOrderDirection . $orderByLoopDivider:'';
			$orderByLoopStmt .= $value == 'tcOut' ? "LENGTH(linesTable.tcOut) " . $_SESSION['orderDirection'] . ", linesTable.tcOut " . $_SESSION['orderDirection'] . $orderByLoopDivider:'';
			$orderByLoopCount++;
		}
		$linesStmt .= $orderByLoopStmt;
		//$debug->add('$countStmt: ' . "$countStmt.");
		//$debug->printArray($countStmtParams,'$countStmtParams');
		$countStmt = $Dbc->prepare($countStmt);
		$countStmt->execute($countStmtParams);
		$result = $countStmt->fetch(PDO::FETCH_ASSOC);
		$itemCount = $result['count'];
		//$debug->add('$itemCount: ' . "$itemCount.");
		//Get the pagination info. The pagination for lines does not use the pagination database table. The values are stored in the userListSettings table.
		if(isset($_POST['offset'])){
			$offset = $_POST['offset'];
			//Update the offset.
			$stmt = $Dbc->prepare("UPDATE
	userListSettings
SET
	listOffset = ?
WHERE
	userId = ? AND
	listId = ?");
			$params = array($offset,$_SESSION['userId'],$_SESSION['listId']);
			$stmt->execute($params);
			$debug->add('Just updated the offset to: ' . $offset);
		}else{
			$offset = $_SESSION['offset'];
		}
		$offset = $offset > $itemCount ? 0 : $offset;
		if(isset($_POST['limit']) && $_POST['limit'] != $_SESSION['limit']){
			$limit = $_POST['limit'] == 0 ? $itemCount : $_POST['limit'];
			$offset = 0;//We must reset the offset when changing the limit.
			$stmt = $Dbc->prepare("UPDATE
	userListSettings
SET
	listOffset = ?,
	limitCount = ?
WHERE
	userId = ? AND
	listId = ?");
			$params = array($offset,$limit,$_SESSION['userId'],$_SESSION['listId']);
			$stmt->execute($params);
			$debug->add('Just updated the offset and limit to: ' . $offset . ', ' . $limit);
		}else{
			$limit = $_SESSION['limit'];
		}
		//Limit the lines shown.
		$linesStmt .= " LIMIT $offset, $limit";
		$debug->add('$linesStmt:<br>' . "$linesStmt.");
		$debug->printArray($linesStmtParams,'$linesStmtParams');
		$linesStmt = $Dbc->prepare($linesStmt);
		$linesStmt->execute($linesStmtParams);
		$created = '';
		$modified = '';
		$deleted = '';
		$bgColor = COLORGRAY;
		$lineNumbering = $offset+1;
		$start = microtime(true);
		//Get the created and modified dates and user for each line.
		$selectUsersStmt = $Dbc->prepare("SELECT
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'creator',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'modifier',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'deleter',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'recorder'
FROM
	linesTable
WHERE
	linesTable.lineId = ?");
		$success = true;
		$foundLines = false;
		while($row = $linesStmt->fetch(PDO::FETCH_ASSOC)){
			$foundLines = true;
			if(((boolean) $_SESSION['showCharacterColors'])){
				$bgColor = $row['charColor'];
			}else{
				if($bgColor == COLORGRAY){
					$bgColor = 'FFFFFF';
				}else{
					$bgColor = COLORGRAY;
				}
			}
			$lineId = $row['lineId'];
			$selectUsersParams = array($row['cId'],$row['mId'],$row['dId'],$row['comId'],$lineId);
			$selectUsersStmt->execute($selectUsersParams);
			$usersRow = $selectUsersStmt->fetch(PDO::FETCH_ASSOC);
			$creator = $usersRow['creator'];
			$modifier = empty($usersRow['modifier']) ? '' : $usersRow['modifier'];
			$deleter = empty($usersRow['deleter']) ? '' : $usersRow['deleter'];
			$recorder = empty($usersRow['recorder']) ? '' : $usersRow['recorder'];
			//$debug->printArray($usersRow,'$usersRow');
			if(empty($row['deleted']) || $row['deleted'] == '0000-00-00 00:00:00'){
				$deleted = false;
			}else{
				$deleted = '<i class="fa fa-circle" style="font-size:smaller"></i><span class="lineTechBit">deleted by ' . $deleter . ' on ' . Adrlist_Time::utcToLocal($row['deleted']) . '</span>';
			}
			if(empty($row['completed']) || $row['completed'] == '0000-00-00 00:00:00'){
				$completed = false;
			}else{
				$completed = '<i class="fa fa-circle" style="font-size:smaller"></i><span class="lineTechBit">completed by ' . $recorder . ' on ' . Adrlist_Time::utcToLocal($row['completed']) . '</span>';
			}

			$lines .= '		<div id="lineHolder' . $lineId . '" lineid="' . $lineId . '">
				<table class="lineMain ui-corner-all">
					<tr>
						<td class="ui-corner-top" style="background-color:#' . $bgColor . '">
							<div class="lineCount left" style="margin-left:.5em">' . $lineNumbering . '</div>
							<div class="lineCount right" style="margin-right:.5em">Line #' . $lineId . '</div>
							<div class="textCenter">
								<span class="hide" id="charId">' . $row['charId'] . '</span><span id="character' . $lineId . '" style="font-family: Courier,Courier New,monospace;">' . strtoupper($row['charName']) . '</span> ' . copyValue($lineId,'addCharacter',$row['charId']) . '
							</div>
							<div class="lineCell" id="line' . $lineId . '">
								<div class="textLeft">
									' . nl2br($row['line'], 1) . copyValue($lineId,'addLine',$row['line']) . '
								</div>
							</div>
							<div>
						</td>
					</tr>
					<tr>
						<td class="lineButtonsColumn ui-corner-bottom" style="background-color:#' . $bgColor . '">
							<div class="lineTech">
								<span class="lineTechBit">Reel: <span id="reel' . $lineId . '">' . $row['reel'] . '</span> ' . copyValue($lineId,'addReel',$row['reel']) . '</span>
								<span class="lineTechBit">Scene: <span id="scene' . $lineId . '">' . $row['scene'] . '</span> ' . copyValue($lineId,'addScene',$row['scene']) . '</span>
								<span class="lineTechBit">TC In: <span id="tcIn' . $lineId . '">' . $row['tcIn'] . '</span> ' . copyValue($lineId,'addTcIn',$row['tcIn']) . '</span>
								<span class="lineTechBit">TC Out: <span id="tcOut' . $lineId . '">' . $row['tcOut'] . '</span> ' . copyValue($lineId,'addTcOut',$row['tcOut']) . '</span>
								<div class="lineHistory">';
			$lines .= empty($creator) ? '' : '<span class="lineTechBit">created by ' . $creator;
			$lines .= empty($row['created']) ? '</span>' : ' on ' . Adrlist_Time::utcToLocal($row['created']) . '</span>';
			if(!empty($modifier) && !empty($row['modified'])){
				$lines .= '<i class="fa fa-circle" style="font-size:smaller"></i><span class="lineTechBit">modified by ' . $modifier . ' on ' . Adrlist_Time::utcToLocal($row['modified']) . '</span>';
			}
			$lines .= $deleted . $completed . '
							</div>
						</div>';
			if(isset($_SESSION['listRoleId']) && $_SESSION['listRoleId'] > 1){
				$lines .= '<button class="editLineButton ui-btn ui-mini ui-btn-inline ui-corner-all" lineId="' . $lineId . '"><i class="fa fa-edit fa-lg"></i>Edit</button>';
				if($deleted){
					$lines .= '<button class="undeleteLineButton ui-btn ui-mini ui-btn-inline ui-corner-all" lineid="' . $lineId .'" style="background-color:#FF7070"><i class="fa fa-undo fa-lg"></i>Undelete</button>';
				}else{
					$lines .= '<button class="deleteLineButton ui-btn ui-mini ui-btn-inline ui-corner-all" lineId="' . $lineId . '"><i class="fa fa-trash-o fa-lg"></i>Delete</button>';
				}
				if($completed){
					$lines .= '<button class="checkbox completed ui-btn ui-mini ui-btn-inline ui-corner-all" lineId="' . $lineId . '" style="background-color:#B1FF99"><i class="fa fa-check-square-o fa-lg"></i>Completed</button>';
				}else{
					$lines .= '<button class="checkbox uncompleted ui-btn ui-mini ui-btn-inline ui-corner-all" lineId="' . $lineId . '"><i class="fa fa-square-o fa-lg"></i>Completed</button>';
				}
			}
			if(!empty($_SESSION['listRoleId']) && $_SESSION['listRoleId'] > 0){
				$lines .= '<button class="commentsToggle ui-btn ui-icon-carat-r ui-btn-icon-right ui-btn-inline ui-corner-all ui-mini" lineId="' . $lineId . '" toggle="commentsHolder' . $lineId . '""><i class="fa fa-comments-o" ></i>Comments</button>
					<div class="hide" id="commentsHolder' . $lineId . '"></div>';
			}
			$lines .= '							<div><span id="validationWarning' . $lineId . '"></span></div>
							<div class="lineCell4" id="notes' . $lineId . '">Notes: ' . nl2br($row['notes'], 1) . copyValue($lineId,'addNotes',$row['notes']) . '</div>
						</td>
					</tr>
				</table>
				<div class="relative" id="editLineHolderAfterThis' . $lineId . '"></div>
			</div>
		';
			$lineNumbering++;
		}
		if(empty($foundLines)){
			$lines .= '<div class="textCenter" style="padding:3em">
			<div class="red" style="margin:1em">';
			$lines .= $search ? 'No matches were found.' : 'No lines match your viewing options.';
			$lines .= '</div>
Are you expecting to see lines? Make sure your view options are set correctly.</div>';
			pdoError(__LINE__,$linesStmt, $linesStmtParams, true);
		}
		//Build pagination. This is a numerical listing showing up to five preceeding and trailing pages, plus first and last pages.
		$pagination = new Adrlist_Pagination('buildLines','buildLines',$itemCount,'Search Lines',$search,array($offset,$limit));
		$pagination = $pagination->output();
		$output = buildViewOptions() . $pagination . $lines;
		if(MODE == 'buildLines'){
			$success = true;
			$returnThis['holder'] = 'buildLinesHolder';
			$returnThis['output'] = $output;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildLines'){
		returnData();
	}else{
		return $output;
	}
}

function buildEditListHeader(){
	global $debug, $message, $Dbc;
	$output = '<div class="center textCenter">
	Working Title: <span class="bold textLarge" id="listName">' . $_SESSION['listName'] . '</span>
	<button class="ui-btn ui-icon-carat-r ui-btn-icon-right ui-corner-all ui-btn-inline ui-mini" toggle="addLineHolder"><i class="fa fa-plus"></i>Add Line</button>
	<button class="ui-btn ui-corner-all ui-btn-inline ui-mini" id="buildAdvancedViewOptions"><i class="fa fa-cogs"></i>Advanced Options</button>
</div>';
	return $output;
}

function buildViewOptions(){
	/*
	Build the advanced view options.
	If MODE == 'buildViewOptions' advanced view options will be returned, otherwise the basic view options.	
	Database columns and what are acceptable values:
	orderBy = char(86) tcIn reel scene character completed deleted createdDate modifiedDate deletedDate tcOut
	orderDirection = char(4) ASC
	viewReels = varchar(255) viewAll. An empty value '' indicates no reel. 0 is a valid reel. If the user wanted to view three reels the format would be 1 3 4, identifying three reels.
	viewCharacters = text. There is no default allowed for text columns. If the user wanted to view three characters the format would be 23 45 35, identifying three characters. An empty value '' implies view all characters.
		
	$userOrderByArray = a numeric array of the user's order by options.
	$orderByArray = an associative array of the user's order by options associated with their visible title equivalents.
	
	$viewCharacters = a nested array of the characters in the list with the charId as the key:
	array (
		'David' => 
		array (
			'charId' => '188',
			'charColor' => 'BFE1FF',
			'activeLinesCount' => '2',
			'totalLinesCount' => '2',
			'deletedLinesCount' => 0,
			'completedLinesCount' => 0
		),...
	
	$userViewCharactersArray = the characters selected for the current list:
	array (
	  0 => '188',
	  1 => '190',
	)
	
	$userReelsArray = the reels selected for the current list:
	array (
	  0 => '1',
	  1 => '2',
	)
	
	$viewReelsArray = nested arrays showing the line count for each reel.
	array (
	  0 => 
	  array (
		'reel' => '',
		'count' => '2',
	  ),
	  1 => 
	  array (
		'reel' => '1',
		'count' => '10',
	  ),...
	*/
	global $debug,$message,$success,$Dbc,$returnThis;
	//Build the view options.
	try{
		if(!isset($_SESSION['userId'])){
			throw new Adrlist_CustomException('','$_SESSION[\'userId\'] is not set.');
		}elseif(!is_numeric($_SESSION['userId'])){
			throw new Adrlist_CustomException('','$_SESSION[\'userId\'] is not numeric.');
		}
		//Order by options.
		$userOrderByArray = explode(' ',$_SESSION['orderBy']);//An array of order by options.
		//$debug->printArray($userOrderByArray,'$userOrderByArray in build view options.');
		$tempOrderByArray = array('tcIn' => 'TC In',
	'character' => 'Character',
	'deleted' => 'Deleted',
	'completed' => 'Completed',
	'reel' => 'Reel',
	'scene' => 'Scene',
	'tcOut' => 'TC Out',
	'createdDate' => 'Created Date',
	'modifiedDate' => 'Modified Date',
	'deletedDate' => 'Deleted Date'
);
		$orderByArray = array();
		foreach($userOrderByArray as $key => $value){
			$orderByArray[$tempOrderByArray[$value]] = $value;
		}
		//$debug->printArray($orderByArray,'$orderByArray in build view options.');
		$orderByOptions = '	<div class="textLeft ui-field-contain" style="margin-bottom:.5em;margin-top:.5em">
		<div class="textLeft">Order By</div>
';
		$orderByOptionsCount = count($orderByArray);
		$orderByLoopCount = 1;
		$class = 'FFFFFF';
		$super = 0;
		foreach($orderByArray as $key => $value){
			$super++;
			if($class == 'rowWhite'){
				$class = 'rowAlt';
			}else{
				$class = 'rowWhite';
			}
			$orderByOptionFirst = $orderByLoopCount == 1 ? true : false;
			$orderByOptionLast = $orderByLoopCount == $orderByOptionsCount ? true : false;
			if($orderByOptionFirst){
				$orderByOptions .= '		<div id="orderByOptionsFirstRow" class="' . $class . '">
			<div class="inline-block viewOptionsItem" id="orderByOptionsFirstUpArrows" style="width:38px"></div>
			<div class="inline-block orderByOption textCenter" id="orderByOptionsFirstValue" style="width:10em;vertical-align:middle" value="' . $value . '">
				' . $key . '
			</div>
			<div class="inline-block viewOptionsItem" id="orderByOptionsFirstDownArrows">
				<img class="arrowDownOne" src="' . LINKIMAGES . '/arrowDown.png" onClick=""><img class="arrowBottom" src="' . LINKIMAGES . '/arrowBottom.png" onClick="">
			</div>';
			}elseif($orderByOptionLast){
				$orderByOptions .= '		<div id="orderByOptionsLastRow" class="' . $class . '">
			<div class="inline-block viewOptionsItem" id="orderByOptionsLastUpArrows">
				<img class="arrowTop" src="' . LINKIMAGES . '/arrowTop.png" onClick=""><img class="arrowUpOne" src="' . LINKIMAGES . '/arrowUp.png" onClick="">
			</div>
			<div class="inline-block orderByOption textCenter" id="orderByOptionsLastValue" style="width:10em;vertical-align:middle" value="' . $value . '">
				' . $key . '
			</div>
			<div class="inline-block viewOptionsItem" id="orderByOptionsLastDownArrows" style="width:38px">
			</div>';
			}else{
				$orderByOptions .= '		<div class="' . $class . '">
			<div class="inline-block viewOptionsItem">
				<img class="arrowTop" src="' . LINKIMAGES . '/arrowTop.png" onClick=""><img class="arrowUpOne" src="' . LINKIMAGES . '/arrowUp.png" onClick="">
			</div>
			<div class="inline-block orderByOption textCenter" style="width:10em" value="' . $value . '">
				' . $key . '
			</div>
			<div class="inline-block viewOptionsItem">
				<img class="arrowDownOne" src="' . LINKIMAGES . '/arrowDown.png" onClick=""><img class="arrowBottom" src="' . LINKIMAGES . '/arrowBottom.png" onClick="">
			</div>';
			}
			$orderByOptions .= '</div>';
			$orderByLoopCount++;
		}
		//$debug->add('$orderByLoopCount: ' . "$orderByLoopCount in build view options.");
		$orderByOptions .= '		<div><div></div></div>
	</div>
';
		//The order direction.
		$orderDirectionOptions = '<fieldset class="viewOptionsItem" data-role="controlgroup" data-type="horizontal" data-mini="true">
    <legend>Direction</legend>
        <input id="orderDirectionAscending" name="orderDirection" value="list" ';
		$orderDirectionOptions .= $_SESSION['orderDirection'] == 'ASC' ? 'checked="checked" ' : '';
		$orderDirectionOptions .= 'type="radio">
        <label for="orderDirectionAscending">Ascending</label>
        <input id="orderDirectionDescending" name="orderDirection" value="list" ';
		$orderDirectionOptions .= $_SESSION['orderDirection'] == 'DESC' ? 'checked="checked" ' : '';
		$orderDirectionOptions .= 'type="radio">
        <label for="orderDirectionDescending">Descending</label>
</fieldset>';
		//Build the characters options. We will get three sets: active, deleted, completed.
		$viewCharacters = array();
		//The characters with active lines.
		$viewCharactersActiveStmt = $Dbc->prepare("SELECT
	characters.charId AS 'charId',
	characters.charName AS 'charName',
	characters.charColor AS 'charColor',
	COUNT(characters.charId) AS 'count'
FROM
	characters
JOIN
	linesTable ON linesTable.charId = characters.charId AND
	linesTable.listId = characters.listId AND
	characters.listId = ? AND
	linesTable.completed IS NULL AND
	linesTable.deleted IS NULL
GROUP BY
	characters.charName");
		$viewCharactersActiveStmt->execute(array($_SESSION['listId']));
		$totalActiveLines = 0;
		while($viewCharactersActiveRow = $viewCharactersActiveStmt->fetch(PDO::FETCH_ASSOC)){
			$viewCharacters[$viewCharactersActiveRow['charName']]['charId'] = $viewCharactersActiveRow['charId'];
			$viewCharacters[$viewCharactersActiveRow['charName']]['charColor'] = $viewCharactersActiveRow['charColor'];
			$viewCharacters[$viewCharactersActiveRow['charName']]['activeLinesCount'] = $viewCharactersActiveRow['count'];
			$viewCharacters[$viewCharactersActiveRow['charName']]['totalLinesCount'] = $viewCharactersActiveRow['count'];
			$totalActiveLines = $totalActiveLines + $viewCharactersActiveRow['count'];
		}
		//The characters with deleted lines. When a line is deleted it is irrelevant if it is marked as completed.
		$viewCharactersDeletedStmt = $Dbc->prepare("SELECT
	characters.charId AS 'charId',
	characters.charName AS 'charName',
	characters.charColor AS 'charColor',
	COUNT(characters.charId) AS 'count'
FROM
	characters
JOIN
	linesTable ON linesTable.charId = characters.charId AND
	linesTable.listId = characters.listId AND
	characters.listId = ? AND
	linesTable.deleted IS NOT NULL
GROUP BY
	characters.charName");
		$viewCharactersDeletedStmt->execute(array($_SESSION['listId']));
		$totalDeletedLines = 0;
		while($viewCharactersDeletedRow = $viewCharactersDeletedStmt->fetch(PDO::FETCH_ASSOC)){
			$viewCharacters[$viewCharactersDeletedRow['charName']]['charId'] = $viewCharactersDeletedRow['charId'];
			$viewCharacters[$viewCharactersDeletedRow['charName']]['charColor'] = $viewCharactersDeletedRow['charColor'];
			$viewCharacters[$viewCharactersDeletedRow['charName']]['deletedLinesCount'] = $viewCharactersDeletedRow['count'];			
			$viewCharacters[$viewCharactersDeletedRow['charName']]['totalLinesCount'] = isset($viewCharacters[$viewCharactersDeletedRow['charName']]['totalLinesCount']) ? $viewCharacters[$viewCharactersDeletedRow['charName']]['totalLinesCount'] : '';
			$totalDeletedLines = $totalDeletedLines + $viewCharactersDeletedRow['count'];
		}
		//The characters with completed lines. Completed lines marked as deleted are ignored.
		$viewCharactersCompletedStmt = $Dbc->prepare("SELECT
	characters.charId AS 'charId',
	characters.charName AS 'charName',
	characters.charColor AS 'charColor',
	COUNT(characters.charId) AS 'count'
FROM
	characters
JOIN
	linesTable ON linesTable.charId = characters.charId AND
	linesTable.listId = characters.listId AND
	characters.listId = ? AND
	linesTable.deleted IS NULL AND
	linesTable.completed IS NOT NULL
GROUP BY
	characters.charName");
		$viewCharactersCompletedStmt->execute(array($_SESSION['listId']));
		$totalCompletedLines = 0;
		while($viewCharactersCompletedRow = $viewCharactersCompletedStmt->fetch(PDO::FETCH_ASSOC)){
			$viewCharacters[$viewCharactersCompletedRow['charName']]['charId'] = $viewCharactersCompletedRow['charId'];
			$viewCharacters[$viewCharactersCompletedRow['charName']]['charColor'] = $viewCharactersCompletedRow['charColor'];
			$viewCharacters[$viewCharactersCompletedRow['charName']]['completed'] = true;
			$viewCharacters[$viewCharactersCompletedRow['charName']]['completedLinesCount'] = $viewCharactersCompletedRow['count'];
			$totalCompletedLines = $totalCompletedLines + $viewCharactersCompletedRow['count'];
		}
		ksort($viewCharacters,SORT_STRING);
		//$debug->printArray($viewCharacters,'$viewCharacters');
		$userViewCharactersArray = explode(' ',$_SESSION['viewCharacters']);//An array of character Ids.
		//$debug->printArray($userViewCharactersArray,'$userViewCharactersArray');
		//View reels options.
		$viewReelsStmt = $Dbc->prepare("SELECT
	linesTable.reel AS 'reel',
	COUNT(linesTable.reel) AS 'count'
FROM
	linesTable
WHERE
	linesTable.listId = ?
GROUP BY
	LENGTH(linesTable.reel), linesTable.reel");
		$viewReelsStmt->execute(array($_SESSION['listId']));
		$reelsOptions = '<fieldset class="viewOptionsItem" data-mini="true" data-role="controlgroup">
    <legend>View Reels</legend>
    <input callback="checkAll"';
		$reelsOptions .=  $_SESSION['viewReels'] == 'viewAll' ? ' checked="checked"' : '';
		$reelsOptions .= '" id="viewReelsMaster" name="viewReelsMaster" type="checkbox" viewReelsValue="viewAll">
    <label for="viewReelsMaster">All Reels</label>
';
		$userReelsArray = explode(' ',$_SESSION['viewReels']);//An array of reel ids.
		//$debug->printArray($userReelsArray,'$userReelsArray in build view options.');
		$viewReelsArray = array();
		$reelId = 0;
		while($viewReelsRow = $viewReelsStmt->fetch(PDO::FETCH_ASSOC)){
			$reelCount = lineCount($viewReelsRow['count']);
			if(empty($viewReelsRow['reel'])){
				if(in_array('',$userReelsArray) || $_SESSION['viewReels'] == 'viewAll'){
					$checked = 'checked="checked"';
					$reelsValue = 'no reel ' . $reelCount;
				}else{
					$checked = '';
					$reelsValue = 'no reel ' . $reelCount;
				}
			}else{
				if(in_array($viewReelsRow['reel'],$userReelsArray) || $_SESSION['viewReels'] == 'viewAll'){
					$checked = 'checked="checked"';
					$reelsValue = 'Reel ' . $viewReelsRow['reel'] . ' ' . $reelCount;
				}else{
					$checked = '';
					$reelsValue = 'Reel ' . $viewReelsRow['reel'] . ' ' . $reelCount;
				}
			}
			$reelsOptions .= '    <input callback="masterState" master="viewReelsMaster" name="viewReel' . $reelId . '" id="viewReel' . $reelId . '" type="checkbox" ' . $checked . ' viewReelsValue="' . $viewReelsRow['reel'] . '">
    <label for="viewReel' . $reelId . '">' . $reelsValue . '</label>';
			$viewReelsArray[] = $viewReelsRow;
			$reelId++;
		}
		$reelsOptions .= '</fieldset>';
		//$debug->printArray($viewReelsArray,'$viewReelsArray');
		
		//Build the character view options
		$characterOptions = '<fieldset class="viewOptionsItem" data-mini="true" data-role="controlgroup">
    <legend class="ui-controlgroup-vertical"><span class="mobile">View Characters</span><table class="desktop tablet ui-controlgroup-controls textCenter" style="padding:0 1em"><tr><td style="min-width:10em">View Characters</td><td style="padding:.5em 0;width:33%">active</td><td style="background-color:#B1FF99;padding:.5em 0;width:33%">completed</td><td style="background-color:#FF7070;padding:.5em 0;width:33%">deleted</td></tr></table></legend>
    <input callback="checkAll"';
		$characterOptions .=  empty($_SESSION['viewCharacters']) ? ' checked="checked"' : '';
		$characterOptions .= '" id="viewCharactersMaster" name="viewCharactersMaster" viewCharactersValue="viewAll" type="checkbox">
    <label for="viewCharactersMaster"><span class="mobile">All Characters</span><table class="desktop tablet textCenter" style="width:100%"><tr><td style="min-width:10em">All Characters</td><td style="width:33%">' . $totalActiveLines . '</td><td style="width:33%">' . $totalDeletedLines . '</td><td style="width:33%">' . $totalCompletedLines . '</td></tr></table></label>';


		$characterCount = count($userViewCharactersArray);
		foreach($viewCharacters as $key => $value){
			//Determine the line count.
			$lineCount = 0;
			if($_SESSION['showCompletedLines'] == true){
				$lineCount = empty($value['completedLinesCount']) ? 0 : $value['completedLinesCount'];
				//$debug->add('$lineCount first if for ' . $key . ': ' . "$lineCount.");
			}
			if($_SESSION['showDeletedLines'] == true){
				$lineCount += empty($value['deletedLinesCount']) ? 0 : $value['deletedLinesCount'];
				//$debug->add('$lineCount second if for ' . $key . ': ' . "$lineCount.");
			}
			$lineCount = empty($value['activeLinesCount']) ? lineCount($lineCount) : lineCount($lineCount + $value['activeLinesCount']);
			//Determine what character color to show.
			if($_SESSION['showCharacterColors'] == false || empty($value['charColor'])){
				$characterColor = 'FFF';
			}else{
				$characterColor = $value['charColor'];
			}		
			if(in_array($value['charId'],$userViewCharactersArray) || empty($_SESSION['viewCharacters'])){
				$checked = ' checked="checked"';
				$checkedOld = 'checked';
			}else{
				$checked = '';
				$checkedOld = 'unchecked';
			}	
			$characterOptions .= '<input callback="masterState" master="viewCharactersMaster" name="viewCharacter' . $value['charId'] . '" id="viewCharacter' . $value['charId'] . '" type="checkbox" ' . $checked . ' viewCharactersValue="' . $value['charId'] . '">
    <label for="viewCharacter' . $value['charId'] . '" style="background-color:#' . $characterColor . '"><span class="mobile">' . $key . '</span><table class="desktop tablet textCenter" style="width:100%"><tr>
			<td style="min-width:10em">' . $key . '</td><td style="width:33%">';
			$characterOptions .= empty($value['activeLinesCount']) ? '0' : $value['activeLinesCount'];
			$characterOptions .= '</td><td style="width:33%">';
			$characterOptions .= empty($value['completedLinesCount']) ? '0' : $value['completedLinesCount'];
			$characterOptions .= '</td><td style="width:33%">';
			$characterOptions .= empty($value['deletedLinesCount']) ? '0' : $value['deletedLinesCount'];
			$characterOptions .= '</td>
		</tr>
	</table>
</label>';
		}
		$characterOptions .= '</fieldset>';

		//The show options.
		$showOptions = '	
			<fieldset class="textLeft" data-role="controlgroup" data-type="vertical" data-mini="true">
		<legend>Show</legend>
		<input callback="advancedViewColorWheel" id="advancedShowCharacterColors" name="advancedShowCharacterColors" type="checkbox"';
		$showOptions .= $_SESSION['showCharacterColors'] == true ? ' checked="checked"' : '';
		$showOptions .= '">
		<label for="advancedShowCharacterColors">Character Colors <img class="middle" id="advancedSaveColorWheel" src="' . LINKIMAGES . '/colorWheel.png" style="height:1.3em;width:1.3em;"></label>
		
		<input id="advancedShowCompletedLines" name="advancedShowCompletedLines" style="background-color:#FF7070" type="checkbox"';
		$showOptions .= $_SESSION['showCompletedLines'] == true ? ' checked="checked"' : '';
		$showOptions .= '">
		<label for="advancedShowCompletedLines" style="background-color:#B1FF99">Completed Lines</label>
		
		<input data-wrapper-class="textLeft" id="advancedShowDeletedLines" name="advancedShowDeletedLines" type="checkbox"';
		$showOptions .= $_SESSION['showDeletedLines'] == true ? ' checked="checked"' : '';
		$showOptions .= '">
		<label for="advancedShowDeletedLines" style="background-color:#FF7070">Deleted Lines</label>
	</fieldset>';

		$advancedViewOptionsBlueButtons = '<div class="break" id="advancedViewOptions">
		<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all" id="resetViewOptionsToDefault" onClick="">Reset To Default</button>';
		$advancedViewOptionsBlueButtons .= (isset($_SESSION['listRoleId']) && $_SESSION['listRoleId'] > 1) ? '<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all" id="tcValidateAll" onClick="">Validate TC</button>' : '';
		$advancedViewOptionsBlueButtons .= '
	<button class="ui-btn ui-icon-refresh ui-btn-icon-left ui-btn-inline ui-shadow ui-corner-all" id="refreshAdvancedViewOptions" onClick="">Refresh</button><button class="ui-btn ui-icon-heart ui-btn-icon-left ui-btn-inline ui-shadow ui-corner-all" id="saveAdvancedViewOptions" onClick="">Save</span>' . cancelButton() . '</div>
';
		//Print options.
			$printOptions = '<div class="break center textCenter" style="margin:1em">
	<button class="export ui-btn ui-btn-inline ui-corner-all ui-mini" exportFor="engineer">PDF for Engineer</button>
	<button class="export ui-btn ui-btn-inline ui-corner-all ui-mini" exportFor="talent">PDF for Talent</button>
	<div class="textCenter" style="margin-top:1em">Options</div>
	<div class="ui-field-contain">
		<input name="viewListOnLogin" id="exportShowComments" type="checkbox" data-wrapper-class="true">
		<label for="exportShowComments">Show Comments</label>
	</div>
</div>';
$tabs = '<div style="height:2em"></div><div data-role="tabs" id="tabs">
	<div data-role="navbar">
		<ul>
			<li><a class="ui-btn-active" data-theme="c" data-ajax="false" href="#viewOptions"><i class="fa fa-cogs desktopInline tabletInline"></i>View<span class="desktopInline tabletInline">&nbsp;Options</span></a></li>
			<li><a data-theme="c" data-ajax="false" href="#export"><i class="fa fa-share desktopInline tabletInline"></i>Export</a></li>
			<li><a data-theme="c" data-ajax="false" href="#reports"><i class="fa fa-bar-chart-o desktopInline tabletInline"></i>Reports</a></li>
		</ul>
	</div>
	<div id="viewOptions" class="ui-body-c ui-content">
		' . $orderByOptions . $orderDirectionOptions . $reelsOptions  . $characterOptions . $showOptions . $advancedViewOptionsBlueButtons . '
	</div>
	<div id="export">
		' . $printOptions . '
	</div>
	<div id="reports">
		nothing here yet
	</div>
</div>';

		$advancedViewOptions = $tabs;
		if(MODE == 'buildViewOptions'){
			$success = true;
			$returnThis['buildViewOptions'] = $advancedViewOptions;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildViewOptions'){
		returnData();
	}
}

function copyValue($lineId,$field,$value){
	return '<i class="fa fa-toggle-up hand copyValue" lineId="' . $lineId . '" onClick="" field="' . $field . '" value="' . $value . '" style="height:10px;width:12px;"></i>';
}

function createNewCharacter(){
	//Creates a  new character from the Create New Character dialogue.
	global $debug, $message, $success, $Dbc, $returnThis;
	if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
		$message .= "Your role doesn't allow you to edit this list.<br>";
	}elseif(empty($_POST['createNewCharacterName'])){
		$message .= 'Please enter a first name.';
		$debug->add('$_POST[\'createNewCharacterName\'] is empty.');
	}else{
		$createNewCharacter = 0;
		$createNewCharacterName = trim($_POST['createNewCharacterName']);
		$charNameExplode = explode(' ', $createNewCharacterName);
		try{
			$Dbc->beginTransaction();
			//Checks to see if a character already exists.
			$addCharacterCheckQuery = "SELECT
	charId AS 'charId',
	characters AS 'charName',
	charColor AS 'charColor',
	created AS 'created',
	dId as 'dId'
FROM
	characters
WHERE
	listId = ?";
			$params = array($_SESSION['listId']);
			if(count($charNameExplode) > 0){
				foreach($charNameExplode as $value){
					$addCharacterCheckQuery .= " AND
	characters LIKE ?";
					$params[] = '%' . $value . '%';
				}
			}else{
				 $addCharacterCheckQuery .= "AND
	characters LIKE ?";
				$params[] = '%' . $createNewCharacterName . '%';
			}
			//Insert the new character.
			$insertStmt = $Dbc->prepare("INSERT INTO
	characters
SET
	listId = ?,
	characters = ?,
	cId = ?,
	created = ?");
			if(empty($_POST['addForSure'])){
				$stmt = $Dbc->prepare($addCharacterCheckQuery);
				$stmt->execute($params);
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if(empty($result)){
					pdoError(__LINE__, $stmt, false, true);
					$createNewCharacter = 1;
				}else{
					$message .= 'Did you want to select the existing character: ';
					foreach($result as $row){
						$message .= '<div class="hand italic underline"';
						if(empty($row['dId'])){//We don't want to have deleted characters ressurected here.
							$message .= ' id="potential' . $row['charId'] . '" charId="' . $row['charId'] . '" charcolor="' . $row['charColor'] . '" charname="' . $row['charName'] . '">';
						}else{//These are deleted characters that need to be marked as undeleted to be used again.
							$message .= ' id="mustBeUndeleted" charId="' . $row['charId'] . '" charcolor="' . $row['charColor'] . '" charname="' . $row['charName'] . '">';
						}
						$message .= $row['charName'] . ' <span class="textSmall">(created ' . Adrlist_Time::utcToLocal($row['created']) . ')</span></div>';
					}
					$message .= '<div class="hand underline" id="addForSure">No, create a new character.</div>';
					$success = 2;
				}
			}else{
				$createNewCharacter = 1;
			}
			if($createNewCharacter){
				$params = array($_SESSION['listId'],$createNewCharacterName,$_SESSION['userId'],DATETIME);
				$insertStmt->execute($params);
				$newCharId = $Dbc->lastInsertId();
				$Dbc->commit();
				buildCharactersList();
				updateListHist($_SESSION['listId']);
				$message .= 'Added';				
				if(MODE == 'createNewCharacter'){
					$success = true;
					$returnThis['newCharId'] = $newCharId;
				}
			}
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
	if(MODE == 'createNewCharacter'){
		returnData();
	}
}

function deleteCharacter(){
	//Delete a character. Just like lines, characters aren't deleted, but marked as deleted. Their associated lines are also marked as deleted.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['charId'])){
			throw new Adrlist_CustomException('','charId is empty.');
		}elseif(!is_numeric($_POST['charId'])){
			throw new Adrlist_CustomException('','$_POST[\'charId\'] is not numeric.');
		}
		$Dbc->beginTransaction();
		//Update the character deleted column.
		$stmt = $Dbc->prepare("UPDATE
	characters
SET
	characters.dId = ?,
	characters.deleted = ?
WHERE
	characters.charId = ?");
		$stmt->execute(array(intval($_SESSION['userId']),DATETIME,$_POST['charId']));
		$stmt = $Dbc->prepare("UPDATE
	linesTable
SET
	linesTable.dId = ?,
	linesTable.deleted = ?
WHERE
	linesTable.charId = ?");
		$stmt->execute(array(intval($_SESSION['userId']),DATETIME,$_POST['charId']));
		$Dbc->commit();
		$message .= 'Deleted';
		updateListHist($_SESSION['listId']);
		if(MODE == 'deleteCharacter'){
			$success = true;
			$returnThis['buildLines'] = buildLines();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'deleteCharacter'){
		returnData();
	}
}

function deleteComment(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['commentId'])){
			throw new Adrlist_CustomException('','$_POST[\'commentId\'] is empty.');
		}elseif(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','$_POST[\'lineId\'] is empty.');
		}
		$stmt = $Dbc->prepare("DELETE
FROM 
	lineComments
WHERE
	commentId = ? AND
	userId = ?");
		$stmt->execute(array($_POST['commentId'],$_SESSION['userId']));
		updateListHist($_SESSION['listId']);
		$message .= 'Deleted';
		if(MODE == 'deleteComment'){
			$success = true;
			$returnThis['buildComments'] = buildComments($_POST['lineId']);
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'deleteComment'){
		returnData();
	}
}

function deleteLine(){
	//Updates or inserts the line history deleted date and userId.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','$_POST[\'lineId\'] is empty.');
		}elseif(!is_numeric($_POST['lineId'])){
			throw new Adrlist_CustomException('','$_POST[\'lineId\'] is not numeric.');
		}
		$stmt = $Dbc->prepare("UPDATE
	linesTable
SET
	dId = ?,
	deleted = ?
WHERE
	lineId = ?");
		$stmt->execute(array($_SESSION['userId'],DATETIME,$_POST['lineId']));
		updateListHist($_SESSION['listId']);
		$message .= 'Deleted';
		if(MODE == 'deleteLine'){
			$success = true;
			$returnThis['buildLines'] = buildLines();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'deleteLine'){
		returnData();
	}
}

function editCharacterPart1(){
	//Show the edit character fields.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
		$message .= "Your role doesn't allow you to edit this list.<br>";
	}elseif(empty($_POST['charId'])){
		error(__LINE__,'','$_POST[\'charId\'] is empty.');
	}elseif(!is_numeric($_POST['charId'])){
		error(__LINE__,'','$_POST[\'charId\'] is not numeric.');
	}else{
		try{
			$stmt = $Dbc->prepare("SELECT
	characters.charName AS 'charName',
	characters.charColor AS 'charColor'
FROM
	characters
WHERE
	characters.charId = ?");
			$stmt->execute(array($_POST['charId']));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if(empty($row)){
				pdoError(__LINE__, $stmt, true);
				error(__LINE__);
			}else{
				$output .= '
<div class="center textCenter">
	<div class="myAccountTitle">Edit Character</div>
	<input autocapitalize="off" autocorrect="off" charId="' . $_POST['charId'] . '" data-clear-btn="true" data-wrapper-class="center" id="editCharacterName" goswitch="editCharacterSave" name="editCharacterName" placeholder="Character Name" value="' . $row['charName'] . '" type="text">
	<div class="ui-field-contain">
		<label for="editCharacterColor" unused="ui-hidden-accessible">Character Color</label>
		<input autocapitalize="off" autocorrect="off" data-mini="true" data-wrapper-class="true" id="editCharacterColor" goswitch="editCharacterSave" name="editCharacterColor" placeholder="" value="' . $row['charColor'] . '" type="text">
	</div>
	
	<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-plus" id="editCharacterSave">Save</button><button class="ui-btn ui-btn-b ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-delete" id="editCharacterCancel">Cancel</button>
</div>';
				if(MODE == 'editCharacterPart1'){
					$success = true;
					$returnThis['returnCode'] = $output;
				}
			}
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
	if(MODE == 'editCharacterPart1'){
		returnData();
	}
}

function editCharacterPart2(){
	//Edit a character name.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['charId'])){
			throw new Adrlist_CustomException('','$_POST[\'charId\'] is empty.');
		}elseif(empty($_POST['editCharacterName'])){
			throw new Adrlist_CustomException('','editCharacterName is empty.');		
		}elseif(!isset($_POST['editCharacterColor'])){
			throw new Adrlist_CustomException('','editCharacterColor is empty.');		
		}
		$editCharacterName = trim($_POST['editCharacterName']);
		$editCharacterColor = trim($_POST['editCharacterColor']);
		$editCharacterColor = trim($_POST['editCharacterColor'], '#');
		//Check to see if the newly edited character name already exists. This query differs from the createNewCharacterQuery in that it ignores the currently selected charId.
		$stmt = $Dbc->prepare("SELECT
	characters.charName AS 'charName',
	characters.charId AS 'charId'
FROM
	characters
WHERE
	characters.listId = ? AND
	characters.charName LIKE ? AND
	characters.charId != ?");
		$params = array($_SESSION['listId'],"%$editCharacterName%",$_POST['charId']);
		$stmt->execute($params);
		$characterCheck = 0;
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$message .= '<div class="hand italic underline" id="editCharactersPotential" charId="' . $row['charId'] . '">' . $row['charName'] . '</div>';
		}
		if(empty($row)){
			//It's ok to not have any lines affected here.
			pdoError(__LINE__, $stmt, $params, true);
			$characterCheck = 1;
		}else{
			$message .= 'A character with this name already exists.';
		}
		if($characterCheck){
			//The character does exists, so update it.
			$stmt = $Dbc->prepare("UPDATE
	characters
SET
	characters = ?,
	charColor = ?,
	mId = ?,
	modified = ?
WHERE
	charId = ?");
			$stmt->execute(array($editCharacterName,$editCharacterColor,$_SESSION['userId'],DATETIME,$_POST['charId']));
			updateListHist($_SESSION['listId']);
			if(!empty($editCharacterColor) && $editCharacterColor != 'FFFFFF'){
				$_POST['showCharacterColors'] = 'true';//The following options are in quotes because jquery is passing values via POST, which does not respect data types (boolean vs string vs integer). Here we check for 'true' (string) and not true (boolean). Furthermore, PHP is not strictly typed, so it equates any string  or 1 as true.
				saveViewOptions();
			}
			$message .= 'Saved';
			if(MODE == 'editCharacterPart2'){
				$success = true;
				$returnThis['buildLines'] = buildLines();
			}else{
				$debug->add('In editCharacterPart2, MODE is not editCharacterPart2.');
			}
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'editCharacterPart2'){
		returnData();
	}
}

function editLinePart1(){
	//Build the edit line div.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','editLinePart1: $_POST[\'lineId\'] is empty.');
		}
		$lineId = intval($_POST['lineId']);
		$stmt = $Dbc->prepare("SELECT
	linesTable.charId AS 'charId',
	linesTable.lineId as lineId,
	linesTable.reel AS 'reel',
	linesTable.scene AS 'scene',
	linesTable.tcIn AS 'tcIn',
	linesTable.tcOut AS 'tcOut',
	linesTable.line AS 'line',
	linesTable.notes AS 'notes'
FROM
	linesTable
WHERE
	linesTable.lineId = ? AND
	linesTable.listId = ?");
		$params = array($lineId,$_SESSION['listId']);
		$stmt->execute($params);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(empty($row)){
			error(__LINE__);
			pdoError(__LINE__, $stmt, $params, 1);
		}else{
			$row = charToHtml($row);//Convert all special characters to html.
			$output .= '<div id="lineDiv' . $lineId . '" class="lineMain ui-corner-all center textCenter" style="border:none">
					' . buildCharacters($row['charId'], 'editLineCharacter', 13) . '
					<div class="ui-field-contain">
						<label for="editReel" unused="ui-hidden-accessible">Reel</label>
						<input autocapitalize="off" autocorrect="off" data-mini="true" data-wrapper-class="true" id="editReel" goswitch="addLineButton" name="editReel" placeholder="" type="text" value="' . $row['reel'] . '">
					</div>
					<div class="ui-field-contain">
						<label for="editScene" unused="ui-hidden-accessible">Scene</label>
						<input autocapitalize="off" autocorrect="off" data-mini="true" data-wrapper-class="true" id="editScene" goswitch="addLineButton" name="editScene" placeholder="" type="text" value="' . $row['scene'] . '">
					</div>
					<div class="ui-field-contain">
						<label for="editTcIn" unused="ui-hidden-accessible">TC In</label>
						<input autocapitalize="off" autocorrect="off" class="tcValidate" data-mini="true" data-wrapper-class="true" entry="edit" id="editTcIn" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" maxlength="14" name="editTcIn" otherfield="editTcOut" placeholder="" type="text" value="' . $row['tcIn'] . '">
					</div>
					<button lineId="2351" class="swapTc ui-btn ui-mini ui-btn-inline ui-corner-all" entry="edit"><i class="fa fa-exchange fa-lg fa-rotate-90"></i>Swap</button>
					<div class="ui-field-contain">
						<label for="editTcOut" unused="ui-hidden-accessible">TC Out</label>
						<input autocapitalize="off" autocorrect="off" class="tcValidate" data-mini="true" data-wrapper-class="true" entry="edit" id="editTcOut" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" maxlength="14" name="editTcOut" otherfield="editTcIn" placeholder="" type="text" value="' . $row['tcOut'] . '">
					</div>
					<div class="ui-field-contain">
						<label for="editLine" unused="ui-hidden-accessible">Line</label>
						<textarea autocapitalize="off" autocorrect="off" data-mini="true" data-wrapper-class="true" id="editLine" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" name="addLine" placeholder="" rows="5">' . $row['line'] . '</textarea>
					</div>
					<div class="ui-field-contain">
						<label for="editNotes" unused="ui-hidden-accessible">Notes</label>
						<textarea autocapitalize="off" autocorrect="off" data-mini="false" data-wrapper-class="true" id="editNotes" framerate="' . $_SESSION['framerate'] . '" goswitch="addLineButton" name="addNotes" placeholder="" rows="5">' . $row['notes'] . '</textarea>
					</div>
					<button class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-heart" id="saveLineButton" lineId="' . $row['lineId'] . '">Save Changes</button><button class="ui-btn ui-btn-b ui-btn-inline ui-shadow ui-corner-all ui-btn-icon-left ui-icon-delete" id="cancelEditLine">Cancel</button>				
				</div>';
			$success = MODE == 'editLinePart1' ? true : $success;
			$returnThis['returnEditLinePart1'] = $output;
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'editLinePart1'){
		returnData();
	}
}

function editLinePart2(){
	//Save the edited line.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'lineId\'] is empty.');
		}elseif(empty($_POST['charId'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'charId\'] is empty.');
		}elseif(!isset($_POST['reel'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'reel\'] is empty.');
		}elseif(!isset($_POST['scene'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'scene\'] is empty.');
		}elseif(!isset($_POST['tcIn'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'tcIn\'] is empty.');
		}elseif(!isset($_POST['tcOut'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'tcOut\'] is empty.');
		}elseif(!isset($_POST['line'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'line\'] is empty.');
		}elseif(!isset($_POST['notes'])){
			throw new Adrlist_CustomException('','editLinePart2: $_POST[\'notes\'] is empty.');
		}
		$stmt = $Dbc->prepare("UPDATE
	linesTable
SET
	linesTable.charId = ?,
	linesTable.reel = ?,
	linesTable.scene = ?,
	linesTable.tcIn = ?,
	linesTable.tcOut = ?,
	linesTable.line = ?,
	linesTable.notes = ?,
	linesTable.modified = ?,
	linesTable.mId = ?
WHERE
	linesTable.lineId = ?");
		$stmt->execute(array($_POST['charId'],$_POST['reel'],$_POST['scene'],$_POST['tcIn'],$_POST['tcOut'],$_POST['line'],$_POST['notes'],DATETIME,$_SESSION['userId'],$_POST['lineId']));
		updateListHist($_SESSION['listId']);
		$message .= 'Saved';
		if(MODE == 'editLinePart2'){
			$success = true;
			$returnThis['buildLines'] = buildLines();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'editLinePart2'){
		returnData();
	}
}

function initializeList(){	
	global $debug, $message, $Dbc;
	try{
		//Get the basic info of the list and the user's relationship to it.
		$initializeListStmt = $Dbc->prepare("SELECT
	userSiteSettings.listId AS 'listId',
	lists.listName AS 'listName',
	lists.frId AS 'frId',
	lists.locked AS 'locked',
	lists.cId AS 'cId',
	lists.created AS 'created',
	lists.mId AS 'mId',
	lists.modified AS 'modified',
	lists.dId AS 'dId',
	lists.deleted AS 'deleted',
	folders.folderName AS 'folderName',
	framerates.framerate AS 'framerate',
	framerates.fps AS 'fps',
	userFolderSettings.folderRoleId AS 'folderRoleId',
	userListSettings.listRoleId AS 'listRoleId',
	userListSettings.listOffset AS 'offset',
	userListSettings.limitCount AS 'limit',
	userListSettings.orderBy AS 'orderBy',
	userListSettings.orderDirection AS 'orderDirection',
	userListSettings.viewReels AS 'viewReels',
	userListSettings.viewCharacters AS 'viewCharacters',
	userListSettings.showCompletedLines AS 'showCompletedLines',
	userListSettings.showDeletedLines AS 'showDeletedLines',
	userListSettings.showCharacterColors AS 'showCharacterColors'
FROM
	lists
JOIN
	userSiteSettings ON userSiteSettings.listId = lists.listId AND
	userSiteSettings.userId = ?
JOIN
	userListSettings ON userListSettings.listId = userSiteSettings.listId AND
	userListSettings.listRoleId <> '0' AND
	userListSettings.userId = userSiteSettings.userId
JOIN
	framerates on framerates.frId = lists.frId
LEFT JOIN
	folders ON folders.folderId = lists.folderId
LEFT JOIN
	userFolderSettings ON userFolderSettings.folderId = folders.folderId AND
	userFolderSettings.folderRoleId <> '0' AND
	userFolderSettings.userId = userSiteSettings.userId");
		$initializeListStmt->execute(array($_SESSION['userId']));
		$initializeListRow = $initializeListStmt->fetch(PDO::FETCH_ASSOC);
		if(empty($initializeListRow)){
			$message .= "Your role doesn't allow you to access that list.";
			header('Location:' . LINKADRLISTS . '?message=' . $message);
		}elseif($initializeListRow['locked']){
			$message .= "That list is locked. It must be unlocked before it can be viewed or edited. " . faqLink(45);
			header('Location:' . LINKADRLISTS . '?message=' . $message);
		}else{
			foreach($initializeListRow as $key => $value){
				$_SESSION[$key] = $value;
			}
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
}

function lineCount($count){
	$output = '';
	if(is_numeric($count)){
		if($count > 1){
			$output = ' (' . $count . ' lines)';
		}else{
			$output = ' (' . $count . ' line)';
		}
	}
	return $output;
}

function markCompleted(){
	//Mark the line as completed.
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		if(empty($_SESSION['listRoleId']) && $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','$_POST[\'lineId\'] is empty.');
		}
		$stmt = $Dbc->prepare("UPDATE
	linesTable
SET
	comId = ?,
	completed = ?
WHERE
	lineId = ?");
		$stmt->execute(array($_SESSION['userId'],DATETIME,$_POST['lineId']));
		$success = MODE == 'markCompleted' ? true : $success;
		$message .= 'Marked as completed.<br>';
		updateListHist($_SESSION['listId']);
		$returnThis['buildLines'] = buildLines();
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'markCompleted'){
		returnData();
	}
}

function markUncompleted(){
	//Mark the line as uncompleted.
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		if(empty($_SESSION['listRoleId']) && $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','$_POST[\'lineId\'] is empty.');
		}
		$stmt = $Dbc->prepare("UPDATE
	linesTable
SET
	completed = ?
WHERE
	lineId = ?");
		$stmt->execute(array(NULL,$_POST['lineId']));
		$success = MODE == 'markUncompleted' ? true : $success;
		$message .= 'Marked as uncompleted.';
		updateListHist($_SESSION['listId']);
		$returnThis['buildLines'] = buildLines();
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'markUncompleted'){
		returnData();
	}
}

function saveViewOptions(){
	//Set the view options.
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		$userListDefaults = getDefaultListSettings();
		$debug->printArray($userListDefaults,'$userListDefaults');
		if($userListDefaults === false){
			throw new Adrlist_CustomException('',"Could not get the user's default list settings.");
		}
		$tempArray = array('defaultViewReels' => 'viewAll', 'defaultViewCharacters' => '');
		$userListDefaults = array_merge($userListDefaults,$tempArray);
		$resetOffset = false;
		//We will build the update statement in parts.
		$updateListSettingsStmt = "UPDATE
	userListSettings
SET
	";
		$updateListSettingsParams = array();
		//Order by statement. Order by must always be 78 characters long using a combination of the string options.
		if(!empty($_POST['orderBy'])){
			$updateListSettingsStmt .= "orderBy = ?,";
			if(strlen($_POST['orderBy']) == 86){
				//The user has used the advanced view options. A complete string of order by options should have been submitted.
				$updateListSettingsParams[] = $_POST['orderBy'];
			}else{
				$updateListSettingsParams[] = $userListDefaults['defaultOrderBy'];
				$debug->add('Set order by to default.');
			}
		}
		//Order direction statement.
		if(!empty($_POST['orderDirection'])){
			$updateListSettingsStmt .= "orderDirection = ?,";
			//The user has used the advanced view options.
			if($_POST['orderDirection'] == 'ASC' || $_POST['orderDirection'] == 'DESC'){
				$updateListSettingsParams[] = $_POST['orderDirection'];
			}else{
				$updateListSettingsParams[] = $userListDefaults['defaultOrderDirection'];
				$debug->add('Set order direction to default.');
			}
		}
		//View reels statement. Default is 'viewAll' and implies view all reels.
		$updateListSettingsStmt .= "viewReels = ?,";
		if(isset($_POST['viewReels'])){
			$updateListSettingsParams[] = $_POST['viewReels'];
			$resetOffset = $_SESSION['viewReels'] == $_POST['viewReels'] ? false : true;//Reset the offset if view reels has changed.
		}else{
			$updateListSettingsParams[] = $userListDefaults['defaultViewReels'];
			$debug->add('Set view reels to default.');
			$resetOffset = true;
		}
		
		//View characters statement. An empty value '' implies view all characters.
		$updateListSettingsStmt .= "viewCharacters = ?,";
		if(isset($_POST['viewCharacters'])){
			$updateListSettingsParams[] = $_POST['viewCharacters'];
		}else{
			$_POST['viewCharacters'] = '';
			$updateListSettingsParams[] = $userListDefaults['defaultViewCharacters'];
		}
		$resetOffset = $_SESSION['viewCharacters'] == $_POST['viewCharacters'] ? false : true;//Reset the offset if view characters has changed.
		//The following options are in quotes because jquery is passing values via POST, which does not respect data types (boolean vs string vs integer). Here we check for 'true' (string) and not true (boolean). Furthermore, PHP is not strictly typed, so it equates any string  or 1 as true.
		//Show character colors stmt.
		$updateListSettingsStmt .= "showCharacterColors = ?,";
		if(isset($_POST['showCharacterColors'])){
			$updateListSettingsParams[] = $_POST['showCharacterColors'] === 'true' ? 1 : 0;
		}else{
			$_POST['showCharacterColors'] = '';
			$updateListSettingsParams[] = $userListDefaults['defaultShowCharacterColors'];
		}
		//Show completed lines stmt.
		$updateListSettingsStmt .= "showCompletedLines = ?,";
		if(isset($_POST['showCompletedLines'])){
			$updateListSettingsParams[] = $_POST['showCompletedLines'] === 'true' ? 1 : 0;
		}else{
			$_POST['showCompletedLines'] = '';
			$updateListSettingsParams[] = $userListDefaults['defaultShowCompletedLines'];
		}
		//Show deleted lines stmt.
		$updateListSettingsStmt .= "showDeletedLines = ?";
		if(isset($_POST['showDeletedLines'])){
			$updateListSettingsParams[] = $_POST['showDeletedLines'] === 'true' ? 1 : 0;
		}else{
			$_POST['showDeletedLines'] = '';
			$updateListSettingsParams[] = $userListDefaults['defaultShowDeletedLines'];
		}
		$debug->add('$_POST[\'showCharacterColors\']: ' . $_POST['showCharacterColors'] . '<br>
$_POST[\'showCompletedLines\']: ' . $_POST['showCompletedLines'] . '<br>
$_POST[\'showDeletedLines\']: ' . $_POST['showDeletedLines'] . '.');
		array_push($updateListSettingsParams,$_SESSION['userId'],$_SESSION['listId']);
		$updateListSettingsStmt .= "
WHERE
	userId = ? AND
	listId = ?";
		$debug->add('$updateListSettingsStmt: ' . "$updateListSettingsStmt.");
		$debug->printArray($updateListSettingsParams,'$updateListSettingsParams');
		$updateListSettingsStmt = $Dbc->prepare($updateListSettingsStmt);
		$updateListSettingsStmt->execute($updateListSettingsParams);
		initializeList();
		$_SESSION['offset'] = 0;
		if(MODE == 'saveViewOptions'){
			$success = true;//It's okay if no lines were updated by this query. The user may have hit the default view button and not changed any view options.
			$returnThis['buildLines'] = buildLines();
			$message .= 'Saved view options.';
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'saveViewOptions'){
		returnData();
	}
}

function tcPad($padThis){
	//Will pad a zero to single digit timecode values.
	if(strlen($padThis)<2){
		return str_pad($padThis,2,'0',STR_PAD_LEFT);
	}else{
		return $padThis;
	}
}

function tcToFrames($tcValue,$fps){
	/*
	Converts HH:MM:SS:FF to frames per cycle, based on the fpc.
	$tcValue = (string) a timecode in HH:MM:SS:FF format.
	$fps = (string) the frames per cycle, which is not the same as the framerate. This should be whole value frames: 24, 25, 30.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	if(empty($fps)){
		if(empty($_SESSION['fps'])){
			error(__LINE__,'','No frames per cycle was available.');
		}else{
			$fps = $_SESSION['fps'];
		}
	}
	if(empty($tcValue)){
		return 0;
	}else{
		$parts = explode(':',$tcValue);//Outputs array(0 => hours, 1 => minutes, 2 => seconds, 3 => frames).
		//$debug->printArray($parts,'$parts in tcToFrames() before: ');
		$parts = array_map("intThis",$parts);//Convert values to integers.
		//$debug->printArray($parts,'$parts in tcToFrames() after: ');
		$hourFrames = $parts[0] * 60 * 60 * $fps;
		$minuteFrames = isset($parts[1]) ? $parts[1] * 60 * $fps : 0;
		$secondFrames = isset($parts[2]) ? $parts[2] * $fps : 0;
		$parts[3] = isset($parts[3]) ? $parts[3] : 0;
		$totalFrames = $hourFrames + $minuteFrames + $secondFrames + $parts[3];
		//$debug->add('$hourFrames: ' . $hourFrames . '<br>$minuteFrames: ' . $minuteFrames . '<br>$secondFrames: ' . $secondFrames . '<br>frames: ' . $parts['3'] . '<br>$totalFrames: ' . $totalFrames);
		return $totalFrames;
	}
}

function tcValidate($tcValue,$framerate){
	/*
	Validates a timecode value. Timecode is considered valid when conforms to the HH:MM:SS:FF format.
	$tcValue = (string) formatted as HH:MM:SS:FF.
	$framerate = (decimal) the framerate to compare the timecode against.
	Returns an array(
		'success' => (boolean),
		'tcValidateMessage' => (string)
	)
	*/
	global $debug, $message, $success, $returnThis, $Dbc;
	$tcBad = false;
	if(empty($framerate)){
		if(empty($_SESSION['framerate'])){
			$debug->add('No framerate was available.');
			return array('success' => false, 'tcValidateMessage' => 'No framerate was available.');
		}else{
			$framerate = $_SESSION['framerate'];
		}
	}
	$parts = explode(':',$tcValue);//Outputs array(0 => hours, 1 => minutes, 2 => seconds, 3 => frames).
	//$debug->printArray($parts,'$parts before: ');
	$parts = array_map("intThis",$parts);//Convert parts to integers.
	//$debug->printArray($parts,'$parts after: ');
	//PREG expression to check for the format HH:MM:SS:FF.
	$pattern = '^\d{1,2}:\d{2}:\d{2}:\d{2}$^';
	if(empty($tcValue) || !preg_match($pattern,$tcValue,$matches)){
		$debug->add('$tcValue does not match the HH:MM:SS:FF format.',debug_backtrace());
		return array('success' => false, 'tcValidateMessage' => 'does not match the HH:MM:SS:FF format.');
	}
	//Check minutes and seconds.
	if($parts[1] > 59){//Check minutes are 59 or less.
		$debug->add('Minutes are greater than 59.',debug_backtrace());
		return array('success' => false, 'tcValidateMessage' => 'minutes are greater than 59.');
	}elseif($parts[2] > 59){//Check seconds are 59 or less.
		$debug->add('Seconds are greater than 59.',debug_backtrace());
		return array('success' => false, 'tcValidateMessage' => 'seconds are greater than 59.');
	}elseif($parts[3] > $framerate){//Check frames are within framerate.
		$debug->add('Frames are greater than framerate.',debug_backtrace());
		return array('success' => false, 'tcValidateMessage' => 'frames are greater than framerate.');
	}else{
		return array('success' => true, 'tcValidateMessage' => 'gong');
	}
}

function tcValidateAll(){
	/*
	Validates an entire list's timecode fields. Builds a new list of lines.
	*/
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		//Get the framerate of the current list.
		if(empty($_SESSION['framerate'])){
			$framerateStmt = $Dbc->prepare("SELECT
	framerate AS 'framerate'
FROM
	framerates
WHERE
	frId = ?");
			$framerateStmt->execute(array($_SESSION['frId']));
			$framerate = $framerateStmt->fetch(PDO::FETCH_ASSOC);
			$framerate = $fpc['framerate'];
		}else{
			$framerate = $_SESSION['framerate'];
		}
		$tcValidateStmt = "SELECT
	linesTable.lineId AS 'lineId',
	characters.charName AS 'charName',
	characters.charColor AS 'charColor',
	linesTable.reel AS 'reel',
	linesTable.scene AS 'scene',
	linesTable.tcIn AS 'tcIn',
	linesTable.tcOut AS 'tcOut',
	linesTable.line AS 'line',
	linesTable.notes AS 'notes',
	linesTable.deleted AS 'deleted',
	linesTable.cId AS 'cId',
	linesTable.created AS 'created',
	linesTable.mId AS 'mId',
	linesTable.modified AS 'modified',
	linesTable.dId AS 'dId',
	linesTable.deleted AS 'deleted',
	linesTable.comId AS 'comId',
	linesTable.completed AS 'completed'
FROM
	linesTable
JOIN
	characters ON characters.charId = linesTable.charId AND
	linesTable.listId = ? AND
	linesTable.deleted IS NULL";
		if(empty($_SESSION['orderBy']) || $_SESSION['orderBy'] == 'NULL'){
			$orderBy = 'tcIn';
		}else{
			$pieces = explode(' ',$_SESSION['orderBy']);
			$orderBy = $pieces[0];
		}
		if(!empty($orderBy) && $orderBy != 'NULL'){
			$orderDirection = $_SESSION['orderDirection'];
			if($orderBy == 'charId'){
				$orderByQuery = "
ORDER BY
	characters.charName" . $orderDirection;
			}else{
				$orderByQuery = "
ORDER BY LENGTH(linesTable." . $orderBy . ") " . $orderDirection . ", linesTable." . $orderBy . " " . $orderDirection;
			}
		}else{
			if($orderBy == 'charId'){
				$orderByQuery = "
ORDER BY characters.charName";
			}else{
				$orderByQuery = "
ORDER BY LENGTH(linesTable.tcIn), linesTable.tcIn";
			}
		}
		$tcValidateStmt = $Dbc->prepare($tcValidateStmt . $orderByQuery);
		$tcValidateStmt->execute(array($_SESSION['listId']));
		$selectUsersStmt = $Dbc->prepare("SELECT
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'creator',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'modifier',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'deleter',
	(SELECT CONCAT_WS(' ', users.firstName, users.lastName) FROM users WHERE users.userId = ?) AS 'recorder'
FROM
	linesTable
WHERE
	linesTable.lineId = ?");
		$lineNumbering = 0;
		$foundRows = false;
		$tcBad = false;
		$lines = '<div style="margin-top:4em"></div>';
		$debug->add('Iteration ' . $lineNumbering);
		$framerate = $_SESSION['framerate'];
		while($row = $tcValidateStmt->fetch(PDO::FETCH_ASSOC)){
			//Validate the timecode values.
			$tcInValid = true;
			$tcOutValid = true;
			$tcCompareValid = true;
			$tcInFrames = tcToFrames($row['tcIn'],$_SESSION['fps']);
			$tcOutFrames = tcToFrames($row['tcOut'],$_SESSION['fps']);
			$tcInValidate = tcValidate($row['tcIn'],$framerate);
			$tcOutValidate = tcValidate($row['tcOut'],$framerate);
			$tcInParts = explode(':',$row['tcIn']);
			$tcInHours = $tcInParts[0];
			$tcOutParts = explode(':',$row['tcOut']);
			$tcOutHours = $tcOutParts[0];
			$reelHours = intThis($row['reel']);
			$tcValidateMessage = '';
			if(is_numeric($reelHours)){
				if($tcInHours != $reelHours){
					$tcInValid = false;
					$debug->add('TC In hours do not match reel hours.',debug_backtrace());
					$tcValidateMessage .= 'TC In hours do not match reel hours.';
				}
				if($tcOutHours != $reelHours){
					$tcOutValid = false;
					$debug->add('TC Out hours do not match reel hours.',debug_backtrace());
					$tcValidateMessage .= ' TC Out hours do not match reel hours.';
				}
			}
			if(empty($row['tcIn'])){
				$tcInValid = false;
				$debug->add('TC In is empty.',debug_backtrace());
				$tcValidateMessage .= ' TC In is empty.';
			}elseif(!$tcInValidate['success']){
				$tcInValid = false;
				$debug->add($tcInValidate['tcValidateMessage'],debug_backtrace());
				$tcValidateMessage .= ' TC In ' . $tcInValidate['tcValidateMessage'];
			}elseif(empty($row['tcOut'])){
				$tcOutValid = false;
				$debug->add('TC Out is empty.',debug_backtrace());
				$tcValidateMessage .= ' TC Out is empty.';
			}elseif(!$tcOutValidate['success']){
				$tcOutValid = false;
				$debug->add($tcOutValidate['tcValidateMessage'],debug_backtrace());
				$tcValidateMessage .= ' TC Out ' . $tcOutValidate['tcValidateMessage'];
			}elseif($tcInFrames <= $tcOutFrames){
				$debug->add('We have success here. $tcInFrames:' . $tcInFrames . ' < $tcOutFrames: ' . $tcOutFrames);
				$tcValidateMessage .= '';
			}else{
				$tcCompareValid = false;
				$debug->add('$tcIn is later than $tcOut. $tcInFrames:' . $tcInFrames . ' < $tcOutFrames: ' . $tcOutFrames,debug_backtrace());
				$tcValidateMessage .= ' TC In is later than TC Out.';
			}
			if(!$tcInValid || !$tcOutValid){
				$tcBad = true;
				$lineNumbering++;
				if(((boolean) $_SESSION['showCharacterColors'])){
					$bgColor = $row['charColor'];
				}else{
					if($bgColor == COLORGRAY){
						$bgColor = 'FFFFFF';
					}else{
						$bgColor = COLORGRAY;
					}
				}
				$selectUsersParams = array($row['cId'],$row['mId'],$row['dId'],$row['comId'],$_SESSION['listId']);
				$selectUsersStmt->execute($selectUsersParams);
				$usersRow = $selectUsersStmt->fetch(PDO::FETCH_ASSOC);
				$creator = $usersRow['creator'];
				$modifier = empty($usersRow['modifier']) ? '' : $usersRow['modifier'];
				$deleter = empty($usersRow['deleter']) ? '' : $usersRow['deleter'];
				$recorder = empty($usersRow['recorder']) ? '' : $usersRow['recorder'];
				$lines .= '<table class="lineMain">
			<tr>
		<td class="lineLeft" style="background-color:#' . $bgColor . ';">
			Character:&nbsp;' . $row['charName'] . '
			<table class="textLeft" style="width:100%">
				<tr>
					<td class="textLeft" style="width:50%">Reel:&nbsp;' . $row['reel'] . '</td>
					<td class="textLeft" style="width:50%">Scene:&nbsp;' . $row['scene'] . '</td>
				</tr>
			</table>
			<table class="textLeft">
				<tr>
					<td class="tdRight">TC In:&nbsp;</td>
					<td><input class="tcValidate" id="tcValidateIn' . $row['lineId'] . '" otherfield="tcValidateOut' . $row['lineId'] . '" value="' . $row['tcIn'] . '" style="background-color:#';
				if(!$tcInValid || !$tcCompareValid){
					$lines .= 'FFACAC';
				}else{
					$lines .= 'D4FFAB';
				}
				$lines .= '"></td>
					<td class="textLeft" rowspan="2"><img src="' . LINKIMAGES . '/swap.png" alt="Swap" id="swapvalidate" lineid="' . $row['lineId'] . '" style="height:36px;width:36px"></td>
				</tr>
				<tr>
					<td class="tdRight">TC Out:&nbsp;</td>
					<td><input class="tcValidate" id="tcValidateOut' . $row['lineId'] . '" otherfield="tcValidateIn' . $row['lineId'] . '" value="' . $row['tcOut'] . '" style="background-color:#';
				if(!$tcOutValid || !$tcCompareValid){
					$lines .= 'FFACAC';
				}else{
					$lines .= 'D4FFAB';
				}
				$lines .= '"></td>
				</tr>
			</table>
<div class="red textCenter">' . $tcValidateMessage . '</div>
			<div class="buttonBlueThin textCenter" id="tcValidateSave' . $row['lineId'] . '" lineid="' . $row['lineId'] . '">Save</div>
			<div class="lineHistory">created';
				$lines .= empty($creator) ? '' : ' by ' . $creator;
				$lines .= empty($row['created']) ? '' : ' on ' . Adrlist_Time::utcToLocal($row['created']);
				$lines .= '						<br>';
				if(!empty($modifier) && !empty($row['modified'])){
					$lines .= 'modified by ' . $modifier . ' on ' . Adrlist_Time::utcToLocal($row['modified']) . '<br>';
				}
				if(empty($row['deleted']) || $row['deleted'] == '0000-00-00 00:00:00'){
					$deleted = false;
				}else{
					$lines .= 'deleted by ' . $deleter . ' on ' . Adrlist_Time::utcToLocal($row['deleted']);
					$deleted = true;
				}
				if(empty($row['completed']) || $row['completed'] == '0000-00-00 00:00:00'){
					$completed = false;
				}else{
					$lines .= 'completed by ' . $recorder . ' on ' . Adrlist_Time::utcToLocal($row['completed']);
					$completed = true;
				}
				$lines .= '
			</div>
		</td>
		<td style="background-color:#' . $bgColor . ';" class="textLeft top">
			<table class="lineRight">
				<tr>
					<td class="lineCell1">Line:</td>
					<td class="lineCell2">' . nl2br($row['line'], 1) . '</td>
				</tr>
				<tr>
					<td class="lineCell3" rowspan="2">Notes:</td>
					<td class="lineCell4">' . $row['notes'] . '</td>
				</tr>
			</table>
		</td>
		<td class="lineButtonColumn" style="background-color:#';
				if($completed){
					$lines .= 'B1FF99';
				}elseif($deleted){
					$lines .= 'FF7070';
				}else{
					$lines .= 'EEE';
				}
				$lines .= '">
			<div class="relative">
				<div class="lineCount" style="top:-5px">' . $lineNumbering . '</div>
			</div>
			';
				if(isset($_SESSION['listRoleId']) && $_SESSION['listRoleId'] > 1){
					if($deleted){
						$lines .= '	<div>Deleted</div>
			';
					}
					if($completed){
						$lines .= '	<div>Completed</div>';
					}
				}
				$lines .= '				</td>
						</tr>
					</table>
					<div class="relative" id="editLineHolderAfterThis' . $row['lineId'] . '">
						<div class="lineCount" style="bottom:2px">
							Line #' . $row['lineId'] . '
						</div>
					</div>';
			}
		}
		$output .= $tcBad ? $lineNumbering . ' lines have invalid timecode values.' : '';
		$output .= $lines;
		$success = MODE == 'tcValidateAll' ? true : false;
		$returnThis['tcValidateAll'] = $tcBad ? $output : 'All lines have valid timecode.';
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'tcValidateAll'){
		returnData();
	}else{
		return $output;
	}
}

function tcValidateSave(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','$_POST[\'lineId\'] is empty.');
		}
		$saveStmt = $Dbc->prepare("UPDATE
	linesTable
SET
	tcIn = ?,
	tcOut = ?
WHERE
	lineId = ?");
		$saveStmtParams = array($_POST['tcValidateIn'],$_POST['tcValidateOut'],$_POST['lineId']);
		$saveStmt->execute($saveStmtParams);
		$message = 'Saved. ';
		$success = MODE == 'tcValidateSave' ? true : false;
		$returnThis['tcValidateAll'] = tcValidateAll();
		$returnThis['buildLines'] = buildLines();
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'tcValidateSave'){
		returnData();
	}
}

function undeleteCharacter(){
	global $debug, $message, $success, $Dbc, $returnThis;
	try{
		if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['charId'])){
			throw new Adrlist_CustomException('','$_POST[\'charId\'] is empty.');
		}
		$stmt = $Dbc->prepare("UPDATE
	characters
SET
	characters.mId = ?,
	characters.modified = ?,
	characters.dId = NULL,
	characters.deleted = NULL
WHERE
	characters.charId = ?");
		$stmt->execute(array($_SESSION['userId'],DATETIME,$_POST['charId']));
		updateListHist($_SESSION['listId']);
		if(MODE == 'undeleteCharacter'){
			$success = true;
			$returnThis['buildLines'] = buildLines();
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'undeleteCharacter'){
		returnData();
	}
}

function undeleteLine(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		if(!isset($_SESSION['listRoleId']) || $_SESSION['listRoleId'] < 2){
			throw new Adrlist_CustomException("Your role doesn't allow you to edit this list.",'');
		}elseif(empty($_POST['lineId'])){
			throw new Adrlist_CustomException('','lineId is empty.');
		}elseif(empty($_POST['charId'])){
			throw new Adrlist_CustomException('','charName is empty.');
		}
		$Dbc->beginTransaction();
		$updateDeletedLinesStmt = $Dbc->prepare("UPDATE
	linesTable
SET
	linesTable.dId = NULL,
	linesTable.deleted = NULL
WHERE
	linesTable.lineId = ?");
		$updateDeletedLinesStmt->execute(array(intThis($_POST['lineId'])));
		$updateDeletedCharactersStmt = $Dbc->prepare("UPDATE
	characters
SET
	characters.dId = NULL,
	characters.deleted = NULL
WHERE
	characters.charId = ?");
		$updateDeletedCharactersStmt->execute(array(intThis($_POST['charId'])));
		$Dbc->commit();
		updateListHist($_SESSION['listId']);
		if(MODE == 'undeleteLine'){
			$success = true;
			$returnThis['buildLines'] = buildLines();
		}
	}catch(Adrlist_CustomException $e){
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'undeleteLine'){
		returnData();
	}
}