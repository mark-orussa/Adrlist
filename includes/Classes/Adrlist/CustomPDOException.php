<?php
class Adrlist_CustomPDOException extends PDOException{
	/**
	 * Create custom PDO exceptions.
	 *
	 * This will add messages to the public $message variable and the private $debug.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	*/
	
	//Properties.
	
	//Lets allow for public and private messages.
	public function __construct(PDOException $e) {
 		global $debug, $message, $returnThis;
		$debug->printArray($e); // Outputs: "28000"
		/*if(strstr($e->getMessage(), 'SQLSTATE[')) {
			preg_match('/SQLSTATE\[(\w+)\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
			$this->code = ($matches[1] == 'HT000' ? $matches[2] : $matches[1]);
			$this->message = $matches[3];
			$debug->add($err->getCode()); // Outputs: "28000"
			$debug->add($err->getMessage());
        }*/
    } 
	/*public function __construct(PDOException $e) {
		global $debug, $message, $returnThis;
        //Add the messages.
		$trace = parent::getTrace();
		$temp = '<div class="border red red">
	Custom Exception<br>
';
		if(is_array($trace)){
			foreach($trace as $traceKey => $traceValue){
				$temp .= '	<span class="bold">File:</span>' . $traceValue['file'] . ' line ' . $traceValue['line'] . '<br>
	<span class="bold">Called:</span>' . $traceValue['class'] . '->' . $traceValue['function'] . '(';
				$traceArgs = '';
				foreach($traceValue['args'] as $key => $value){
					if(empty($traceArgs)){
						$traceArgs .= $value;
					}else{
						$traceArgs .= ', ' . $value;
					}
				}
			}
		}
		$temp .= $traceArgs . ')<br>
	<span class="bold">Class File:</span>' . parent::getFile() . ' line ' . parent::getLine() . '<br>
	<span class="bold">Message:</span>' . $privateMessage . '</span>
</div>
yellowbelly';
		$debug->add($temp);

        // make sure everything is assigned properly
        parent::__construct($publicMessage, $code, $previous);
		if(MODE != ''){
			returnData();
		}
    }


    // custom string representation of object
    /*public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }*/
}
