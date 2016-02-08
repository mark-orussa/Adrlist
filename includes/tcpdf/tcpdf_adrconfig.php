<?php
//
// Description : Try to automatically configure some TCPDF
//               constants if not defined.
//
//============================================================+

/**
 * @file
 * Try to automatically configure some TCPDF constants if not defined.
 * @package com.tecnick.tcpdf
 * @version 1.0.000
 */

// DOCUMENT_ROOT fix for IIS Webserver
if ((!isset($_SERVER['DOCUMENT_ROOT'])) OR (empty($_SERVER['DOCUMENT_ROOT']))){
	if(isset($_SERVER['SCRIPT_FILENAME'])){
		$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
	} elseif(isset($_SERVER['PATH_TRANSLATED'])){
		$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
	} else {
		// define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
		$_SERVER['DOCUMENT_ROOT'] = '/';
	}
}
$_SERVER['DOCUMENT_ROOT'] = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']);
if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/'){
	$_SERVER['DOCUMENT_ROOT'] .= '/';
}

// Load main configuration file only if the K_TCPDF_EXTERNAL_CONFIG constant is set to false.
if (!defined('K_TCPDF_EXTERNAL_CONFIG') OR !K_TCPDF_EXTERNAL_CONFIG){
	// define a list of default config files in order of priority
	$tcpdf_config_files = array(dirname(__FILE__).'/config/tcpdf_config.php', '/etc/php-tcpdf/tcpdf_config.php', '/etc/tcpdf/tcpdf_config.php', '/etc/tcpdf_config.php');
	foreach ($tcpdf_config_files as $tcpdf_config){
		if (@file_exists($tcpdf_config) AND is_readable($tcpdf_config)){
			require_once($tcpdf_config);
			break;
		}
	}
}

if (!defined('K_PATH_MAIN')){
	define ('K_PATH_MAIN', dirname(__FILE__).'/');
}

if (!defined('K_PATH_FONTS')){
	define ('K_PATH_FONTS', K_PATH_MAIN.'fonts/');
}

if (!defined('K_PATH_URL')){
	$k_path_url = K_PATH_MAIN; // default value for console mode
	if (isset($_SERVER['HTTP_HOST']) AND (!empty($_SERVER['HTTP_HOST']))){
		if(isset($_SERVER['HTTPS']) AND (!empty($_SERVER['HTTPS'])) AND (strtolower($_SERVER['HTTPS']) != 'off')){
			$k_path_url = 'https://';
		} else {
			$k_path_url = 'http://';
		}
		$k_path_url .= $_SERVER['HTTP_HOST'];
		$k_path_url .= str_replace( '\\', '/', substr(K_PATH_MAIN, (strlen($_SERVER['DOCUMENT_ROOT']) - 1)));
	}
	define ('K_PATH_URL', $k_path_url);
}

if (!defined('K_PATH_IMAGES')){
	/*$tcpdf_images_dirs = array(K_PATH_MAIN.'examples/images/', K_PATH_MAIN.'images/', '/usr/share/doc/php-tcpdf/examples/images/', '/usr/share/doc/tcpdf/examples/images/', '/usr/share/doc/php/tcpdf/examples/images/', '/var/www/tcpdf/images/', '/var/www/html/tcpdf/images/', '/usr/local/apache2/htdocs/tcpdf/images/', K_PATH_MAIN);
	foreach ($tcpdf_images_dirs as $tcpdf_images_path){
		if (@file_exists($tcpdf_images_path)){
			break;
		}
	}*/
	define ('K_PATH_IMAGES', '../images/');
}

if (!defined('PDF_HEADER_LOGO')){
	//The path to an images needs to be relative, and not use http://.
	$tcpdf_header_logo = '';
	$debug->add('K_PATH_IMAGES.\'logo.png\': ' . K_PATH_IMAGES.'logo.png.');
	if (file_exists(K_PATH_IMAGES.'logo.png')){
		$tcpdf_header_logo = 'logo.png';
	}else{
		$debug->add('Couldn\'t find: K_PATH_IMAGES.\'logo.png\'.');
	}
	define ('PDF_HEADER_LOGO', $tcpdf_header_logo);
	$debug->add('PDF_HEADER_LOGO: ' . PDF_HEADER_LOGO . '.');
}

if (!defined('PDF_HEADER_LOGO_WIDTH')){
	if (!empty($tcpdf_header_logo)){
		define ('PDF_HEADER_LOGO_WIDTH', 60);
	} else {
		define ('PDF_HEADER_LOGO_WIDTH', 0);
	}
}

if (!defined('K_PATH_CACHE')){
	define ('K_PATH_CACHE', sys_get_temp_dir().'/');
}

if (!defined('K_BLANK_IMAGE')){
	define ('K_BLANK_IMAGE', '_blank.png');
}

if (!defined('PDF_PAGE_FORMAT')){
	define ('PDF_PAGE_FORMAT', 'A4');
}

if (!defined('PDF_PAGE_ORIENTATION')){
	define ('PDF_PAGE_ORIENTATION', 'P');
}

if (!defined('PDF_CREATOR')){
	define ('PDF_CREATOR', THENAMEOFTHESITE);
}

if (!defined('PDF_AUTHOR')){
	define ('PDF_AUTHOR', THENAMEOFTHESITE);
}

if (!defined('PDF_HEADER_TITLE')){
	define ('PDF_HEADER_TITLE', '');
}

if (!defined('PDF_HEADER_STRING')){
	define ('PDF_HEADER_STRING', '');
}

if (!defined('PDF_UNIT')){
	define ('PDF_UNIT', 'mm');
	/*
    pt: point
    mm: millimeter (default)
    cm: centimeter
    in: inch
	*/
}

if (!defined('PDF_MARGIN_HEADER')){
	//Does not add to the top margin.
	define ('PDF_MARGIN_HEADER', 10);
}

if (!defined('PDF_MARGIN_FOOTER')){
	define ('PDF_MARGIN_FOOTER', 10);
}

if (!defined('PDF_MARGIN_TOP')){
	//Measured from the top of the page, regardless of the header margin.
	define ('PDF_MARGIN_TOP', 15);
}

if (!defined('PDF_MARGIN_BOTTOM')){
	define ('PDF_MARGIN_BOTTOM', 15);
}

if (!defined('PDF_MARGIN_LEFT')){
	define ('PDF_MARGIN_LEFT', 10);
}

if (!defined('PDF_MARGIN_RIGHT')){
	define ('PDF_MARGIN_RIGHT', 10);
}

if (!defined('PDF_FONT_NAME_MAIN')){
	define ('PDF_FONT_NAME_MAIN', 'lucidasansunicode');
}

if (!defined('PDF_FONT_SIZE_MAIN')){
	define ('PDF_FONT_SIZE_MAIN', 10);
}

if (!defined('PDF_FONT_NAME_DATA')){
	define ('PDF_FONT_NAME_DATA', 'times');
}

if (!defined('PDF_FONT_SIZE_DATA')){
	define ('PDF_FONT_SIZE_DATA', 8);
}

if (!defined('PDF_FONT_MONOSPACED')){
	define ('PDF_FONT_MONOSPACED', 'courier');
}

if (!defined('PDF_IMAGE_SCALE_RATIO')){
	define ('PDF_IMAGE_SCALE_RATIO', 1.0);
}

if (!defined('HEAD_MAGNIFICATION')){
	define('HEAD_MAGNIFICATION', 1.0);
}

if (!defined('K_CELL_HEIGHT_RATIO')){
	define('K_CELL_HEIGHT_RATIO', 1.0);
}

if (!defined('K_TITLE_MAGNIFICATION')){
	define('K_TITLE_MAGNIFICATION', 1.0);
}

if (!defined('K_SMALL_RATIO')){
	define('K_SMALL_RATIO', 2/3);
}

if (!defined('K_THAI_TOPCHARS')){
	define('K_THAI_TOPCHARS', true);
}

if (!defined('K_TCPDF_CALLS_IN_HTML')){
	define('K_TCPDF_CALLS_IN_HTML', true);
}

if (!defined('K_TCPDF_THROW_EXCEPTION_ERROR')){
	define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
}

//============================================================+
// END OF FILE
//============================================================+
