<?php
/*******************************************************************************
 *	Copyright 2008-2011 Amazon Technologies, Inc.
 *	Licensed under the Apache License, Version 2.0 (the 'License');
 *
 *	You may not use this file except in compliance with the License.
 *	You may obtain a copy of the License at: http://aws.amazon.com/apache2.0
 *	This file is distributed on an 'AS IS' BASIS, WITHOUT WARRANTIES OR
 *	CONDITIONS OF ANY KIND, either express or implied. See the License for the
 *	specific language governing permissions and limitations under the License.
 ******************************************************************************/
$fileInfo = array('title' => 'Payment Authorization', 'fileName' => 'Amazon/IpnReturnUrlValidation/SignatureUtilsForOutbound.php');
$debug->newFile($fileInfo['fileName']);

class Amazon_IpnReturnUrlValidation_SignatureException extends Exception {}

class Amazon_IpnReturnUrlValidation_SignatureUtilsForOutbound {
	 
    const SIGNATURE_KEYNAME = "signature";
    const SIGNATURE_METHOD_KEYNAME = "signatureMethod";
    const SIGNATURE_VERSION_KEYNAME = "signatureVersion";
    const SIGNATURE_VERSION_1 = "1";
    const SIGNATURE_VERSION_2 = "2";
    const CERTIFICATE_URL_KEYNAME = "certificateUrl";

    const FPS_PROD_ENDPOINT = 'https://fps.amazonaws.com/';
    const FPS_SANDBOX_ENDPOINT = 'https://fps.sandbox.amazonaws.com/';
    const USER_AGENT_IDENTIFIER = 'Amazon FPS 2010-08-28 PHP5 Library 2.1';


	//Your AWS access key	
	private $aws_access_key;

	//Your AWS secret key. Required only for ipn or return url verification signed using signature version1.	
	private $aws_secret_key;

    public function __construct($aws_access_key = null, $aws_secret_key = null){
        $this->aws_access_key = $aws_access_key;
        $this->aws_secret_key = $aws_secret_key;
    }
	
    /**
     * Validates the request by checking the integrity of its parameters.
     * @param parameters - all the http parameters sent in IPNs or return urls. 
     * @param urlEndPoint should be the url which recieved this request. 
     * @param httpMethod should be either POST (IPNs) or GET (returnUrl redirections)
     */
    public function validateRequest(array $parameters, $urlEndPoint, $httpMethod)  {
		global $debug;
        $signatureVersion = $parameters[self::SIGNATURE_VERSION_KEYNAME];
        if(self::SIGNATURE_VERSION_2 == $signatureVersion){
            return $this->validateSignatureV2($parameters, $urlEndPoint, $httpMethod);
        } else {
            return $this->validateSignatureV1($parameters);
        }
    }

    /**
     * Verifies the signature using HMAC and your secret key. 
     */
    private function validateSignatureV1(array $parameters){
	
	    //We should not include signature while calculating string to sign.
    	$signature = $parameters[self::SIGNATURE_KEYNAME];
	    unset($parameters[self::SIGNATURE_KEYNAME]);
 
        $stringToSign = self::_calculateStringToSignV1($parameters);
	    //We should include signature back to array after calculating string to sign.
    	$parameters[self::SIGNATURE_KEYNAME] = $signature;
	        
        return $signature == base64_encode(hash_hmac('sha1', $stringToSign, $this->aws_secret_key, true));
    }
	
    /**
     * Verifies the signature. 
     * Only default algorithm OPENSSL_ALGO_SHA1 is supported.
     */
    private function validateSignatureV2(array $parameters, $urlEndPoint, $httpMethod){
		global $debug, $success;
		try{
			//1. Input validation
			$signature = $parameters[self::SIGNATURE_KEYNAME];
			if(!isset($signature)){
				//throw new Amazon_FPS_SignatureException
				throw new Adrlist_CustomException('',"'signature' is missing from the parameters.");
			}
			$signatureMethod = $parameters[self::SIGNATURE_METHOD_KEYNAME];
			if(!isset($signatureMethod)){
				throw new Adrlist_CustomException('',"'signatureMethod' is missing from the parameters.");
			}
			$signatureAlgorithm = self::getSignatureAlgorithm($signatureMethod);
			if(!isset($signatureAlgorithm)){
				throw new Adrlist_CustomException('',"'signatureMethod' present in parameters is invalid. Valid values are: RSA-SHA1");
			}
			$certificateUrl = $parameters[self::CERTIFICATE_URL_KEYNAME];
			if(!isset($certificateUrl)){
				throw new Adrlist_CustomException('',"'certificateUrl' is missing from the parameters.");
			}
			elseif(stripos($parameters[self::CERTIFICATE_URL_KEYNAME], self::FPS_PROD_ENDPOINT) !== 0 && stripos($parameters[self::CERTIFICATE_URL_KEYNAME], self::FPS_SANDBOX_ENDPOINT) !== 0){
				throw new Adrlist_CustomException('','The certificateUrl value must begin with ' . self::FPS_PROD_ENDPOINT . ' or ' . self::FPS_SANDBOX_ENDPOINT . '.');
			}
			$verified = $this->verifySignature($parameters, $urlEndPoint);
			if(!$verified){
				throw new Adrlist_CustomException('','The signature could not be verified by the FPS service');
			}
			return $verified;
	}catch(Adrlist_CustomException $e){
		$success = false;
		$debug->add('<pre>' . $e . '</pre>');
	}
}

private function httpsRequest($url){
	global $debug;
	// Compose the cURL request
	$caBundlePath = __DIR__ . "/../ca-bundle.crt";
	//$caBundlePath = __DIR__ . "/ca-bundle.crt";//This works when ca-bundle.crt is in the IpnReturnUrlValidation folder.
	clearstatcache();
	/*
	if(file_exists($caBundlePath)){
		die('file exists!');
	}else{
		die('file does not exist.');
	}
	*/
	$ch = curl_init();  
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FILETIME, false);
	//curl_setopt($ch, CURLOPT_SSLVERSION,3);
	//curl_setopt($ch, CURLOPT_PORT , 8088);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, '2');
	//curl_setopt($ch, CURLOPT_SSLCERT, $caBundlePath);
	curl_setopt($ch, CURLOPT_CAINFO, $caBundlePath);
	curl_setopt($ch, CURLOPT_CAPATH, $caBundlePath);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_NOSIGNAL, true);
	curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT_IDENTIFIER);	
	// Handle the encoding ifwe can.
	if(extension_loaded('zlib')){
		curl_setopt($ch, CURLOPT_ENCODING, '');
	}
	// Execute the request
	$response = curl_exec($ch);
	//$debug->add($response,'curl response on line ' . __LINE__);
	$curlError = curl_error($ch);
	if($curlError){
		error(__LINE__,'','There was a curl error: ' . $curlError);
	}
	// Grab only the body
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$responseBody = substr($response, $headerSize);
	
	// Close the cURL connection
	curl_close($ch);
	
	// Return the public key
	return $responseBody;
}

	private function verifySignature($parameters, $urlEndPoint){
		/*
		Method: verify_signature
		*/
		global $debug;
		// Switch hostnames
		if(stripos($parameters[self::CERTIFICATE_URL_KEYNAME], self::FPS_SANDBOX_ENDPOINT) === 0){
			//If the certificateUrl string has 'https://fps.sandbox.amazonaws.com/' at position 0 then $fpsServiceEndPoint = 'https://fps.sandbox.amazonaws.com/'.
			$fpsServiceEndPoint = self::FPS_SANDBOX_ENDPOINT;
		}elseif(stripos($parameters[self::CERTIFICATE_URL_KEYNAME], self::FPS_PROD_ENDPOINT) === 0){
			$fpsServiceEndPoint = self::FPS_PROD_ENDPOINT;
		}
		$url = $fpsServiceEndPoint . '?Action=VerifySignature&UrlEndPoint=' . rawurlencode($urlEndPoint);
		$queryString = rawurlencode(http_build_query($parameters, '', '&'));
		//$queryString = str_replace(array('%2F', '%2B'), array('%252F', '%252B'), $queryString);
		$url .= '&HttpParameters=' . $queryString . '&Version=2010-08-28';
		$debug->add('$url: ' . $url);
		$response = $this->httpsRequest($url);
		$debug->add('$ipn verification response: ' . $response);
		$xml = new SimpleXMLElement($response);
		$result = (string) $xml->VerifySignatureResult->VerificationStatus;
		return ($result === 'Success');
	}

    /**
     * Calculate String to Sign for SignatureVersion 1
     * @param array $parameters request parameters
     * @return String to Sign
     */
    private static function _calculateStringToSignV1(array $parameters){
        $data = '';
        uksort($parameters, 'strcasecmp');
        foreach ($parameters as $parameterName => $parameterValue){
            $data .= $parameterName . $parameterValue;
        }
        return $data;
    }

    
	
    
    private static function getSignatureAlgorithm($signatureMethod){
        if("RSA-SHA1" == $signatureMethod){
            return OPENSSL_ALGO_SHA1;
        }
        return null;
    }

}
?>
