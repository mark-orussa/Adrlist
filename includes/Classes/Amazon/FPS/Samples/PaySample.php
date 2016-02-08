<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     Amazon_FPS
 *  @copyright   Copyright 2008-2009 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2010-08-28
 */
/******************************************************************************* 
 *    __  _    _  ___ 
 *   (  )( \/\/ )/ __)
 *   /__\ \    / \__ \
 *  (_)(_) \/\/  (___/
 * 
 *  Amazon FPS PHP5 Library
 *  Generated: Wed Jun 15 05:50:14 GMT+00:00 2011
 * 
 */

/**
 * Pay  Sample
 */
//include_once ('Amazon/.config.inc.php');

/************************************************************************
 * Instantiate Implementation of Amazon FPS
 * 
 * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants 
 * are defined in the .config.inc.php located in the same 
 * directory as this sample
 ***********************************************************************/
 $service = new Amazon_FPS_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
 
/************************************************************************
 * Uncomment to try out Mock Service that simulates Amazon_FPS
 * responses without calling Amazon_FPS service.
 *
 * Responses are loaded from local XML files. You can tweak XML files to
 * experiment with various outputs during development
 *
 * XML files available under Amazon/FPS/Mock tree
 *
 ***********************************************************************/
 // $service = new Amazon_FPS_Mock();

/************************************************************************
 * Setup request parameters and uncomment invoke to try out 
 * sample for Pay Action
 ***********************************************************************/
 // @TODO: set request. Action can be passed as Amazon_FPS_Model_PayRequest
 // object or array of parameters
 invokePay($service, $request);

                                                                                                    
/**
  * Pay Action Sample
  * 
  * Allows calling applications to move money from a sender to a recipient.
  *   
  * @param Amazon_FPS_Interface $service instance of Amazon_FPS_Interface
  * @param mixed $request Amazon_FPS_Model_Pay or array of parameters
  */
  function invokePay(Amazon_FPS_Interface $service, $request) 
  {
	  global $debug;
      try {
              $response = $service->pay($request);
              
                $debug->add("Service Response\n");
                $debug->add("=============================================================================\n");

                $debug->add("        PayResponse\n");
                if ($response->isSetPayResult()) { 
                    $debug->add("            PayResult\n");
                    $payResult = $response->getPayResult();
                    if ($payResult->isSetTransactionId()) 
                    {
                        $debug->add("                TransactionId\n");
                        $debug->add("                    " . $payResult->getTransactionId() . "\n");
                    }
                    if ($payResult->isSetTransactionStatus()) 
                    {
                        $debug->add("                TransactionStatus\n");
                        $debug->add("                    " . $payResult->getTransactionStatus() . "\n");
                    }
                } 
                if ($response->isSetResponseMetadata()) { 
                    $debug->add("            ResponseMetadata\n");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId()) 
                    {
                        $debug->add("                RequestId\n");
                        $debug->add("                    " . $responseMetadata->getRequestId() . "\n");
                    }
                } 

     } catch (Amazon_FPS_Exception $ex) {
         $debug->add("Caught Exception: " . $ex->getMessage() . "\n");
         $debug->add("Response Status Code: " . $ex->getStatusCode() . "\n");
         $debug->add("Error Code: " . $ex->getErrorCode() . "\n");
         $debug->add("Error Type: " . $ex->getErrorType() . "\n");
         $debug->add("Request ID: " . $ex->getRequestId() . "\n");
         $debug->add("XML: " . $ex->getXML() . "\n");
     }
 }
                                