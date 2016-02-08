<?php require_once('siteAdmin.php');
// This file is solely for transferring the old mysql lists to this new system.
if($_POST['mode'] == 'convertHist'){
	if(!empty($_POST['fromTable']) && !empty($_POST['toTable']) && !empty($_POST['id'])){
		convertHist($_POST['fromTable'], $_POST['toTable'], $_POST['id']);
	}else{
		$debug->add('No table or id is set.');
	}
}
if($_POST['mode'] == 'getOldLines'){
	getOldLines();
}

function oldDB(){
	global $debug, $message, $Dbc;
	if($Dbc){
		mysql_close();
	}
	if($DbcConnection = @mysql_connect('mysql408.ixwebhosting.com', 'churiou_marko', 'aaronscizor5')){
		//$debug->add('Successful connection to the mysql server.');
		if(@mysql_select_db('churiou_scizors')) {
			//$debug->add("Successful connection to the database: $database.");
			define('oldDBC', 1);
			return true;
		}else{
			$debug->add("Could not connect to the database 'churiou_scizors' because:<span class=\"bold\">" . mysql_error() . '</span>.');
			$message .= 'We apologize, but there is a technical problem which is preventing this information from being displayed. Please try again later.<br>
			<br>
			<a href="" onClick="createEmailAddress(' . "'" . EMAILDONOTREPLY . "', 'Regarding a technical problem preventing information from being displayed.', 'The error occured in " . $_SERVER['PHP_SELF'] . ".'); return false;\">You may contact the webmaster regarding this error by clicking here.</a>";
			define('oldDBC', 0);
			return false;
		}
	}else{
		$debug->add("Could not connect to the database 'churiou_scizors' because:<span class=\"bold\">" . mysql_error() . '</span>.');
		$message .= 'We apologize, but there is a technical problem which is preventing this information from being displayed. Please try again later.<br>
		<br>
		<a href="" onClick="createEmailAddress(' . "'" . EMAILDONOTREPLY . "', 'Regarding a technical problem preventing information from being displayed.', 'The error occured in " . $_SERVER['PHP_SELF'] . ".'); return false;\">You may contact the webmaster regarding this error by clicking here.</a>";
		define('oldDBC', 0);
		return false;
	}
}

//Convert the old 
function convertHist($fromTable, $toTable, $id){
	global $debug, $message, $Dbc;
	$success = false;
	$output = '';
	$getExisting = "SELECT
	$id AS '$id',
	userId AS 'userId',
	statusId AS 'statusId',
	statusDate AS 'statusDate'
FROM
	$fromTable
ORDER BY
	$id, statusId";
	if($Dbc){
		if($result = mysql_query($getExisting)){
			if(mysql_affected_rows() == 0){
				$debug->add('Zero rows were affected by the query on line ' . __LINE__ . ": $getExisting.");
			}else{
				while($row = mysql_fetch_assoc($result)){
					if($row['statusDate'] == NULL){
						$userId = 'NULL';
						$statusDate = 'NULL';
					}else{
						$userId = "'" . $row['userId'] . "'";
						$statusDate = "'" . $row['statusDate'] . "'";
					}
					if($row['statusId'] == '1'){
						$updateQuery = "UPDATE $toTable
SET
	cId = '$userId',
	created = $statusDate
WHERE
	$id = '" . $row[$id] . "'";
					}elseif($row['statusId'] == '2'){
						$updateQuery = "UPDATE $toTable
SET
	mId = '$userId',
	modified = $statusDate
WHERE
	$id = '" . $row[$id] . "'";
					}elseif($row['statusId'] == '3'){
						$updateQuery = "UPDATE $toTable
SET
	dId = '$userId',
	deleted = $statusDate
WHERE
	$id = '" . $row[$id] . "'";
					}
					if(mysql_query($updateQuery)){
						$success = true;
					}else{
						error(__LINE__);
						$debug->add('There is an error with the query on line ' . __LINE__ . ": $updateQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span>');
						break;
					}
				}
			}
		}else{
			error(__LINE__);
			$debug->add('There is an error with the query on line ' . __LINE__ . ": $getExisting<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span>');
		}
	}
			
	returnData();
}

//Transfer from the old scizors adr lists.
function getOldLines(){
	global $debug, $message, $Dbc;
	$success = false;
	$output = '';
	if(oldDB()){
		$oldLines = array();
		$oldLinesSelect = "SELECT
	adr_lines.line_id AS 'lineId',
	adr_lines.list_id AS 'listId',
	adr_lines.character AS 'char',
	adr_lines.reel_number AS 'reel',
	adr_lines.scene_number AS 'scene',
	adr_lines.tc_in AS 'tcIn',
	adr_lines.tc_out AS 'tcOut',
	adr_lines.linesTable AS 'line',
	adr_lines.notes AS 'notes',
	adr_lists.list_name AS 'listName'
FROM
	adr_lines
JOIN
	adr_lists ON adr_lines.list_id = adr_lists.list_id";
		if($result = mysql_query($oldLinesSelect)){
			if(mysql_affected_rows() == 0){
				$debug->add('Zero rows were affected by the query on line ' . __LINE__ . ": $oldLinesSelect.");
			}else{
				$debug->add('Entering loop.');
				while($row = mysql_fetch_assoc($result)){
					$lineHistQuery = "SELECT
	adr_lines_history.status AS 'statusId',
	adr_lines_history.status_date AS 'statusDate',
	users.user_id AS 'userId'
FROM
	adr_lines_history
JOIN
	users ON users.user_id = adr_lines_history.user_id AND
	adr_lines_history.line_id = '" . $row['lineId'] . "'";
					if($result2 = mysql_query($lineHistQuery)){
						if(mysql_affected_rows() == 0){
							$debug->add('Zero rows were affected by the query on line ' . __LINE__ . ": $lineHistQuery on line.");
						}else{
							while($row2 = mysql_fetch_assoc($result2)){
								if($row2['statusId'] == 1){
									$row['creatorId'] = $row2['userId'];
									$row['created'] = $row2['statusDate'];
								}elseif($row2['statusId'] == 2){
									$row['modifierId'] = $row2['userId'];
									$row['modified'] = $row2['statusDate'];
								}elseif($row2['statusId'] == 3){
									$row['deleterId'] = $row2['userId'];
									$row['deleted'] = $row2['statusDate'];
								}
							}
						}
					}else{
						error(__LINE__);
						$debug->add('There is an error with the query on line ' . __LINE__ . ": $lineHistQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span>');
					}
					$oldLines[] = $row;
				}
				mysql_close();
				$debug->add('Successfully retrieved old lines.');
				$x = 0;
				if($Dbc){
					//Insert line info into new db.
					foreach($oldLines as $key => $value){
						$x++;
						$insertLinesQuery = "INSERT INTO
	linesTable
SET
	reel = '" . $value['reel'] . "',
	scene = '" . $value['scene'] . "',
	tcIn = '" . $value['tcIn'] . "',
	tcOut = '" . $value['tcOut'] . "',
	line = '" . mysqlSafe($value['line']) . "',
	notes = '" . mysqlSafe($value['notes']) . "'";
						if(mysql_query($insertLinesQuery)){
							if(mysql_affected_rows() == 0){
								die('Zero rows were affected by the query on line ' . __LINE__ . ": $insertLinesQuery <br>
");
							}else{
								$lineId = mysql_insert_id();
								//Insert line history info into new db.
								//These lines will make sure values are created even if they weren't in the old list.
								$value['creator'] = !isset($value['creator']) ? '1' : $value['creator'];
								$value['created'] = !isset($value['created']) ? "'" . '2011-03-30 17:00:00' . "'" : "'" . $value['created'] . "'";
								$value['modifierId'] = !isset($value['modifierId']) ? '1' : $value['modifierId'];
								$value['modified'] = !isset($value['modified']) ? "'" . '2011-03-30 17:00:00' . "'" : "'" . $value['modified'] . "'";
								$value['deleterId'] = !isset($value['deleterId']) ? '1' : $value['deleterId'];
								$value['deleted'] = !isset($value['deleted']) ? 'NULL' : "'" . $value['deleted'] . "'";
								$insertLineHistQuery = "INSERT INTO
	lineHist (lineId, userId, statusId, statusDate)
VALUES
	('$lineId', '" . $value['creatorId'] . "', '1', " . $value['created'] . "),
	('$lineId', '" . $value['modifierId'] . "', '2', " . $value['modified'] . "),
	('$lineId', '" . $value['deleterId'] . "', '3', " . $value['deleted'] . ")";
								if(mysql_query($insertLineHistQuery)){
									if(mysql_affected_rows() == 0){
										die('Zero rows were affected by the query on line ' . __LINE__ . ": $insertLineHistQuery<br>
");
									}else{
										$lineListMapQuery = "INSERT INTO
	lineListMap
SET
	listId = '" . $value['listId'] . "',
	lineId = '$lineId'";
										if(mysql_query($lineListMapQuery)){
											if(mysql_affected_rows() == 0){
												die('Zero rows were affected by the query on line ' . __LINE__ . ": $lineListMapQuery <br>
");
											}else{
												//Check to see if the char already exists.
												$charCheckQuery = "SELECT
	characters.charId AS 'charId'
FROM
	characters
WHERE
	characters.charFirstName = '" . mysqlSafe($value['char']) . "' AND
	characters.listId = '" . $value['listId'] . "'";
												if($charCheckResult = mysql_query($charCheckQuery)){
													if(mysql_affected_rows() == 0){
														$debug->add('Zero rows were affected by the query on line ' . __LINE__ . ": $charCheckQuery ");
														$insertCharQuery = "INSERT INTO
	characters
SET
	listId = '" . $value['listId'] . "',
	charFirstName = '" . mysqlSafe($value['char']) . "'";
														if(mysql_query($insertCharQuery)){
															if(mysql_affected_rows() == 0){
																die('Zero rows were affected by the query on line ' . __LINE__ . ": $insertCharQuery <br>
");
															}else{
																$charId = mysql_insert_id();
																//Create the char history.
																$charHistQuery = "INSERT INTO
	charHist (charId, userId, statusId, statusDate)
VALUES
	('$charId', '" . $value['creatorId'] . "', '1', " . $value['created'] . "),
	('$charId', '" . $value['modifierId'] . "', '2', " . $value['modified'] . "),
	('$charId', '" . $value['deleterId'] . "', '3', " . $value['deleted'] . ")";
																if(mysql_query($charHistQuery)){
																	if(mysql_affected_rows() == 0){
																		die('Zero rows were affected by the query on line ' . __LINE__ . ": $charHistQuery <br>
");
																	}
																}else{
																	error(__LINE__);
																	die('There is an error with the query on line ' . __LINE__ . ": $charHistQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span><br>
');
																}
															}
														}else{
															error(__LINE__);
															die('There is an error with the query on line ' . __LINE__ . ": $insertCharQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span><br>
');
														}
													}else{
														$row3 = mysql_fetch_assoc($charCheckResult);
														$charId = $row3['charId'];
													}
													//Create the character to line link.
													$charLineMapQuery = "INSERT INTO
	charLineMap
SET
	charId = '$charId',
	lineId = '$lineId'";
													if(mysql_query($charLineMapQuery)){
														if(mysql_affected_rows() == 0){
															die('Zero rows were affected by the query on line ' . __LINE__ . ": $charLineMapQuery <br>
");
														}else{
															$success = true;	
														}
													}else{
														error(__LINE__);
														die('There is an error with the query on line ' . __LINE__ . ": $charLineMapQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span><br>
');
													}
												}else{
													error(__LINE__);
													die('There is an error with the query on line ' . __LINE__ . ": $charCheckQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span><br>
');
												}
											}
										}else{
											error(__LINE__);
											die('There is an error with the query on line ' . __LINE__ . ": $lineListMapQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span><br>
');
										}
									}
								}else{
									error(__LINE__);
									die('There is an error with the query on line ' . __LINE__ . ": $insertLineHistQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span><br>
');
								}
							}
						}else{
							error(__LINE__);
							die('There is an error with the query on line ' . __LINE__ . ": $insertLinesQuery<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span><br>
');
						}
					}
				}				
				//$debug->printArray($oldLines,'$oldLines');
			}
		}else{
			error(__LINE__);
			$debug->add('There is an error with the query on line ' . __LINE__ . ": $oldLinesSelect<br>
The error is: <span class=\"bold\">" . mysql_error() . '</span>');
		}
	}
			
	returnData();
}
