<?php
class Adrlist_BuildPage{
	/**
	 * Build the page content.
	 *
	 * Build the body, javascript includes, meta, css includes, and other foundational html content.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	array	$_javascriptIncludes	The userId of the sender.
	 * @param	array	$_cssIncludes			The userId of the recipient.
	 *
	*/
	
	//Properties.
	protected $_javascriptIncludes;
	protected $_cssIncludes;
	protected $_errorMessage;
	
	public function __construct(){
		global $debug;
		$this->_javascriptIncludes = NULL;
		$this->_cssIncludes = NULL;
		$this->_errorMessage = '';
	}
	
	/**
	 * Include php files.
	 *
	 * Accepts either a single file or an array of file names in filename.extension format.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	array string	$fileName	The file name(s) with the file extension(s). This script assumes the file is in the includes folder.
	 * @param	boolean			$throwError	If the user mistakenly tries to add multiple files without placing them into an array an error will be thrown.
	 *
	 * @return	string	A series of require_once statements.
	*/
	public function addIncludes($fileName,$throwError = ''){
		global $debug, $Dbc;
		if(!empty($throwError)){
			throw new Adrlist_CustomException('You are trying to include more than one file, but you haven\'t put it in an array.');
		}
		if(is_array($fileName)){
			foreach($fileName as $key){
				require_once($key);
			}
		}else{
			require_once($fileName);
		}
	}
	
	/**
	 * Include javascript files.
	 *
	 * Accepts either a single file or an array of file names in filename.extension format.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	array string	$fileName	The file name(s) with the file extension(s). If the file is relative or lacking a FQDN an autolink will be created.	
	 *
	 * @return	string	Valid html javascript include(s).
	*/
	public function addJs($fileName){
		if(!is_array($fileName)){
			$fileName = array($fileName);
		}
		foreach($fileName as $key){
			$querySeparator = !stripos($key,'?') === false ? '&' : '?';
			if(stripos($key,'http://') === false && stripos($key,'https://') === false){
				$this->_javascriptIncludes .= '<script type="text/javascript" src="' . LINKJS . '/' . $key . $querySeparator . date('j') . '"></script>
';
			}else{
				$this->_javascriptIncludes .= '<script type="text/javascript" src="' . $key . $querySeparator . date('j') . '"></script>
';
			}
		}
	}

	/**
	 * Include css files.
	 *
	 * Accepts either a single file or an array of file names in filename.extension format.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	 * @param	array string	$fileName	The file name(s) with the file extension(s). If the file is relative or lacking a FQDN an autolink will be created.	
	 *
	 * @return	string	Valid html javascript include(s).
	*/
	public function addCss($fileName){
		global $debug;
		if(is_array($fileName)){
			foreach($fileName as $key){
				if(stripos($key,'http://') === false && stripos($key,'https://') === false){
					$this->_cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $key .'?' . date('j') . '" type="text/css" media="all">
';
				}else{
					$this->_cssIncludes .= '<link rel="stylesheet" href="' . $key .'?' . date('j') . '" type="text/css" media="all">
';
				}
			}
		}else{
				if(stripos($fileName,'http://') === false && stripos($fileName,'https://') === false){
					$this->_cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $fileName .'?' . date('j') . '" type="text/css" media="all">
';
				}else{
					$this->_cssIncludes .= '<link rel="stylesheet" href="' . $fileName .'?' . date('j') . '" type="text/css" media="all">
';
				}
		}
	}

	public function buildFooter(){
		global $debug;
		$output = '
		</div>
		<div data-role="footer">
			<div class="textCenter" style="margin:.5em">
				<a href="http://markproaudio.com" data-role="none">Mark Pro Audio Inc</a> 2014
			</div>
		</div>
		' . googleCode() . $debug->output() . '
	</div>
</body>
</html>';
		return $output;
/*
<!-- mobile/tablet vs destop footer -->
		<div data-role="footer">
			<div class="desktop" style="margin:.5em">
				<div class="inline-block textLeft">
					<a href="' . LINKLEGAL . '">Terms of Use</a><a href="' . LINKPRIVACY . '" style="margin-left:1em">Privacy Policy</a>
				</div>
				<div class="inline-block right">
					<a href="http://markproaudio.com">Mark Pro Audio Inc</a> 2014
				</div>
			</div>
			<div class="mobile tablet noise">
				<div class="textCenter">
					<a href="' . LINKLEGAL . '">Terms of Use</a>/<a href="' . LINKPRIVACY . '">Privacy Policy</a>
				</div>
				<div class="textCenter">
					<a href="http://markproaudio.com">Mark Pro Audio Inc</a> 2014
				</div>
			</div>
		</div>


<!-- really old footer -->
		<div data-role="footer">
			<div class="hr1"></div>
			<div class="absolute textSmall" style="right:5px; z-index:100"><br>
				&copy; <a href="http://markproaudio.com">Mark Pro Audio Inc</a> 2014
			</div>
			<div class="footerLinks">
				<div class="left">
					<a class="footerLinksMain" href="' . AUTOLINK . '">Home</a>
				</div>
				<div class="left">
					<a class="footerLinksMain" href="' . LINKPLANS . '">Plans</a>		
					<a class="footerLinksSub" href="' . LINKFEATURES . '">Features</a>
					<a class="footerLinksSub" href="' . LINKPLANS . '">Plans</a>
				</div>
				<div class="left">
					<a class="footerLinksMain" href="' . LINKLOGIN . '">Login</a>
					<a class="footerLinksSub" href="' . LINKLOGIN . '/?invitationCode=NA">Create Account</a>
					<a class="footerLinksSub" href="' . LINKFORGOTPASSWORD . '/">Forgot Password</a>
				</div>
				<div class="left">
					<a class="footerLinksMain" href="' . LINKSUPPORT . '">Support</a>
					<a class="footerLinksSub" href="' . LINKSUPPORT . '">Contact</a>
					<a class="footerLinksSub" href="' . LINKFAQ . '">FAQ</a>
				</div>
				<div class="left">
					<a class="footerLinksMain" href="' . LINKLEGAL . '">Legal</a>		
					<a class="footerLinksSub" href="' . LINKPRIVACY . '">Privacy Policy</a>
					<a class="footerLinksSub" href="' . LINKLEGAL . '">Terms</a>
				</div>
			</div>
		</div>
*/
	}

	public function addErrorMessage($errorMessage){
		$this->_errorMessage .= $errorMessage;
	}

	public function output(){
		global $debug, $Dbc, $fileInfo, $message;
		$output = '';
		$head = '<!DOCTYPE HTML>
<html lang="en" xml:lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="Description" content="An online ADR list tool for professionals in the film and television production industry.">
<meta name="Keywords" content="ADR, looping, dubbing, film, edit, editor, dialogue, dialog, replacement, list, movie, tv, picture, video">
';
		if(is_array($fileInfo) && array_key_exists('title', $fileInfo) && $fileInfo['title'] != ''){
			$head .= '<title>'. THENAMEOFTHESITE . ' - ' . $fileInfo['title'] . '</title>';
		}elseif(!empty($fileInfo['title'])){
			$head .= '<title>'. THENAMEOFTHESITE . ' - ' . $fileInfo['title'] . '</title>';
		}else{
			$head .= '<title>'. THENAMEOFTHESITE . '</title>';
			$debug->add('The $title array for this page was not found.<br>');
		}
		$head .= '
<link rel="icon" href="' . LINKIMAGES . '/favicon.png" type="image/png">
<link rel="apple-touch-icon" href="' . LINKIMAGES . '/touch-icon-iphone.png">
<link rel="apple-touch-icon" sizes="72x72" href="' . LINKIMAGES . '/touch-icon-ipad.png">
<link rel="apple-touch-icon" sizes="114x114" href="' . LINKIMAGES . '/touch-icon-iphone4.png">
<link rel="stylesheet" href="' . LINKCSS . '/jquery/jquery-ui-1.10.4/custom-theme/jquery-ui-1.10.4.custom.min.css" media="all" type="text/css">
<link rel="stylesheet" href="' . LINKCSS . '/jquery/jqueryMobile/icon-pack/jqm-icon-pack-fa.css" media="all" type="text/css">
<link rel="stylesheet" href="' . LINKCSS . '/jquery/jqueryMobile/jquery.mobile-1.4.5.min.css" media="all" type="text/css">
<link rel="stylesheet" href="' . LINKCSS . '/jquery/jqueryMobile/custom-theme/custom-theme.min.css" media="all" type="text/css">
<link rel="stylesheet" href="' . LINKCSS . '/font-awesome-4.0.3/css/font-awesome.min.css" media="all" type="text/css">';
		$head .= empty($this->_cssIncludes) ? '': $this->_cssIncludes;			
		$head .= '<link rel="stylesheet" href="' . LINKCSS . '/main.css?' . date('j') . '" media="all" type="text/css">';
/*
<link rel="stylesheet" href="' . LINKCSS . '/jquery/jquery-ui-1.10.4/custom-theme/jquery-ui-1.10.4.custom.min.css" media="all" type="text/css">
*/
		$head .='
<script type="text/javascript" src="' . LINKJS . '/jquery/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="' . LINKJS . '/jquery/jquery-ui-1.10.4.custom.js"></script>
<script type="text/javascript" src="' . LINKJS . '/jquery/jquery.mobile-1.4.5.min.js"></script>
<script type="text/javascript" src="' . LINKJS . '/functions.js?' . date('j') . '"></script>
';
		$head .= empty($this->_javascriptIncludes) ? '': $this->_javascriptIncludes . '</head>';
		$path = $_SERVER['SCRIPT_NAME'];
		$scriptName = basename($path);
		//There are two different sets of links that vary depending on whether the user is logged in. One is for the top links for desktop browsers. The other is the panel links for tablet and mobile browsers.
		$linksArray = array();
		$linksArray['About'] = array(
			array('Features',AUTOLINK . '#features','th-large'),
			array('Plans',LINKPLANS,'list-alt'),
			array('Terms',LINKLEGAL,'book'),
			array('Privacy',LINKPRIVACY,'eye')
		);
		if(isset($_SESSION['auth']) && $_SESSION['auth']){
			//Get messages.
			$messagesStmt = $Dbc->prepare("SELECT
	COUNT(*) AS 'count'
FROM
	messageCenter
WHERE
	recipientUserId = ? AND
	readByRecipient IS NOT NULL");
			$messagesStmt->execute(array($_SESSION['userId']));
			$row = $messagesStmt->fetch(PDO::FETCH_ASSOC);
			$messagesCount = $row['count'];
			if(isset($_SESSION['siteRoleId']) && $_SESSION['siteRoleId'] == 5){
				$linksArray['Account'][] = array('Admin',LINKADMIN,'gear');
			}
			$linksArray['Account'][] = array('ADR Lists',LINKADRLISTS,'file-o');
			$linksArray['Account'][] = array('My Account',LINKMYACCOUNT,'user');
			$linksArray['Account'][] = array('Logout',LINKLOGIN . '?logout=1','sign-out fa-rotate-180');
		}else{
			$linksArray['Account'] = array(
				array('Login',LINKLOGIN,'sign-in'),
				array('Create Account',LINKCREATEACCOUNT,'plus'),
				array('Forgot Password',LINKFORGOTPASSWORD,'question')
			);
		}
		$linksArray['Support'] = array(
			array('FAQ',LINKFAQ,'question-circle'),
			array('Contact',LINKCONTACT,'envelope')
		);
		
		/*elseif(!strstr($_SERVER['PHP_SELF'],'login')){
			$linksArray[] = array('Login',LINKLOGIN,'lock');
			$linksArray[] = array('Create Account',LINKCREATEACCOUNT,'plus');
		}*/
		$topLinks = '<div class="menu-horizontal"><ul>';
		$panelLinks = '<div class="textCenter" style="margin:-.5em -1em 1em -1em">
	<a class="ui-btn ui-shadow ui-btn-icon-right ui-icon-delete ui-btn-b ui-mini" data-rel="close" style="text-decoration:none">Close</a>
</div>
<div data-role="collapsibleset" data-inset="false" data-theme="a" data-content-theme="a">';
		$x = 1;
		foreach($linksArray as $section => $subSection){
			$topLinks .= '<li class="relative"><span class="hand" toggle="' . $x . 'Links">' . $section . '</span><div class="topLinks roundedCornersBottom" id="' . $x . 'Links">';
			$panelLinks .= '	<div data-icon="false" data-role="collapsible">
		<h3>' . $section . '</h3>
		<ul data-role="listview" data-theme="c" data-divider-theme="c">';
			foreach($subSection as $key => $value){
				$topLinks .= '<div><a href="' . $value[1] . '" data-ajax="false"><i class="fa fa-' . $value[2] . '" style="color:#333;padding:0 .5em"></i>' . $value[0] . '</a></div>';
				$panelLinks .= '			<li data-icon="false"><a href="' . $value[1] . '" data-ajax="false"><i class="fa fa-' . $value[2] . '" ></i>' . $value[0] . '</a></li>';
			}
			$topLinks .= '</div>
			</li>';
			$panelLinks .= '		</ul>
	</div>';
			$x++;
		}
		$topLinks .= '</ul></div>';
		$panelLinks .= '</div>';
		//Header.
		$header = '<div class="relative" id="header" data-role="header"';
		if(isset($_SESSION['maintMode']) && $_SESSION['maintMode']){
			$header .= ' style="background-color:red">MAINTENANCE MODE!';
		}else{
			$header .= '>';
		}
		$header .='
	<div class="mobile tablet" style="height:68px">
		<div class="absolute" style="top:0;width:100%">
			<div class="left"><img alt="" src="' . LINKIMAGES . '/logo.png" style="height:68px;width:245px"></div>
			<div class="right menuButton">
				<a href="#menuLinksPanel" class="ui-btn ui-shadow ui-corner-all ui-icon-bars ui-btn-icon-notext"  style="margin-right:.5em">Menu</a>
			</div>
		</div>
	</div>
	<div class="desktop" style="height:68px;">
		<div style="background-color:#E9E9E9;width:100%">
			<img alt="" class="left" src="' . LINKIMAGES . '/logo.png" style="height:68px;width:245px">
			' . $topLinks . '
		</div>
	</div>
</div>';
		//Build the output.
		$output .= $head . '<body>
	<div class="cover" style="z-index:5;"></div>
	<div class="spinnerHolder" style="z-index:11;"><a data-ajax="false" href="' . AUTOLINK . '/' . $_SERVER['PHP_SELF'] . '"><img alt="" class="absolute" src="' . LINKIMAGES . '/spinner.png" style=""><p>Refresh</p></a></div>
	<div class="red textCenter">
		<noscript>(javascript required)</noscript>
	</div>
	<div data-role="page">
		<div class="contentFloater floater" style="z-index:10;"></div>
		<div data-role="panel" id="menuLinksPanel" data-position="right" data-display="overlay">
 		   ' . $panelLinks . '
		</div>
		<a href="#showMessage" data-rel="popup"></a>
		<div class="ui-content" data-role="popup" id="showMessage"></div>
		' . $header . '
		<div class="ui-content">
		';
		$output .= $this->_errorMessage == '' ? '' : $this->_errorMessage;
		$output .= '			<div class="textCenter textXlarge">
				' . $fileInfo['title'] . '
			</div>';
		return $output;
	}

	public function outputForPaymentProcessing(){
		global $debug, $fileInfo, $message;
		$output = '<!DOCTYPE HTML>
<html lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="Description" content="An online ADR list tool for professionals in the film and television production industry.">
<meta name="Keywords" content="ADR, looping, dubbing, film, edit, editor, dialogue, dialog, replacement, list, movie, tv, picture, video">
';
		if(is_array($fileInfo) && array_key_exists('title', $fileInfo) && $fileInfo['title'] != ''){
				$output .= '<title>'. THENAMEOFTHESITE . ' - ' . $fileInfo['title'] . '</title>
';
		}elseif(!empty($fileInfo['title'])){
			$output .= '<title>'. THENAMEOFTHESITE . ' - ' . $fileInfo['title'] . '</title>
';
		}else{
			$output .= '<title>'. THENAMEOFTHESITE . '</title>
';
			$debug->add('The $title array for this page was not found.<br>');
		}
		$output .= '
<link rel="icon" href="' . LINKIMAGES . '/favicon.png" type="image/png">
<link rel="apple-touch-icon" href="' . LINKIMAGES . '/touch-icon-iphone.png">
<link rel="apple-touch-icon" sizes="72x72" href="' . LINKIMAGES . '/touch-icon-ipad.png">
<link rel="apple-touch-icon" sizes="114x114" href="' . LINKIMAGES . '/touch-icon-iphone4.png">
<link rel="stylesheet" href="' . LINKCSS . '/main.css?' . date('j') . '" type="text/css" media="all">
';
		$output .= empty($this->_cssIncludes) ? '': $this->_cssIncludes;			
		$output .='<link type="text/css" href="' . LINKCSS . '/ui-lightness/jquery-ui-1.10.4.custom.css" rel="stylesheet">	
<script type="text/javascript" src="' . LINKJS . '/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="' . LINKJS . '/jquery-ui-1.10.4.custom.min.js"></script>
<script type="text/javascript" src="' . LINKJS . '/functions.js?' . date('j') . '"></script>
';
		$output .= empty($this->_javascriptIncludes) ? '': $this->_javascriptIncludes;			
		$output .= '<body>
	<div class="cover textCenter"></div>
	<div class="spinnerHolder"><img alt="" class="absolute" src="' . LINKIMAGES . '/spinner.png" style=""></div>
	<div class="floater" id="messageFloater"></div>
	<div class="layout" id="header"';
		if(isset($_SESSION['maintMode']) && $_SESSION['maintMode']){
			$output .= ' style="background-color:red">MAINTENANCE MODE!';
		}else{
			$output .= '>
';
		}
		$output .='		<div class="left"><img alt="" src="' . LINKIMAGES . '/logo.png" style="height:68px;width:245px"></div>
	<div class="break"></div>';
		return $output;
	}
}
