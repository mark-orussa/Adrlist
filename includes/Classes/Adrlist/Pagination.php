<?php
class Adrlist_Pagination{
	/**
	 * Builds those nifty pagination numbers at the top and bottom of lists.
	 *
	 * This builds page numbers for the top and bottom of a list. An associated database table is required. A count of the items to paginate must be determined manually and input into this class for proper operation. Offset and limit are handled automatically using the database and cookies.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 *
	 * @param	$_itemCount				int		The number of items in the list. Strings and floats are converted to int.
	 * @param	$_offset				int		The starting count number for pagination. Strings and floats are converted to int.
	 * @param	$_limit					string	The number of items to show. Strings and floats are converted to int.
	 * @param	$_pagesNumbersToDisplay	int		
	 * @param	$_uniqueId				int		A unique identifier that allows multiple paginations per page without infering with one another.
	 *
	 * @return 	array	Returns an array with the top and bottom pagination sets. array('paginationTop', 'paginationBottom').
	 *
	*/
	
	//Properties.
	protected $_action;
	protected $_itemCount;
	protected $_offset;
	protected $_limit;
	protected $_uniqueId;
	protected $_defaultSearchValue;
	protected $_additionalSearchParams;
	protected $_pagesNumbersToDisplay;
	protected $_searchOnly;
	protected $_fromSearch;

	public function __construct($action,$uniqueId,$itemCount,$defaultSearchValue = false,$fromSearch = false,$offsetLimit = false){
		/**
		 * Initiate the properties.
		 *
		 * It uses globally available variables to get the offset and limit given the uniqueId and itemCount.
		 *
		 * @param	$action				string	The function or method to call when sending the AJAX search request.
		 * @param	$uniqueId			string	A unique identifier. This is necessary to prevent collision with other search fields. Best practice is to use the name of the calling function to prevent conflicts.
		 * @param	$itemCount			int		The item count for the list. This is used to insure the offset is never greater than the count.
		 * @param	$defaultSearchValue	int		The initial text to display in the search input.
		 * @param	$fromSearch			int		To specify which searchfield has initiated the search.
		 * @param	$offsetLimit		int		If known, this will skip the getOffsetLimit call.
		 *
		*/
		global $debug, $message, $Dbc;
		try{
			if(empty($action)){
				throw new Adrlist_CustomException('','$action is empty.');
			}elseif(empty($uniqueId)){
				throw new Adrlist_CustomException('','$uniqueId is empty.');
			}elseif(!is_numeric($itemCount)){
				throw new Adrlist_CustomException('','$itemCount is not numeric.');
			}
			$itemCount = empty($itemCount) ? 1 : $this->intThis($itemCount);
			if(is_array($offsetLimit)){
				list($offset,$limit) = $offsetLimit;
			}else{
				if(isset($_POST[$uniqueId . 'Offset']) || isset($_POST[$uniqueId . 'Limit'])){
					$debug->add('in 1');
					if(empty($_SESSION['userId'])){
						$debug->add('in 1.5');
						setcookie($uniqueId . 'Offset',$_POST[$uniqueId . 'Offset'],time()+60*60*24*365,COOKIEPATH,COOKIEDOMAIN,false);
						setcookie($uniqueId . 'Limit',$_POST[$uniqueId . 'Limit'],time()+60*60*24*365,COOKIEPATH,COOKIEDOMAIN,false);
					}
					$offset = $_POST[$uniqueId . 'Offset'];
					$limit = empty($_POST[$uniqueId . 'Limit']) ? $itemCount : $_POST[$uniqueId . 'Limit'];
				}else{
					$debug->add('in 1.8');
					if(empty($_SESSION['userId'])){
						$debug->add('in 2');
						if(isset($_COOKIE[$uniqueId . 'Offset'])){
							$offset = $_COOKIE[$uniqueId . 'Offset'];
							$limit = $_COOKIE[$uniqueId . 'Limit'];
						}else{
							$debug->add('in 3');
							$offset = 0;
							$limit = 20;//If the user is not logged in and no previous limit exists, this will set the default.
							setcookie($uniqueId . 'Offset',$offset,time()+60*60*24*365,COOKIEPATH,COOKIEDOMAIN,false);
							setcookie($uniqueId . 'Limit',$limit,time()+60*60*24*365,COOKIEPATH,COOKIEDOMAIN,false);
						}
					}else{
						$debug->add('in 4');
						$offsetLimit = $this->getOffsetLimit($_SESSION['userId'],$uniqueId);
						$offset = $offsetLimit[0];
						$limit = empty($offsetLimit[1]) ? 20 : $offsetLimit[1];//If no previous limit exists, this will set the default.
					}
				}
			}
			$offset = $offset > $itemCount ? 0 : $offset;//When changing list viewing options the offset may be larger than the count.
			$offset = $this->intThis($offset);
			$limit = $this->intThis($limit);
			$debug->add('From Pagination: offset: ' . $offset . ', limit: ' . $limit . ', $itemCount: ' . $itemCount);
			if(!empty($_SESSION['userId'])){
				$debug->add('in 5');
				//Check for an existing record.
				$checkStmt = $Dbc->prepare("SELECT
	paginationId AS 'paginationId'
FROM
	pagination
WHERE
	userId = ? AND
	scriptName = ? AND
	uniqueId = ?");
				$checkParams = array($_SESSION['userId'],$_SERVER['SCRIPT_NAME'],$uniqueId);
				$checkStmt->execute($checkParams);
				$existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
				if($existingRecord){
					$debug->add('in 6');
					$paginationStmt = $Dbc->prepare("UPDATE
	pagination
SET
	scriptName = ?,
	uniqueId = ?,
	pageOffset = ?,
	pageLimit = ?
WHERE
	paginationId = ?");
					$paginationParams = array($_SERVER['SCRIPT_NAME'],$uniqueId,$offset,$limit,$existingRecord['paginationId']);
					pdoError(__LINE__,$paginationStmt,$paginationParams);
				}else{
					$debug->add('in 7');
					$paginationStmt = $Dbc->prepare("INSERT INTO
	pagination
SET
	userId = ?,
	scriptName = ?,
	uniqueId = ?,
	pageOffset = ?,
	pageLimit = ?,
	dateAdded = ?");
					$paginationParams = array($_SESSION['userId'],$_SERVER['SCRIPT_NAME'],$uniqueId,$offset,$limit,DATETIME);
					pdoError(__LINE__,$paginationStmt,$paginationParams);
				}
				$paginationStmt->execute($paginationParams);
				$lastInsertId = $Dbc->lastInsertId();
				$debug->add('$lastInsertId: ' . $lastInsertId);
			}
			$this->_action = $action;
			$this->_itemCount = $itemCount;
			$this->_offset = $offset;
			$this->_limit = $limit;
			$this->_uniqueId = $uniqueId;
			$this->_defaultSearchValue = empty($defaultSearchValue) ? 'Search Term' : $defaultSearchValue;
			$this->_fromSearch = $fromSearch;
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			error(__LINE__,'','<pre class="red">' . $e . '</pre>');
		}
	}
	
	public function addSearchParameters($searchParamsArray){
		/**
		* Build additional search parameters.
		*
		* Builds custom attributes meant to be included with the search field element.
		*
		* @param	$searchParamsArray	array	An associative array with the structure array('param name' => 'param value').
		*
		* @return	string	Adds search parameters to the protected property $_additionSearchParams, otherwise false.
		*/
		global $debug;
		$output = '';
		try{
			if(is_array($searchParamsArray)){
				$x = 1;
				foreach($searchParamsArray as $key => $value){
					$this->_additionalSearchParams .= ' searchparamname' . $x . '="' . $key . '" searchparamvalue' . $x . '="' . $value . '"';
					$x++;
				}
			}else{
				throw new Adrlist_CustomException('','$searchParamsArray is not an array.');
			}
		}catch(Adrlist_CustomException $e){}
	}

	private function intThis($before){
		/**
		 * Force a value to be an integer.
		 *
		 * Attempts to return an integer. The coersion method used here is faster than (int) or intval(), and produces more desireable outcomes when given non-numeric values. It accepts strings and arrays.
		 *
		 * @param	$before	string|array	The value to convert.
		 *
		 * @return	string|array	The converted value.
		*/
		if(is_array($before)){
			foreach($before as &$value){
				$value = 0 + $value;
			}
			return $before;
		}else{
			return 0 + $before;
		}
	}

	public function getOffsetLimit($userId = '',$uniqueId = ''){
		/**
		 * Get the offset and limit for a list.
		 *
		 * This will get the offset and limit for a pagination item. If an item does not exist it will add one. It will also perform some housekeeping duties like deleteing multiple entries.
		 *
		 * @param	$userId		int		The userId of the requesting person. Each user has unique pagination options.
		 * @param	$uniqueId	string	The name of the pagination item in the database.
		 *
		 * @return	array	Two values, 0 => offset, 1 => limit, otherwise a default array(0,10).
		*/
		try{
			global $debug, $message, $Dbc;
			//Force the parameters to integers.
			if(empty($_SESSION['userId'])){
				throw new Adrlist_CustomException('','$_SESSION[\'userId\'] is empty.');
			}elseif(empty($this->_uniqueId) && empty($uniqueId)){
				throw new Adrlist_CustomException('','$this->_uniqueId and $uniqueId are empty.');
			}
			//Get the pagination information.
			$getPaginationStmt = $Dbc->prepare("SELECT
	pageOffset AS 'offset',
	pageLimit AS 'limit'
FROM
	pagination
WHERE
	userId = ? AND
	scriptName = ? AND
	uniqueId = ?");
			$uniqueId = empty($this->_uniqueId) ? $uniqueId : $this->_uniqueId;
			$getPaginationParams = array($_SESSION['userId'],$_SERVER['SCRIPT_NAME'],$uniqueId);
			$getPaginationStmt->execute($getPaginationParams);
			$row = $getPaginationStmt->fetch(PDO::FETCH_ASSOC);
			return array($this->intThis($row['offset']),$this->intThis($row['limit']));
		}catch(Adrlist_CustomException $e){
		}catch(PDOException $e){
			error(__LINE__,'','<pre class="red">' . $e . '</pre>');
		}
	}
	
	public function offsetLimit(){
		//Returns the offset and limit in an array.
		return array($this->_offset,$this->_limit);	
	}
	
	public function _searchOnly(){
		//Will force the pagination to only produce the search section; no page numbers or limit.
		$this->_searchOnly = true;
	}
	
	public function setDisplayPages($displayPages){
		//The number of pages numbers displayed to the user.
		$this->_displayPages = intThis($displayPages);
	}
	
	public function output($mobileOptimizedId = false){
		/*
		 * Output the pagination.
		 *
		 * $mobileOptimizedId	string	If you want to hide all of the search and limit stuff, make a unique ID for the toggle hide function.
		 *
		 * @return				string
		 */
		global $debug;
		$output = '';
		$tempDebug = new Adrlist_Debug();
		$tempDebug->add('$this->_defaultSearchValue at begining of output: ' . $this->_defaultSearchValue);
		try{
			$pageNumbersOutput = '';
			if($this->_itemCount === '' || !is_int($this->_itemCount)){
				throw new Adrlist_CustomException('','_itemCount is not valid: ' . $this->_itemCount . '.');
			}elseif($this->_offset === '' || !is_int($this->_offset)){
				throw new Adrlist_CustomException('','_offset is not valid: ' . $this->_offset . '.');
			}elseif($this->_limit === '' || !is_int($this->_limit)){
				throw new Adrlist_CustomException('','_limit is not valid: ' . $this->_limit . '.');
			}elseif($this->_uniqueId === ''){
				throw new Adrlist_CustomException('','_uniqueId is not valid: ' . $this->_uniqueId . '.');
			}
			$tempDebug->add('$this->_itemCount: ' . $this->_itemCount . '<br>$this->_offset: ' . $this->_offset . '<br>$this->_limit: ' . $this->_limit . '<br>$this->_uniqueId: ' . $this->_uniqueId . '<br>$this->_defaultSearchValue: ' . $this->_defaultSearchValue);
			$tempDebug->add('itemCount: ' . $this->_itemCount);
			//Build pagination. This is a numerical listing showing a user-selectable number of pages.
			if($this->_itemCount > 0){
				$totalPages = @ceil($this->_itemCount/$this->_limit);
				$currentPage = @ceil(($this->_offset+1)/$this->_limit);
				$displayPages = empty($this->_displayPages) ? 11 : $this->_displayPages;
				$splitPoint = floor($displayPages/2);//A halfway measurement.
				$preceedingDots = false;
				$trailingDots = false;
				//Determine where $currentPage is in relation to $totalPages.
				if(($totalPages - $currentPage) < $splitPoint){//$currentPage is near end.
					$tempDebug->add('1');
					$endPage = $totalPages;
					if(($endPage - $displayPages) < 1){
						$tempDebug->add('1-1');
						$startPage = 1;
					}else{
						$tempDebug->add('1-2');
						$startPage = $endPage - ($displayPages-1);
						$preceedingDots = true;
					}
				}else{//$currentPage is not near the end.
					$tempDebug->add('2');
					$trailingDots = true;
					if(($currentPage - $splitPoint) > 1){//It is not near the beginning.
						$tempDebug->add('2-1');
						$startPage = $currentPage - $splitPoint;
						$preceedingDots = true;
						$endPage = $currentPage + $splitPoint;
						if($startPage + ($displayPages-1) == $totalPages){
							$tempDebug->add('2-1-1');
							$trailingDots = false;
						}
					}else{
						$tempDebug->add('2-2');
						$startPage = 1;
						if($displayPages >= $totalPages){
							$tempDebug->add('2-2-1');
							$endPage = $totalPages;
							$trailingDots = false;
						}else{
							$tempDebug->add('2-2-2');
							$endPage = $displayPages;
						}
					}
				}
				$tempDebug->add('$totalPages: ' . "$totalPages<br>" . '$displayPages: ' . "$displayPages<br>" . '$currentPage: ' . "$currentPage<br>" . '$splitPoint: ' . "$splitPoint<br>" . '$startPage: ' . "$startPage<br>" . '$endPage: ' . "$endPage");
				if(empty($this->_limit)){
					$pageNumbersOutput .= '<table class="table"><tr><td style="padding:2px 0px 2px 0px">&nbsp;</td></tr></table>';	
				}else{
					$pageNumbersOutput = '	<div class="pagination"><table class="center">
		<tr>
';
					$pageCount = $startPage;
					if($preceedingDots){
						$pageNumbersOutput .= '<td><button action="' . $this->_action . '" class="goToPage ui-btn ui-btn-c ui-btn-inline ui-shadow ui-corner-all ui-mini" limit="' . $this->_limit . '" offset="0" uniqueId="' . $this->_uniqueId . '">&laquo;</button></td><td class="backgroundWhite textLarge" style="border:none; padding:0px 0px 0px 0px">&#8230;</td>';
					}
					while($displayPages >= 1){
						if($pageCount > $totalPages){
							$pageNumbersOutput .= '<td style="padding: 2px 6px">&nbsp;</td>';
							break;
						}
						$newOffset = ($pageCount-1)*$this->_limit;
						if($pageCount == $currentPage){
							//The current page.
							$pageNumbersOutput .=  '			<td><button action="' . $this->_action . '" class="ui-btn ui-btn-a ui-btn-inline ui-shadow ui-corner-all ui-mini" limit="' . $this->_limit . '" offset="' . $newOffset . '" uniqueId="' . $this->_uniqueId . '">' . $pageCount . '</button</td>';
						}else{
							//Other pages.
							$pageNumbersOutput .= '			<td> <button action="' . $this->_action . '" class="goToPage ui-btn ui-btn-c ui-btn-inline ui-shadow ui-corner-all ui-mini" limit="' . $this->_limit . '" offset="' . $newOffset . '" uniqueId="' . $this->_uniqueId . '">' . $pageCount . '</button></td>';
						}
						$pageCount++;
						$displayPages--;
					}
					if($trailingDots){
						$newOffset = ($totalPages-1)*$this->_limit;
						$pageNumbersOutput .= '<td class="backgroundWhite textLarge" style="border:none; font-size:larger; padding:0px 0px 0px 3px">&#8230;</td><td><button action="' . $this->_action . '" class="goToPage ui-btn ui-btn-c ui-btn-inline ui-shadow ui-corner-all ui-mini" limit="' . $this->_limit . '" offset="' . $newOffset . '" uniqueId="' . $this->_uniqueId . '">&raquo;</button></td>';
					}
					$pageNumbersOutput .= '
		</tr>
	</table>
</div>
';
				}
			}
			$debug->add('$this->_limit when $this->_uniqueId is ' . $this->_uniqueId . ': ' . $this->_limit);
			$searchOutput = '<div class="pagination textLeft">
		<label class="ui-hidden-accessible" for="search' . $this->_uniqueId . '">Search Input:</label>
<input class="searchfield" type="search" action="' . $this->_action . '" autocapitalize="off" autocorrect="off" autoreset="true" id="search' . $this->_uniqueId . '" placeholder="' . $this->_defaultSearchValue . '" goswitch="' . $this->_uniqueId . 'Goswitch"' . $this->_additionalSearchParams . ' name="search' . $this->_uniqueId . '"';// default="' . $this->_defaultSearchValue . '"
			$searchOutput .= $this->_fromSearch ? ' value="' . $_POST['searchVal'] . '"' : '';//hide
			$searchOutput .= '><button action="' . $this->_action . '" class="hide searchButton ui-btn ui-btn-inline ui-btn-cui-shadow ui-corner-all ui-mini" id="' . $this->_uniqueId . 'Goswitch" style="margin:0 .5em"><i class="fa fa-search" ></i>Search</button>
</div>';//<button class="clearSearch ui-btn ui-btn-inline ui-btn-b ui-icon-delete ui-btn-icon-left ui-shadow ui-corner-all ui-mini" default="' . $this->_defaultSearchValue . '" style="margin:0;">Clear</button>
			$limitOutput = '<div class="desktop pagination textRight" id="' . $mobileOptimizedId . '">
	Show <input class="textRight" data-role="none" goswitch="setLimitButton' . $this->_uniqueId . '" offset="0" type="number" style="margin-right:.5em;width:3em;" value="' . $this->_limit .'"><button action="' . $this->_action . '" class="setLimitButton ui-btn ui-btn-inline ui-btn-c ui-shadow ui-corner-all ui-mini" id="setLimitButton' . $this->_uniqueId . '" uniqueId="' . $this->_uniqueId . '">Set</button><button action="' . $this->_action . '" class="limitShowAll ui-btn ui-btn-inline ui-btn-c ui-shadow ui-corner-all ui-mini" uniqueId="' . $this->_uniqueId . '">Show All</button>
</div>';
			//Build the output.
			if($this->_searchOnly){
				$output .= $searchOutput;
			}else{
				$output .= '<!-- begin pagination --><div class="break textCenter">' . $searchOutput . $pageNumbersOutput . $limitOutput . '</div>';
				$output .= $mobileOptimizedId ? '<button class="mobile tablet ui-btn ui-mini ui-icon-carat-r ui-btn-inline ui-corner-all left"  toggle="' . $mobileOptimizedId . '">View Options</button>' : '';
				$output .= '<!-- end pagination -->';
			}
		}catch(Adrlist_CustomException $e){
		}
		//$debug->add($tempDebug->output());
		return $output;
	}
}
