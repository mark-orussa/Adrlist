<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     Amazon_FPS
 *  @copyright   Copyright 2008-2010 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2008-09-17
 */
/******************************************************************************* 
 *    __  _    _  ___ 
 *   (  )( \/\/ )/ __)
 *   /__\ \    / \__ \
 *  (_)(_) \/\/  (___/
 * 
 *  Amazon FPS PHP5 Library
 *  Generated: Wed Sep 23 03:35:04 PDT 2009
 * 
 */

   /************************************************************************
    * REQUIRED
    * 
    * Access Key ID and Secret Acess Key ID, obtained from:
    * http://aws.amazon.com
    ***********************************************************************/
    define('AWS_ACCESS_KEY_ID', $_SERVER['HTTP_AWS_ACCESS_KEY_ID']);
    define('AWS_SECRET_ACCESS_KEY', $_SERVER['HTTP_AWS_SECRET_ACCESS_KEY']);

	//Specify whether to use the live production or testing sandbox url.
	$testing = true;
	if($testing){
		//Sandbox.
		define('AMAZON_API_URL','https://fps.sandbox.amazonaws.com');
		define('AMAZON_CBUI_URL','https://authorize.payments-sandbox.amazon.com/cobranded-ui/actions/start');
	}else{
		//Production.
		define('AMAZON_API_URL','https://fps.amazonaws.com');
		define('AMAZON_CBUI_URL','https://authorize.payments.amazon.com/cobranded-ui/actions/start');
	}
	if(LOCAL){
		define('SETOVERRIDEIPNURL','https://' . LOCALIP . '/localhost/' . DOMAIN . 'myAccount/amazonIPNListener.php');
	}else{
		define('SETOVERRIDEIPNURL','https://' . DOMAIN . '/myAccount/amazonIPNListener.php');
	}
   /************************************************************************ 
    * OPTIONAL ON SOME INSTALLATIONS
    *
    * Set include path to root of library, relative to Samples directory.
    * Only needed when running library from local directory.
    * If library is installed in PHP include path, this is not needed
    ***********************************************************************/   
    //set_include_path(get_include_path() . PATH_SEPARATOR . '../../../.');    
    
   /************************************************************************ 
    * OPTIONAL ON SOME INSTALLATIONS  
    * 
    * Autoload function is reponsible for loading classes of the library on demand
    * 
    * NOTE: Only one __autoload function is allowed by PHP per each PHP installation,
    * and this function may need to be replaced with individual //require_once statements
    * in case where other framework that define an __autoload already loaded.
    * 
    * However, since this library follows common naming convention for PHP classes it
    * may be possible to simply re-use an autoload mechanism defined by other frameworks
    * (provided library is installed in the PHP include path), and so classes may just 
    * be loaded even when this function is removed
    ***********************************************************************/   
	/*
	function __autoload($className){
		$filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
		$includePaths = explode(PATH_SEPARATOR, get_include_path());
		foreach($includePaths as $includePath){
			if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)){
				//require_once $filePath;
				return;
			}
		}
	}
	*/