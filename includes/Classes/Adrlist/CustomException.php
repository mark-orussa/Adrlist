<?php
class Adrlist_CustomException extends Exception{
	/**
	 * Create custom exceptions.
	 *
	 * This will add messages to the public $message variable and the private $debug.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	*/
	
	//Properties.

	//Lets allow for public and private messages.
    public function __construct($publicMessage = '', $privateMessage = '', $code = 0, Exception $previous = null){
		global $debug, $message, $success;
		$success = false;
		$message .= $publicMessage ? $publicMessage : 'We\'ve encountered a technical problem that is preventing infomation from being shown. Please try again in a few moments.<br>
<br>
If the problem persists please <a href="' . LINKSUPPORT . '">contact support</a>.';
        //Add the messages.
		$trace = parent::getTrace();
		$temp = '<div class="border red red">
	Custom Exception<br>
';
		if(is_array($trace)){
			foreach($trace as $traceKey => $traceValue){
				$traceValue['class'] = empty($traceValue['class']) ? '' : $traceValue['class'];
				$temp .= '	<span class="bold">File:</span>' . $traceValue['file'] . ' line ' . $traceValue['line'] . '<br>
	<span class="bold">Called:</span>' . $traceValue['class'] . '->' . $traceValue['function'] . '(';
				$traceArgs = '';
				if(isset($traceValue['args'])){
					foreach($traceValue['args'] as $key => $value){
						if(empty($traceArgs)){
							if(!is_object($value)){
								if(is_array($value)){
									$debug->printArrayOutput($value);
								}else{
									$traceArgs .= $value;
								}
							}
						}else{
							$traceArgs .= ', ' . $value;
						}
					}
				}
			}
		}
		$temp .= $traceArgs . ')<br>
	<span class="bold">Class File:</span>' . parent::getFile() . ' line ' . parent::getLine() . '<br>
	<span class="bold">Message:</span>' . $message . '<br>
	<span class="bold">Private Message:</span>' . $privateMessage . '</span>
</div>';
		$debug->add($temp);
        // make sure everything is assigned properly
		$code = (int) $code;
		parent::__construct($publicMessage, $code, $previous);
		if(MODE == ''){
			echo $publicMessage;
		}
	}
}
