<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     Amazon_CBUI
 *  @copyright   Copyright 2008-2011 Amazon Technologies, Inc.
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
 * 
 */

//require_once('Amazon/.config.inc.php');
class CBUISingleUsePipelineSample {

    function test() {
        $pipeline = new Amazon_CBUI_CBUISingleUsePipeline(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
		//setMandatoryParameters($callerReference, $returnUrl, $transactionAmount)
        $pipeline->setMandatoryParameters(uniqid(), "https://adrlist.com/myAccount/paymentAuthorization.php", "1");
        
        //optional parameters
		//$pipeline->addParameter("currencyCode", "USD");
        $pipeline->addParameter("paymentReason", "Project Monthly");
        $pipeline->addParameter("transactionAmount", "9.99");
        
        //SingleUse url
        print "Sample CBUI url for SingleUse pipeline : " . $pipeline->getUrl() . "\n";
    }
}

$test = new CBUISingleUsePipelineSample();
$test->test();
//CBUISingleUsePipelineSample::test();
