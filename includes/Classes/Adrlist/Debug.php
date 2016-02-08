<?php

class Adrlist_Debug{
	//Properties
	private $_debugInformation;

	public function __construct(){
	}
	
	private function backtrace($debug_backtrace){//Currently not used.
/*		$output = '';
		foreach($debug_backtrace as $key => $value){
			$output .= '<div style="margin-left:2em;">
	<div style="color:"green">file: ';
			$output .= empty($value['file']) ? '' : $value['file'];
			$output .= '</div>
	<div style="color:blue">line: ';
			$output .= empty($value['line']) ? '' : $value['line'];
			$output .= '</div>
	<div style="color:purple">function: ';
			$output .= empty($value['function']) ? '' : $value['function'];
			$output .= '</div>
	<div style="color:purple">agrs: ';
			if(isset($value['args']) && is_array($value['args'])){
				foreach($value['args'] as $value2){
					$tempDebug .= $value2;
				}
			}
			$output .= '</div>
</div>
';
		}
		return $output;*/
	}
	
	public function add($debugMessage,$debug_backtrace = NULL){
	/*
	Add detailed debug information to the debug class.
	$debugMessage = (string) a user message to help explain the debug.
	$debugInfo = debug_backtrace(false)
	*/
		$tempStuff = '';
		if(is_array($debug_backtrace) && !empty($debug_backtrace)){
			$tempStuff .= $this->printArray($debug_backtrace);
		}
		$tempStuff .= empty($debugMessage) ? '' : '<div>' . $debugMessage . '</div>
';
		$this->_debugInformation .= $tempStuff;
	}
	
	public function newFile($fileName = NULL){
		/*
		$fileName = (string) will default the the page's $title['fileName'] if not provided.
		*/
		global $fileInfo;
		if(empty($fileName)){
			if(empty($fileInfo)){
				$fileName = NULL;
			}else{
				$fileName = is_array($fileInfo) ? $fileInfo['fileName'] : NULL;
			}
		}
		//Specifically used when introducing a new document. All php files should use this at the top.
		$this->_debugInformation .= '<div style="font-weight:bold;border:1px dotted #333;">From ' . $fileName . '</div>
';
	}
	
	public function printArray($array, $arrayName = '', $dump = false){
		if(is_array($array)){
			$printArrayOutput = '<div class="break bold">The array named: ' . $arrayName . ':</div>
<pre>
';
			ob_start();
			if($dump){
				var_dump($array);//this will produce an array structure with extra information like variable type and value length
			}else{
				var_export($array);//this will produce a simple array structure
			}
			$printArrayOutput .= ob_get_contents();
			ob_end_clean();
			$printArrayOutput .= '
</pre>
';
		}
		else {
			$printArrayOutput = $arrayName ? "$arrayName is not an array. $arrayName: " . "$array<br>" : "The supplied variable is not an array: $array<br>
";
		}
		$this->_debugInformation .= $printArrayOutput;
	}

	public function printArrayOutput($array, $arrayName = '', $dump = false){
		//Perform the printArray function and return the results. This includes any prior debug information.
		if(is_array($array)){
			$printArrayOutput = '<div class="break bold">The array named: ' . $arrayName . ':</div>
<pre>
';
			ob_start();
			if($dump){
				var_dump($array);//this will produce an array structure with extra information like variable type and value length
			}else{
				var_export($array);//this will produce a simple array structure
			}
			$printArrayOutput .= ob_get_contents();
			ob_end_clean();
			$printArrayOutput .= '
</pre>
';
		}
		else {
			$printArrayOutput = $arrayName ? "$arrayName is not an array. $arrayName: " . "$array<br>" : "The supplied variable is not an array: $array<br>
";
		}
		return $printArrayOutput;
	}

	public function output(){
		//if(!empty($_SESSION['siteRoleId']) && $_SESSION['siteRoleId'] == 5){
		if((isset($_COOKIE['DEBUG']) && $_COOKIE['DEBUG'] === 'true') || strpos($_SERVER['SCRIPT_NAME'],'amazonIPNListener') !== false){
			return 	'<div id="debug" class="debug">
	<div style="color:red;font-weight:bold;">BEGIN DEBUG</div>
		' . $this->_debugInformation . '
	<div style="color:red;font-weight:bold;border-top:1px dotted #333;">END DEBUG</div>
</div>
';
		}else{
			return '<!--END-->';
		}
	}
}
