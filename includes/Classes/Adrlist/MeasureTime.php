<?php

class Adrlist_MeasureTime{
	//Properties
	private $_startTime;
	private $_endTime;
	private $_difference;
	private $_name;

	public function __construct($name = ''){
		$this->_name = empty($name) ? '' : $name;
		$this->startTime();
	}
	
	private function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	public function startTime(){
		$this->_startTime = $this->microtime_float();
	}
	
	public function endTime(){
		$this->_endTime = $this->microtime_float();
	}
	
	public function output(){
		if(empty($this->_endTime)){
			$this->endTime();
		}else{
			$this->_endTime = $this->endTime();
		}
		$difference =  $this->_endTime - $this->_startTime;
		if($this->_name){
			return $this->_name . ' time: ' . $difference;
		}else{
			return 'time: ' . $difference;
		}
	}
}
