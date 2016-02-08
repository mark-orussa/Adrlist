<?php require_once('../../includes/config.php');
//https://adrlist.com/admin/payPalListener.php
$email = 'support@adrlist.com';//The address to send helpful debug message to.
if(array_key_exists('test_ipn', $_POST) && 1 === (int)$_POST['test_ipn']){//Set the $url.
    $url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
}else{
    $url = 'https://www.paypal.com/cgi-bin/webscr';
}
$return = 'cmd=_notify-validate'; 
if(array_key_exists('charset', $_POST) && $_POST['charset'] != 'utf-8'){
	$convert = true;
}else{
	$convert = false;
}
$queryStringArray = array();
$returnArray = array();
foreach($_POST as $key => $value){
	if($convert){
		$convertedValue = mb_convert_encoding($value, 'utf-8', $_POST['charset']);//Convert all the values.
		$queryStringArray[] = "$key=$convertedValue";
	}
	$returnArray[] = "$key=$value";
}
if($convert){//Store the charset values for future reference.
	$queryStringArray[] = 'new_charset=utf-8';
}
$queryString = join("&", $queryStringArray);
$return = join("&", $returnArray);
$request = printArray($_REQUEST,'$_REQUEST');
try{
	$ipnQuery = "INSERT INTO
	ipnListener
SET
	time = ?,
	txn_id = ?,
	txn_type = ?,
	payment_status = ?,
	queryString = ?,
	request = ?";
	$ipnStmt = $Dbc->prepare($ipnQuery);
	$ipnParams = array(DATETIME,$_POST['txn_id'],$_POST['txn_type'],$_POST['payment_status'],$queryString,$request);
	$ipnStmt->execute($ipnParams);
	$lastIpnId = mysql_insert_id;
	$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($return) . "\r\n\r\n";
	$pointer = curl_init();
	curl_setopt_array($pointer, array(
		CURLOPT_URL => $url,
		CURLOPT_POST => TRUE,
		CURLOPT_POSTFIELDS => $return,
		CURLOPT_HTTPHEADER => $header,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HEADER => FALSE,
		CURLOPT_SSL_VERIFYPEER => TRUE,
		CURLOPT_TIMEOUT => 30
	));
	//CURLOPT_CAINFO => 'cacert.pem'// For local connections you need to have a local CA bundle to verify the SSL cert.
	$response = curl_exec($pointer);//Get the PayPal response.
	$status = curl_getinfo($pointer, CURLINFO_HTTP_CODE);//Get the http header response.
	$debug->add(curl_error($pointer));
	$debug->add('$response: ' . "$response<br>" . '$status: ' . "$status.");
	curl_close($pointer);//Close connection.
	if($status == 200 && $response == 'VERIFIED'){
		//Use header() to send to another script for adding credits, etc.
		/*TODO: Check the payment_status is completed. Check that txn_id has not been previously processed. Check that receiver_email is your Primary PayPal email. Check that payment_amount/payment_currency are correct. Process payment. If 'VERIFIED', send an email of IPN variables and values to the specified email address. */
		//mail($email, "VERIFIED IPN", $return);
	}else{//Record an error.
		$debug->add('$response: ' . "$response.");
		ipnError();
		mail($email, "Invalid IPN", $return);
	}
}catch(PDOException $e){
	$debug->add('<pre>' . $e . '</pre>');
	error(__LINE__);
	ipnError();
}

function ipnError(){
	global $debug, $Dbc;
	$ipnErrorQuery = "INSERT INTO
	ipnError
SET
	time = ?,
	txn_id = ?,
	errorMessage = ?";
	$ipnErrorStmt = $Dbc->prepare($ipnErrorQuery);
	$ipnErrorParams = array(DATETIME,$_POST['txn_id'],$debug);
	$ipnErrorStmt->execute($ipnErrorParams);
}
?>