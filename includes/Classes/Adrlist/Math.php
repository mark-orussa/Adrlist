<?php
class Adrlist_Quadrilateral{

	protected $_gridSize;
	protected $_startingPoint;
	protected $_secondPoint;
	protected $_thirdPoint;
	protected $_fourthPoint;
	
	public function __construct($h,$v){
		/*
		$h is the horizontal point size.
		$v is the vertical point size.
		*/
		$this->_gridSize = array($h,$v);
		$this->_startingPoint = array(1,1);
		$this->_secondPoint = array(2,1);
		$count = 0;
		while($this->_startingPoint[0] < $this->_gridSize[0] && $this->_startingPoint[1] <= $this->_gridSize[1]){
			$count++;
		}

	}
	
	private function determineSecondPoint(){
		$this->_secondPoint = determineNextPoint($this->_secondPoint);
		if($this->_startingPoint == $this->_secondPoint){
			//The second point is currently the same as the starting point.
			determineNextPoint($this->_secondPoint);
		}elseif($this->_secondPoint[0]+1 <= $dimensions[0]){
			//same row
			$secondPoint = array($this->_secondPoint[0] + 1,$this->_secondPoint[1]);
		}else{
			//next row
			$secondPoint = array(0,$this->_secondPoint[1]+1);
		}
	}

	private function determineNextPoint($point){
		//Automatically selects the next point, moving horizontally until reaching the end of the row. It will then select the point in the first row.
		if(!is_array($point)){
			die('$coordinates is not an array while attempting to determine the next point.');
		}else{
			if($point[0]+1 <= $this->_gridSize[0]){
				return array($point[0] + 1,$point[1]);
			}else{
				return array($point[0] + 1,$point[1]);
			}
		}
	}
}