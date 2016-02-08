<?php
class Adrlist_UserManagement{
	/**
	 * Manage users in the admin section.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	*/
	
	//Properties.
	
	public function __construct(){
	}
	
	public function buildUsers(){
		/**
		 * Build a list of users.
		 *
		 * This builds a list of users with options to perform changes to their account.
		 *
		 * @author	Mark O'Russa	<mark@markproaudio.com>
		 *
		 * @return	boolean	Returns a list of users with pagination, otherwise throws a customException.
		*/
		global $debug, $message, $Dbc;
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
			$pagination = new Adrlist_Pagination('buildUsers','buildUsers',$itemCount,'Search Users');
			list($offset,$limit) = $pagination->offsetLimit();
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
			return $pagination['paginationTop'] . $buildRows->outputTitleRow() . $buildRows->outputRows() . $pagination['paginationBottom'];
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			error(__LINE__,'','<pre class="red">' . $e . '</pre>');
		}
	}
}
