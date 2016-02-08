<?php //This document will automatically force non SSL usage if not local. You do not need to include config.php, as it is included below.
//die(get_include_path());
if(stripos($_SERVER['SERVER_NAME'],'localhost') === false){
	require(get_include_path() . 'adrlist/includes/config.php');
}else{
	require(get_include_path() . 'adrlist.com/includes/config.php');
}
if(SSL){
	if(LOCAL){
		header('Location: http://localhost' . $_SERVER['PHP_SELF']);
	}else{
		header('Location: http://' . $_SERVER['PHP_SELF']);
	}
}