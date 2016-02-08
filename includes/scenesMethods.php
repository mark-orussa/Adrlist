<?php /*
For keeping track of scenes.
*/
$fileInfo = array('fileName' => 'includes/scenesMethods.php');
$debug->newFile($fileInfo['fileName']);
$success = false;
if(MODE == 'buildScenes'){
	buildScenes();
}else{
	$debug->add('No matching mode in ' . $fileInfo['fileName'] . '.');
}


function buildAddScene(){
	global $debug, $message, $success;
	$output = '<div class="break relative" style="width:100%">
	<div class="rowTitle" style="width:100px">Scene</div>
	<div class="rowTitle" style="width:100px">Takes</div>
	<div class="rowTitle" style="width:120px">Date</div>
	<div class="rowTitle" style="width:100px">Circle Take</div>
	<div class="rowTitle" style="width:300px">Notes</div>
</div>
<div class="break relative" style="width:100%">
	<div class="row" style="width:100px"><input autocapitalize="on" autocorrect="off" id="newScene" type="text" size="10"></div>
	<div class="row" style="width:100px"><input autocapitalize="off" autocorrect="off" id="newTakes" type="number" size="3"></div>
	<div class="row" style="width:120px"><input autocapitalize="off" autocorrect="off" id="newDate" type="number" size="3"></div>
	<div class="row" style="width:100px"><input autocapitalize="off" autocorrect="off" id="newCircleTake" type="number" size="3"></div>
	<div class="row" style="width:300px"><textarea id="newNotes" style="height:2em; width:300px"></textarea></div>
	<div class="middle row" style="width:30px"><span class="linkPadding" id="addScene">Add</span></div>
</div>
<div class="break red textCenter" id="addSceneResponse"></div>
';
	return $output;
	}

function buildScenes(){
	global $debug, $message, $success;
	$output = '<div class="break relative" style="width:100%">
	<div class="rowTitle" style="width:100px">Scene</div>
	<div class="rowTitle" style="width:100px">Takes</div>
	<div class="rowTitle" style="width:120px">Date</div>
	<div class="rowTitle" style="width:100px">Circle Take</div>
	<div class="rowTitle" style="width:300px">Notes</div>
</div>
';
	$getScenesQuery = "SELECT
	scene AS 'scene',
	takes AS 'takes',
	scenDatetime AS 'date',
	circleTake AS 'circleTake',
	notes AS 'notes'
FROM
	scenes";
	if($result = mysql_query($getScenesQuery)){
		if(mysql_affected_rows() == 0){
			$output .= 'There are no scenes.';
			pdoError(__LINE__, $getScenesQuery, '$getScenesQuery', 1);
		}else{
			$success = true;
			$message .= '';
			$test = array('49', 'A63', 'A124A', 'A124', '124', '124A');
			$debug->printArray($test, '$test');
			natcasesort($test);
			$debug->printArray($test, '$test after natcasesort');
			$scenes = array();
			$scenesFolders = array();
			while($row = mysql_fetch_assoc($result)){
				$scenes[$row['scene']] = $row;
				$scenesFolders[preg_replace('/\D*/','', $row['scene'])][] = $row['scene'];
			}
			$debug->printArray($scenes, '$scenes');
			ksort($scenesFolders);//Sort array by keys.
			$debug->printArray($scenesFolders, '$scenesFolders after processing');
			$class = 'rowWhite';
			foreach($scenesFolders as $key => $value){
				natcasesort($value);//Sort scenes using natural order.
				foreach($value as $key2 => $value2){
					if($class == 'rowWhite'){
						$class = 'rowAlt';
					}else{
						$class = 'rowWhite';
					}
					$output .= '	<div class="break relative ' . $class . '">
				<div class="row" style="width:100px">' . $scenes[$value2]['scene'] . '</div>
			<div class="row" style="width:100px">' . $scenes[$value2]['takes'] . '</div>
			<div class="row" style="width:120px">' . $scenes[$value2]['date'] . '</div>
			<div class="row" style="width:100px">' . $scenes[$value2]['circleTake'] . '</div>
			<div class="row" style="width:300px">' . $scenes[$value2]['notes'] . '</div>
		</div>
	';
				}
			}
		}
	}else{
		error(__LINE__);
		pdoError(__LINE__, $getScenesQuery, '$getScenesQuery');
	}
	if(MODE == 'buildScenes'){
		$returnThis['returnBuildScenes'] = $output;
		returnData();
	}else{
		return $output;
	}
}