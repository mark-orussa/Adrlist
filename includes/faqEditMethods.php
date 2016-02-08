<?php /*
This script and site designed and built by Mark O'Russa, Mark Pro Audio Inc. Copyright 2008-2013.
This file and it's functions are to be used solely by ../admin/faqEdit.php in conjunction with ../js/faqEdit.js.
*/
require_once('siteAdmin.php');
$fileInfo = array('fileName' => 'includes/faqEditMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'addFaq'){
	addFaq();
}elseif(MODE == 'addTopic'){
	addTopic();
}elseif(MODE == 'buildFaqs'){
	buildFaqs();
}elseif(MODE == 'changeFaqTopic'){
	changeFaqTopic();
}elseif(MODE == 'deleteFaq'){
	deleteFaq();	
}elseif(MODE == 'deleteTopic'){
	deleteTopic();	
}elseif(MODE == 'modifyFaq'){
	modifyFaq();
}elseif(MODE == 'modifyTopic'){
	modifyTopic();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function addFaq(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(empty($_POST['chooseTopicDropDownVal'])){
		error(__LINE__,'','$_POST[\'chooseTopicDropDownVal\'] is empty.');
	}else{
		try{
			$stmt = $Dbc->prepare("INSERT INTO faqs
SET topicId = ?,
	q = ?,
	a = ?");
			$stmt->execute(array($_POST['chooseTopicDropDownVal'],$_POST['addQVal'],$_POST['addAVal']));
			$success = MODE == 'addFaq' ? true : $success;
			$message .= 'FAQ added.';
			$returnThis['returnCode'] = buildFaqs();
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
	if(MODE == 'addFaq'){	
		returnData();
	}
}

function addTopic(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(empty($_POST['addTopicVal'])){
		error(__LINE__,'','$_POST[\'addTopicVal\'] is empty.');
	}else{
		try{
			$stmt = $Dbc->prepare("INSERT INTO faqTopics
SET
	topic = ?");
			$stmt->execute(array($_POST['addTopicVal']));
			$success = MODE == 'addTopic' ? true : $success;
			$message .= 'Added topic.';
			$returnThis['buildFaqs'] = buildFaqs();
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}			
	if(MODE == 'addTopic'){	
		returnData();
	}
}

function buildFaqs(){
	//Build the "add faq" section.
	global $debug, $message, $Dbc;
	$output = '<div class="center relative">
		Create a new FAQ item:
		<div class="break textCenter border roundedCorners" style="padding:5px;">
			<div class="left">
				Add a new topic: <input autocapitalize="on" autocorrect="off" id="addTopic" type="text" size="20"> <input id="addTopicButton" type="button" value="Add Topic">
			</div>
			<div class="right">Delete a topic: <span id="deleteTopicSpan">' . topicsDropDown('deleteTopicDropDown', '', '') . ' </span><input id="deleteTopicButton" type="button" value="Delete Topic">
			</div>
			<div class="textLeft break" style="padding-top:10px;">
				Choose a topic: <span id="chooseTopicSpan">' . topicsDropDown('chooseTopicDropDown', '', '') . '</span>
			</div>
			<div class="break">
				<span class="faqEditQ" style="vertical-align:top">Q:&nbsp;</span><textarea class="faqEditQuestion border blue" id="addQ" rows="2"></textarea>
			</div>
			<span class="faqEditA" style="vertical-align:top">A:&nbsp;</span><textarea class="faqEditAnswer" id="addA" rows="5"></textarea>
			<div class="textCenter"><button id="addFaqButton" type="button">Add New FAQ</button></div>
		</div>
	</div>
	';
	//Now the faqs.
	$faqs = array();
	$topics = getTopics();
	try{
		$stmt = $Dbc->query("SELECT
	faqTopics.topicId AS 'topicId',
	faqTopics.topic AS 'topic',
	faqs.faqId AS 'faqId',
	faqs.q AS 'q',
	faqs.a AS 'a'
FROM
	faqs
JOIN faqTopics ON faqs.topicId = faqTopics.topicId
ORDER BY faqTopics.topic");
		$lastTopic = '';
		$bgColor = '';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$row = charToHtml($row);//Certain characters need to be converted to prevent breaking html code. " & ' < >
			$middle = '<input autocapitalize="off" autocorrect="off" id="topic' . $row['topicId'] . '" type="text" size="18" value="' . $row['topic'] . '" class="topic"> <input id="topicId' . $row['topicId'] . '" topicid="' . $row['topicId'] . '" type="button" value="Modify Topic">';
			if($row['topic'] != $lastTopic && !empty($lastTopic)){
				$output .= '	</ul>
			</li>
			<li class="relative">		
				' . $middle . '
			';
			}elseif($row['topic'] != $lastTopic){
				$output .= '	<ol>
			<li class="relative">
				' . $middle . '
			';
			}
			//Create the Q & A section.
			if($bgColor == COLORGRAY){
				$bgColor = 'white';
			}else{
				$bgColor = COLORGRAY;
			}
		$output .= '		<div class="faqEdit roundedCorners" id="faqId' . $row['faqId'] . '" style="background-color:#' . $bgColor . ';">
						<div>
							<span class="faqEditQ">Q:&nbsp;</span><textarea class="faqEditQuestion"  style="background-color:#' . $bgColor . '"id="q' . $row['faqId'] . '" rows="2">' . $row['q'] . '</textarea>
						</div>
						<div>
							<span class="faqEditA">A:&nbsp;</span><textarea class="faqEditAnswer"  style="background-color:#' . $bgColor . '"id="a' . $row['faqId'] . '" rows="5">' . $row['a'] . '</textarea>
						</div>
						<div>See also: <input id="seeAlso' . $row['faqId'] . '" style="width:"10em"></div>
						<div class="faqEditId">FAQ Id: ' . $row['faqId'] . '</div>
						<div class="textCenter">
							<span class="textSmall">Move to topic:</span>';
							$output .='		<select id="topicDropDown' . $row['faqId'] . '">
											';
							foreach($topics as $key2 => $row2){
								$output .= '<option value="' . $key2 . '"';
								if($row2 == $row['topic']){
									$output .= ' selected="selected"';
								}
								$output .= '>' . $row2 . '</option>';
							}
							$output .= '
										</select>
									';
							$output .= '<input id="modifyFaq' . $row['faqId'] . '" faqid="' . $row['faqId'] . '" type="button" value="Modify FAQ">&nbsp;<input id="deleteFaq' . $row['faqId'] . '" type="button" value="Delete FAQ">
						</div>
					</div>
			';
			$lastTopic = $row['topic'];
		}
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	$debug->printArray($faqs, '$faqs');
	$output .= '			</li>
		</ol>
		<div>&nbsp;</div>
	';
	return $output;
}

function changeFaqTopic(){
	//Changes the topic of a single FAQ.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(!empty($_POST['newTopicId']) && !empty($_POST['faqId'])){
		try{
			$stmt = $Dbc->prepare("UPDATE
			faqs
		SET
			topicId = ?
		WHERE
			faqId = ?");
			$stmt->execute(array($_POST['newTopicId'],$_POST['faqId']));
			$success = MODE == 'changeFaqTopic' ? true : $success;
			$returnThis['returnCode'] = buildFaqs();
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}else{
		error(__LINE__);
		if(empty($_POST['newTopicId'])){
			$debug->add('$_POST[\'newTopicId\'] is empty.');
		}elseif(empty($_POST['faqId'])){
			$debug->add('$_POST[\'faqId\'] is empty.');
		}else{
			$debug->add('Something else is wrong.');	
		}
	}
	if(MODE == 'changeFaqTopic'){	
		returnData();
	}
}

function deleteFaq(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(empty($_POST['faqId'])){
		error(__LINE__,'','$_POST[\'faqId\'] is empty.');
	}else{
		try{
			$stmt = $Dbc->prepare("DELETE
	faqs
FROM
	faqs
WHERE
	faqs.faqId = ?");
			$params = array($_POST['faqId']);
			$stmt->execute($params);
			$affectedLines = $stmt->rowCount();
			if(empty($affectedLines)){
				$message .= "No records were found for faqId: $faqId<br>";
				pdoError(__LINE__, $stmt, $params, true);
			}else{
				$message .= 'The faq (Id: ' . $_POST['faqId'] . ') was successfully deleted.<br>';
				$success = MODE == 'deleteFaq' ? true : $success;
				$returnThis['buildFaqs'] = buildFaqs();
			}
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
	if(MODE == 'deleteFaq'){	
		returnData();
	}
}

function deleteTopic(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(empty($_POST['topicId'])){
		error(__LINE__,'','$_POST[\'topicId\'] is empty.');
	}else{
		try{
			//First, see if the topic is being used.
			$stmt = $Dbc->prepare("SELECT
	faqId AS 'faqId'
FROM
	faqs
WHERE
	topicId = ?");
			$stmt->execute(array($_POST['topicId']));
			$faqId = array();
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$faqId[] = $row['faqId'];
			}
			if(empty($faqId)){
				$stmt = $Dbc->prepare("DELETE FROM faqTopics WHERE faqTopics.topicId = ?");
				$stmt->execute(array($_POST['topicId']));
				$message .= "The topic was successfully deleted.<br>";
				$success = MODE == 'deleteTopic' ? true : $success;
				$returnThis['buildFaqs'] = buildFaqs();
			}else{
				if(count($faqId) == 1){
					$message .= 'This topic is used by FAQ Id: ' . $faqId[0] . '. Please change the topic of this FAQ before deleting the topic.<br>';
				}else{
					$message .= 'This topic is used by FAQ Ids: ';
					$x = 1;
					foreach($faqId as $key => $value){
						if($x == 1){
							$message .= $value;
						}else{
							$message .= ", $value";
						}
						$x++;
					}
					$message .= '. Please change the topic of these FAQs before deleting the topic.<br>';
				}
			}
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
	if(MODE == 'deleteTopic'){	
		returnData();
	}
}

function getTopics(){
	/*Returns an array with the topics as follows:
	array (
		0 => 
		array (
			'topicId' => '1',
			'topic' => 'Editing ADR Lists'
		)
	)
	*/
	global $debug, $message, $Dbc;
	$topics = array();
	try{
		$stmt = $Dbc->query("SELECT
	faqTopics.topicId AS 'topicId',
	faqTopics.topic AS 'topic'
FROM
	faqTopics
ORDER BY faqTopics.topic ASC");
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$topics[$row['topicId']] = $row['topic'];
		}
		return $topics;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
}

function modifyFaq(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(empty($_POST['faqQ'])){
		error(__LINE__,'','$_POST[\'faqQ\'] is empty.');
	}elseif(empty($_POST['faqA'])){
		error(__LINE__,'','$_POST[\'faqA\'] is empty.');
	}elseif(empty($_POST['faqId'])){
		error(__LINE__,'','$_POST[\'faqId\'] is empty.');
	}else{
		//Update the faq.
		try{
			$stmt = $Dbc->prepare("UPDATE
	faqs
SET
	faqs.q = ?,
	faqs.a = ?
WHERE
	faqs.faqId = ?");
			$stmt->execute(array($_POST['faqQ'],$_POST['faqA'],$_POST['faqId']));
			//Mysql does not return affected rows when the value does not change, so we won't check for it here.
			//Get the newly updated faqs.
			$stmt = $Dbc->prepare("SELECT
	faqs.faqId as 'faqId',
	faqs.q AS 'q',
	faqs.a AS 'a'
FROM
	faqs
WHERE
	faqs.faqId = ?");
			$params = array($_POST['faqId']);
			$stmt->execute($params);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if(empty($row)){
				$message .= 'No records were found for faqId: ' . $_POST['faqId'] . '<br>';
				pdoError(__LINE__, $stmt, $params, 1);
			}else{
				$message .= 'Saved';
				$success = MODE == 'modifyFaq' ? true : $success;
				$returnThis['returnQ'] = $row['q'];
				$returnThis['returnA'] = $row['a'];
			}
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
	if(MODE == 'modifyFaq'){	
		returnData();
	}
}

function modifyTopic(){
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	if(empty($_POST['topic'])){
		$message .= 'Please enter a topic.';
	}else{
		try{
			$stmt = $Dbc->prepare("UPDATE
	faqTopics
SET
	faqTopics.topic = ?
WHERE
	faqTopics.topicId = ?
LIMIT 1");
			$stmt->execute(array($_POST['topic'],$_POST['topicId']));
			$stmt = $Dbc->prepare("SELECT
	faqTopics.topic AS 'newTopic'
FROM
	faqTopics
WHERE
	faqTopics.topicId = ?");
			$stmt->execute(array($_POST['topicId']));
			$message .= 'Saved';
			$success = MODE == 'modifyTopic' ? true : $success;
			$returnThis['buildFaqs'] = buildFaqs();
		}catch(PDOException $e){
			error(__LINE__,'','<pre>' . $e . '</pre>');
		}
	}
	if(MODE == 'modifyTopic'){	
		returnData();
	}
}

function topicsDropDown($uniqueId, $topics = false, $selected = false){
	/*
	$uniqueId = A unique Id for the select.
	$topics = An array for the options.
	$selected = A variable to match an option to be selected.
	Returns an html drop down list.
	*/
	global $debug, $message;
	$output ='		<select id="' . $uniqueId . '">
					';
	if(empty($topics)){
		$topics = getTopics();
	}
	foreach($topics as $key => $value){
		$output .= '<option value="' . $key . '"';
		if(!empty($selected) && $value == $selected){
			$output .= ' selected="selected"';
		}
		$output .= '>' . $value . '</option>';
	}
	$output .= '
				</select>
';
	return $output;
}