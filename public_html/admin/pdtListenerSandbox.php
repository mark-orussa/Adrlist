<?php require_once('../../includes/config.php');
dbc();
$email = 'support@adrlist.com';//The address to send helpful debug message to.
if(isset($_GET['tx'])){
	$tx = $_GET['tx'];//This is the transaction id.
}
$queryString= '';
foreach($_GET as $key => $value){
	$queryString .= "&$key=$convertedValue";
}
$request = printArray($_REQUEST,'$_REQUEST');
$pdtQuery = "INSERT INTO
	pdtListener
SET
	time = '" . DATETIME . "',
	tx = '" . $tx . "',
	st = '" . $_GET['st'] . "',
	queryString = '" . $queryString . "',
	request = '" . $request . "'";//
if($result = mysql_query($pdtQuery)){
	if(mysql_affected_rows() == 0){
		pdtError();
	}else{
		$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
		$pointer = curl_init();//Initiate curl.
		//Set return options.
		curl_setopt_array($pointer, array(
			CURLOPT_URL => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => http_build_query(array(
				'cmd' => '_notify-synch',
				'tx' => $tx,
				'at' => 'HKrmC-NF_KSkenm9VusYKH4sHc3I8bCGSQnoWmY_EI5-TMoOg1Kem8Wm_O0',
			)),
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HEADER => FALSE,
			CURLOPT_SSL_VERIFYPEER => TRUE
		));
		//Execute return and get response and status code.
		$response = curl_exec($pointer);
		$status = curl_getinfo($pointer, CURLINFO_HTTP_CODE);
		$debug->add(curl_error($pointer));
		$debug->add('$response: ' . "$response<br>" . '$status: ' . "$status.");
		curl_close($pointer);
		if($status == 200 AND strpos($response, 'SUCCESS') === 0){//Status is good and response starts with SUCCESS.
			/*Receive stuff like this:
			SUCCESS transaction_subject=Monthly+Subscription payment_date=02%3A03%3A09+Jan+11%2C+2012+PST txn_type=subscr_payment subscr_id=I-YF7FHX3043CG last_name=Breeden option_selection1=Studio residence_country=US item_name=Monthly+Subscription payment_gross=499.99 mc_currency=USD business=mark%40markproaudio.com payment_type=instant protection_eligibility=Ineligible payer_status=verified payer_email=cmusicfan%40gmail.com txn_id=6XV3971336213623D receiver_email=mark%40markproaudio.com first_name=Aaron option_name1=Plan payer_id=MC3NVANURG4YS receiver_id=EUNRUXBSANQ7N item_number=monthlySubscription payer_business_name=Scizors payment_status=Completed payment_fee=14.80 mc_fee=14.80 btn_id=2387373 mc_gross=499.99 charset=windows-1252 
			
			*/
		}else{
			$debug->add('$response: ' . "$response.");
			pdtError();
			mail($email, "PDT failure", $return);
		}
	}
}else{
	pdtError();	
}

function pdtError(){
	global $debug;
	//error(__LINE__);
	pdoError(__LINE__, $pdtQuery, '$pdtQuery', 1);
	$ipnErrorQuery = "INSERT INTO
	pdtError
SET
	time = '" . DATETIME . "',
	tx = '" . $tx . "',
	errorMessage = '" . $debug . "'";
	mysql_query($ipnErrorQuery);
}
header('Location: https://adrlist.com/myAccount');