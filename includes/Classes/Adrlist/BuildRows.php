<?php
class Adrlist_BuildRows{
	/**
	 * Build rows of information.
	 *
	 * This builds common lists of information. It is desinged to produce a title row of column headers and rows of information. The titleRow and regular rows are output separately.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 *
	 * @param	$_tableId		array	A unique name for the table, used to associate the jquery mobile column picker.
	 * @param	$_titleRowArray		array	A nested array with each value having:
	 * array(
	 *		0 => array(
	 *				html,
	 *				column-priority
	 *			),
	 *	)
	 *
	 * @param	$_rowArray			array	The row array. it should be a nested array containing multiple rows of information with multiple columns in each row:
	 * array(
	 *		0 => array(
	 *				Name,
	 *				Date,
	 *				etc
	 *			),
	 *		1 => array(
	 *				Name,
	 *				Date,
	 *				etc
	 *			)
	 *	)
	 * @param	$_hiddenArray		array	An optional array of arrays containing hidden row information. The nested arrays are formatted like:
	 * array(
	 *		0 => toggle element id,
	 *		1 => extra row information like row actions or terms,
	 *		2 => the number of columns to skip
	 *	)
	 *
	*/
	
	//Properties.
	protected $_tableId;
	protected $_titleRowArray;
	protected $_rowArray;
	protected $_hiddenArray;
	protected $_toggle;
	
	public function __construct($tableId,$titleRowArray = false,$rowArray = false, $toggle = true){
		/**
		 * Initialize the BuildRow class.
		 *
		 * @param	$tableId	array			An unique name for associating the column picker.
		 * @param	$titleRowArray	array		An array with column titles.
		 * @param	$rowArray	array			A nested array. Each row has an array containing column information.
		 * @param	$toggle		string			Basically this controls whether the table will automatically toggle columns. If set to false, the column chooser will not be shown.
		 *
		*/
		$this->_tableId = empty($tableId) ? rand(100,1000) : $tableId;

		if(!empty($titleRowArray)){
			if(is_array($titleRowArray)){
				$this->_titleRowArray = $titleRowArray;
				$count = count($titleRowArray);
			}else{
				throw new Adrlist_CustomException('','$titleRowArray is not an array.');
			}
		}		

		if(!empty($rowArray)){
			if(is_array($rowArray)){
				$this->_rowArray = $rowArray;
				$count = count($rowArray);//This will overwrite the title row count.
			}else{
				throw new Adrlist_CustomException('','$rowArray is not an array.');
			}
		}		
		
		$this->_toggle = $toggle;
		$this->_hiddenArray = false;
	}
	
	public function addRows($arrayToAdd){
		try{
			if(is_array($arrayToAdd)){
				if(is_array($this->_rowArray)){
					$this->_rowArray = $this->_rowArray + $arrayToAdd;
				}else{
					$this->_rowArray = $arrayToAdd;
				}
			}else{
				throw new Adrlist_CustomException('','The value is not an array.');
			}
		}catch(Adrlist_CustomException $e){
		}
	}
	
	public function addHiddenRows($hiddenArray){
		try{
			if(is_array($hiddenArray)){
				if(is_array($this->_hiddenArray)){
					$this->_hiddenArray = $this->_hiddenArray + $hiddenArray;
				}else{
					$this->_hiddenArray = $hiddenArray;
				}
			}else{
				throw new Adrlist_CustomException('','The value is not an array.');
			}
		}catch(Adrlist_CustomException $e){
		}
	}
	
	public function outputTitleRow(){
		/**
		 * Build the title row.
		 *
		 * An optional class can be added using titleRowClass(). $_cssWidths is used for column widths.
		 *
		 * @return	string	The formatted title row if titleRowArray was provided, otherwise false.
		*/
		global $debug;
		if(empty($this->_titleRowArray)){
			return false;
		}else{
			$rowTitleOutput = '<div class="';
			$rowTitleOutput .= empty($this->_titleRowClass) ? 'break' : $this->_titleRowClass;
			$rowTitleOutput .= '" thing="titleRow">
';
			$x = 0;
			//Insert search section here.
			foreach($this->_titleRowArray as $key){
				$rowTitleOutput .= '	<div class="rowTitle" style="width:' . $this->_cssWidths[$x] . '">' . $key . '</div>
';
				$x++;
			}
			$rowTitleOutput .= '</div>
<div class="hr2"></div>
';
			return $rowTitleOutput;
		}
	}
	
	public function output(){
		/**
		 * Build the rows using jquery mobile tables.
		 *
		 * This build a jquery mobile table version. Both title and regular rows are made here. The jquery mobile implementation causes columns to automatically hide or show base on the data-priority setting and screen width. Associated CSS classes are required. $_cssWidths is used for column widths. If a special row is required, one that sits on it's own row, place it in an array at the end of the parent array.
		 *
		 * @return	string	A table.
		*/
		global $debug;
		$output = '';
		try{
			//The title row.
			$rowTitleOutput = '';
			if(!empty($this->_titleRowArray)){
				$rowTitleOutput .= '
	 <thead>
	   <tr class="ui-bar-a';
				$rowTitleOutput .= empty($this->_titleRowClass) ? ' ' : ' ' . $this->_titleRowClass;
				$rowTitleOutput .= '" thing="titleRow">
';
				foreach($this->_titleRowArray as $key => $value){
					$columnPriority = isset($value[1]) ? ' data-priority="' . $value[1] . '"' : '';
					$rowTitleOutput .= '	<th' . $columnPriority . '>' . $value[0] . '</th>
';
				}
				$rowTitleOutput .= '		</tr>
	</thead>
		';
			}
			$columnCount = count($this->_titleRowArray);
			//The rows.
			$rowOutput = '';
			if(!empty($this->_rowArray)){
				$rowOutput .= '<tbody>';
				foreach($this->_rowArray as $key => $row){
					$rowOutput .= '<tr>';
					if(is_array($row)){
						foreach($row as $value){
							if(is_array($value)){
								//throw new Adrlist_CustomException('','$value is an array. $value: ' . $debug->printArrayOutput($value,'$value'));
							}
							$rowOutput .= '			<td>' . $value . '</td>';// style="width:' . $this->_cssWidths[$x] . '"
						}
					}else{
						throw new Adrlist_CustomException('','$row is not an array. $row: ' . $row);
					}
					$rowOutput .= '</tr>';
					if($this->_hiddenArray){
						$rowOutput .= '<tr class="hide"><td></td></tr>';//An empty row to preserve alternating row colors.
						$rowOutput .= '<tr class="hide" id="' . $this->_hiddenArray[$key][0] . '">';
						if(isset($this->_hiddenArray[$key][2])){
							while($this->_hiddenArray[$key][2]>0){
								$rowOutput .= '<td></td>';
								$this->_hiddenArray[$key][2]--;
							}
						}
						$colspan = $columnCount + 1;
						$rowOutput .= '<td colspan="' . $colspan . '">' . $this->_hiddenArray[$key][1] . '</td></tr>';
					}
				}
				$rowOutput .= '</tbody>';
			}
		}catch(Adrlist_CustomException $e){
		}
		$output .= '<table data-role="table" data-mode="';
		$output .= $this->_toggle ? 'columntoggle' : '';
		$output .= '" id="' . $this->_tableId . '" class="ui-body-a ui-shadow table-stripe ui-responsive" data-column-btn-theme="a" data-column-btn-text="Show Columns" data-column-popup-theme="c">' . $rowTitleOutput . $rowOutput . '</table>';
		return $output;
	}
}