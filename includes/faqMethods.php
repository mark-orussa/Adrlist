<?php $fileInfo = array('fileName' => 'includes/faqMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'buildFaqs'){
	buildFaqs();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}

function buildFaqs(){
	//This gets FAQs with and without searching.
	global $debug, $message, $success, $Dbc, $returnThis;
	$output = '';
	try{
		$faqQuery = "SELECT
	faqs.faqId AS 'faqId',
	faqs.q AS 'q',
	faqs.a AS 'a',
	faqTopics.topicId AS 'topicId',
	faqTopics.topic AS 'topic'
FROM
	faqs
JOIN
	faqTopics ON faqTopics.topicId = faqs.topicId AND
	faqs.hidden = '0'
";
		if(empty($_POST['searchVal'])){
			$search = false;
			$stmt = $Dbc->query($faqQuery . "
ORDER BY
	faqTopics.topic");
			$faqCountStmt = $Dbc->query("SELECT COUNT(*) AS 'count' FROM faqs WHERE hidden = 0");
		}else{
			$search = true;
			$searchVal = '%' . trim($_POST['searchVal']) . '%';
			$faqSearchQuery = "
 AND (faqTopics.topic LIKE ? || faqs.q LIKE ? || faqs.a LIKE ?)
GROUP BY
	faqs.faqId";
			$stmt = $Dbc->prepare($faqQuery . $faqSearchQuery);
			$stmt->execute(array($searchVal,$searchVal,$searchVal));
			$faqCountStmt = $Dbc->prepare("SELECT COUNT(*) AS 'count' FROM faqs JOIN
	faqTopics ON faqTopics.topicId = faqs.topicId AND
	faqs.hidden = '0'" . $faqSearchQuery);
			$faqCountStmt->execute(array($searchVal,$searchVal,$searchVal));
		}
		$row = $faqCountStmt->fetch(PDO::FETCH_ASSOC);
		$itemCount = empty($row['count']) ? 0 : $row['count'];
		$debug->add('$itemCount: ' . $itemCount);
		$success = true;
		$lastTopic = '';
		$topicNumber = 0;
		$foundRows = false;
		$rowsArray = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$foundRows = true;
			$question = convertFaqLink($row['q']);
			$answer = convertFaqLink($row['a']);
			$currentTopic = $row['topic'];
			if($currentTopic != $lastTopic){
				$topicNumber++;
				$lastTopic = $currentTopic;
			}
			$rowsArray[$currentTopic][] = array('<div class="faq hand" faqid="' . $row['faqId'] . '">
	<div class="faqQuestion" id="faq' . $row['faqId'] . '" toggle="faqAnswer' . $row['faqId'] . '">' . nl2br($question, 1) . ' <span class="faqId">FAQ #' . $row['faqId'] . '</span></div>
	<div class="faqAnswer textLeft" id="faqAnswer' . $row['faqId'] . '">' . nl2br($answer, 1) . '</div>
</div>
');
		}
		//$debug->printArray($rowsArray,'$rowsArray');
		$cssWidths = array('100%');
		$temp = $search ? '<div class="red textCenter">Results for "' . $_POST['searchVal'] . '"</div>' : '';
		foreach($rowsArray as $key => $value){
			$temp .= '<div class="sectionTitle textCenter">' . $key . '</div>
';
			$faqRows = new Adrlist_BuildRows('faqs','',$value,false);
			$temp .= $faqRows->output();
		}
		$pagination = new Adrlist_Pagination('buildFaqs','buildFaqs',$itemCount,'Search FAQs',$search);
		$pagination->_searchOnly();
		$output .= '<div class="textCenter">
	Click on a topic to view FAQs
	<div class="break" style="margin:1em">
		<button class="ui-btn ui-btn-inline ui-btn-a ui-shadow ui-corner-all" id="faqHideAll" data-role="false">Hide All</button><button class="ui-btn ui-btn-inline ui-btn-a ui-shadow ui-corner-all" id="faqShowAll" data-role="false">Show All</button>
	</div>
</div>
' . $pagination->output() . $temp;
		if(empty($foundRows)){
			pdoError(__LINE__,$stmt, $params = false, true);
			if($search){
				$output .= '<div class="red textCenter" style="margin:1em">There were no matches for ' . $_POST['searchVal'] . '.</div>';
			}else{
				$output .= '<div class="break" style="padding:5px 0px 10px 0px;">
There are no faqs to show.
</div>		
';
//		<span class="buttonBlueThin" id="faqHideAll">Hide All</span><span class="buttonBlueThin" id="faqShowAll">Show All</button>

			}
		}
		$returnThis['holder'] = 'buildFaqsHolder';
		$returnThis['output'] = $output;
		$returnThis['buildFaqs'] = $output;
	}catch(PDOException $e){
		error(__LINE__,'','<pre>' . $e . '</pre>');
	}
	if(MODE == 'buildFaqs'){
		returnData();
	}else{
		return $output;
	}
}