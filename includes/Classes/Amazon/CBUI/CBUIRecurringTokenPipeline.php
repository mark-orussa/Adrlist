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

//require_once('CBUIPipeline.php');
$debug->newFile('Classes/Amazon/CBUIRecurringTokenPipeline.php');

class Amazon_CBUI_CBUIRecurringTokenPipeline extends Amazon_CBUI_CBUIPipeline {

    /**
     * @param string $accessKeyId    Amazon Web Services Access Key ID.
     * @param string $secretAccessKey   Amazon Web Services Secret Access Key.
     */
    function Amazon_CBUI_CBUIRecurringTokenPipeline($awsAccessKey, $awsSecretKey) {
        parent::Amazon_CBUI_CBUIPipeline("Recurring", $awsAccessKey, $awsSecretKey);
    }

    /**
     * Set mandatory parameters required for recurring token pipeline.
     */
    function setMandatoryParameters($callerReference, $returnUrl, $transactionAmount, $recurringPeriod) {
        $this->addParameter("callerReference", $callerReference);
        $this->addParameter("returnURL", $returnUrl);
        $this->addParameter("transactionAmount", $transactionAmount);
        $this->addParameter("recurringPeriod", $recurringPeriod);
    }

    function validateParameters($parameters) {
        //mandatory parameters for recurring token pipeline
        if (!isset($parameters["transactionAmount"])) {
            throw new Exception("transactionAmount is missing in parameters.");
        }

        if (!isset($parameters["recurringPeriod"])) {
            throw new Exception("recurringPeriod is missing in parameters.");
        }
    }

}
