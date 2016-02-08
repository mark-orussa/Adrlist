<?php 
class Adrlist_Dbc extends PDO{

	public function __construct($dsn){
		global $debug;
		$options = array(
			PDO::ATTR_PERSISTENT => true, 
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		try{
			$dbcParts = preg_split('/{\w+}/',$dsn);
			parent::__construct($dbcParts[0], $dbcParts[1], $dbcParts[2] , $options);
			return true;
		}catch(PDOException $e){
			//print $debug->printArrayOutput($e);
			$debug->add($e->getMessage());
			return false;
		}
	}

	public function execute($values=array()){
		global $debug;
		$debug->printArray($values,'$values');
		try{
			$t = parent::execute($values);
			// maybe do some logging here?
		}catch(PDOException $e){
			// maybe do some logging here?
			die('funkytown2');
			//throw $e . $debug;
		}
		return $t;
	}	
	
	public static function interpolateQuery($query, $params){
		$keys = array();
	
		# build a regular expression for each parameter
		if(is_array($params)){
			foreach($params as $key => $value){
				if (is_string($key)) {
					$keys[] = '/:'.$key.'/';
				} else {
					$keys[] = '/[?]/';
				}
			}
		}
		$query = preg_replace($keys, $params, $query, 1, $count);
		#trigger_error('replaced '.$count.' keys');
		return $query;
	}
}